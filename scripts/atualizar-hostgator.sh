#!/usr/bin/env bash
set -Eeuo pipefail

# Intranet CRT-01 — atualizador controlado para a HostGator.
# Uso: ./scripts/atualizar-hostgator.sh 1.0.1
# Não executa migrations, restaurações ou rollback automaticamente.

falhar() { printf 'ERRO: %s\n' "$*" >&2; exit 1; }
log() { printf '\n=== %s ===\n' "$*"; }

VERSAO="${1:-}"
[[ "$VERSAO" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || falhar 'Uso: atualizar-hostgator.sh X.Y.Z'

for cmd in git docker curl flock sha256sum awk find stat date grep mktemp; do
  command -v "$cmd" >/dev/null 2>&1 || falhar "Ferramenta ausente: $cmd"
done

docker compose version >/dev/null 2>&1 || falhar 'Docker Compose indisponível.'
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RAIZ"
[[ -f .env.prod && -f compose.prod.yaml && -f compose.hostgator.yaml ]] \
  || falhar 'Faltam arquivos de configuração da produção.'
[[ -d .git ]] || falhar 'Este diretório não é um repositório Git.'

CONTROLE=/opt/infraestrutura/versoes
BACKUPS=/opt/backups/intranet-crt01/diarios
mkdir -p "$CONTROLE"
chmod 700 "$CONTROLE"
exec 9>"$CONTROLE/intranet-crt01.lock"
flock -n 9 || falhar 'Outra implantação está em andamento.'

COMPOSE=(docker compose --env-file .env.prod -f compose.prod.yaml -f compose.hostgator.yaml)
compose() { VERSAO_IMAGEM="$VERSAO" "${COMPOSE[@]}" "$@"; }

# Aceita somente o override local como arquivo não rastreado.
SUJOS="$(git status --porcelain --untracked-files=normal | grep -v '^?? compose.hostgator.yaml$' || true)"
[[ -z "$SUJOS" ]] || falhar 'Existem alterações locais no Git. Revise antes de prosseguir.'

log 'VALIDANDO TAG E HISTÓRICO'
git fetch --tags origin
TAG="v$VERSAO"
ALVO="$(git rev-parse -q --verify "refs/tags/$TAG^{commit}")" || falhar "Tag $TAG não encontrada."
ATUAL="$(git rev-parse HEAD)"
[[ "$(git branch --show-current)" == main ]] || falhar 'A implantação exige a branch main.'
if [[ "$ALVO" != "$ATUAL" ]]; then
  git merge-base --is-ancestor "$ATUAL" "$ALVO" \
    || falhar 'A tag não é descendente do commit local. Atualização não linear.'
fi
compose config --quiet || falhar 'Configuração Compose inválida.'

log 'VALIDANDO BACKUP'
[[ -d "$BACKUPS" ]] || falhar 'Diretório de backups inexistente.'
BACKUP="$(find "$BACKUPS" -mindepth 1 -maxdepth 1 -type d -name '20*' | sort | tail -n 1)"
[[ -n "$BACKUP" && -f "$BACKUP/SHA256SUMS" ]] || falhar 'Backup diário não encontrado.'
IDADE=$(( $(date +%s) - $(stat -c %Y "$BACKUP/SHA256SUMS") ))
(( IDADE >= 0 && IDADE <= 129600 )) || falhar 'Backup com mais de 36 horas ou data inválida.'
(cd "$BACKUP" && sha256sum -c SHA256SUMS) || falhar 'Falha na integridade do backup.'

log 'REGISTRANDO ESTADO ANTERIOR'
DIR="$CONTROLE/pre-$VERSAO-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 "$DIR"
ID_TENTATIVA="${DIR##*/}"
cp compose.prod.yaml compose.hostgator.yaml "$DIR/"
printf '%s\n' "$ATUAL" > "$DIR/commit-git-anterior"
if [[ -f "$CONTROLE/intranet-crt01" ]]; then
  cp "$CONTROLE/intranet-crt01" "$DIR/versao-registrada-anterior"
fi
"${COMPOSE[@]}" ps --format json > "$DIR/containers-antes.json"

for servico in backend frontend trabalhador; do
  id="$("${COMPOSE[@]}" ps -q "$servico")"
  [[ -n "$id" ]] || falhar "Container anterior ausente: $servico"
  estado="$(docker inspect -f '{{.State.Status}}' "$id")"
  [[ "$estado" == running ]] || falhar "Container anterior não está executando: $servico"
  imagem_id="$(docker inspect -f '{{.Image}}' "$id")"
  printf '%s\t%s\t%s\n' "$servico" "$id" "$imagem_id" >> "$DIR/imagens-anteriores.tsv"
  docker image tag "$imagem_id" "intranet-crt01-rollback-${servico}:${ID_TENTATIVA}"
done

printf 'Commit Git atual: %s\nCommit da tag: %s\nBackup: %s\nEstado anterior: %s\n' \
  "$ATUAL" "$ALVO" "$BACKUP" "$DIR"
read -r -p 'Preparar código e novas imagens? Digite SIM: ' resposta
[[ "$resposta" == SIM ]] || { echo 'Cancelado sem atualizar os containers.'; exit 0; }

log 'PREPARANDO CÓDIGO E IMAGENS'
if [[ "$ALVO" != "$ATUAL" ]]; then
  git merge --ff-only "$ALVO" || falhar 'Falha ao avançar o código para a tag.'
fi
compose config --quiet || falhar 'Configuração Compose inválida após atualização do código.'
compose build backend frontend || falhar 'Falha na construção das imagens.'

log 'VERIFICANDO MIGRATIONS (SOMENTE LEITURA)'
STATUS="$(compose run --rm --no-deps --entrypoint php backend \
  bin/console doctrine:migrations:status --no-interaction --no-ansi)" \
  || falhar 'Falha ao consultar migrations. Verifique a conexão com o banco.'
printf '%s\n' "$STATUS"

# Na tabela do Doctrine, a linha é "|  | New | 0 |".
# Exige exatamente uma linha New e um valor inteiro.
PENDENTES="$(printf '%s\n' "$STATUS" | awk -F'|' '
  function trim(s) { gsub(/^[[:space:]]+|[[:space:]]+$/, "", s); return s }
  NF >= 4 && trim($3) == "New" { print trim($4); encontrados++ }
  END { if (encontrados != 1) exit 1 }
')" || falhar 'Não foi possível identificar migrations pendentes.'
[[ "$PENDENTES" =~ ^[0-9]+$ ]] || falhar 'Quantidade inválida de migrations pendentes.'
[[ "$PENDENTES" =~ ^0+$ ]] || falhar "Existem $PENDENTES migrations pendentes. Implantação bloqueada."

log 'AGUARDANDO AUTORIZAÇÃO'
printf 'Versão preparada: %s\n' "$VERSAO"
printf 'Os containers backend, frontend e trabalhador serão recriados.\n'
printf 'PostgreSQL, Redis, proxy e volumes não serão recriados por este comando.\n'
read -r -p 'Autorizar a implantação? Digite IMPLANTAR: ' resposta
[[ "$resposta" == IMPLANTAR ]] || {
  echo 'Código e imagens preparados; containers anteriores permanecem em execução.'
  exit 0
}

IMPLANTACAO_INICIADA=1
avisar_falha() {
  rc=$?
  if (( rc != 0 )) && [[ "${IMPLANTACAO_INICIADA:-0}" == 1 ]]; then
    printf '\nATENÇÃO: a implantação pode estar parcial.\n' >&2
    printf 'Estado anterior: %s\n' "$DIR" >&2
    printf 'Imagens preservadas: intranet-crt01-rollback-{backend,frontend,trabalhador}:%s\n' "$ID_TENTATIVA" >&2
    printf 'Não houve rollback automático nem alteração de migrations.\n' >&2
  fi
}
trap avisar_falha EXIT

log 'RECRIANDO SOMENTE OS SERVIÇOS DA APLICAÇÃO'
compose up -d --no-build --no-deps backend frontend trabalhador \
  || falhar 'Falha ao recriar serviços.'

log 'VALIDANDO CONTAINERS E IMAGENS'
for servico in backend frontend trabalhador; do
  case "$servico" in
    frontend) esperado="intranet-crt01-frontend:$VERSAO" ;;
    *) esperado="intranet-crt01-backend:$VERSAO" ;;
  esac
  esperado_id="$(docker image inspect -f '{{.Id}}' "$esperado")" \
    || falhar "Imagem não encontrada: $esperado"
  pronto=0
  for _ in $(seq 1 24); do
    id="$(compose ps -q "$servico")"
    if [[ -n "$id" ]]; then
      atual_id="$(docker inspect -f '{{.Image}}' "$id")"
      estado="$(docker inspect -f '{{.State.Status}}' "$id")"
      saude="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}sem-healthcheck{{end}}' "$id")"
      if [[ "$atual_id" == "$esperado_id" && "$estado" == running && \
            ( "$saude" == healthy || "$saude" == sem-healthcheck ) ]]; then
        pronto=1
        break
      fi
    fi
    sleep 5
  done
  (( pronto == 1 )) || falhar "Serviço não ficou pronto com a imagem esperada: $servico"
  printf '%s: running (%s)\n' "$servico" "$esperado"
done

# Verificação complementar do processo do trabalhador.
TRABALHADOR_ID="$(compose ps -q trabalhador)"
docker top "$TRABALHADOR_ID" 2>/dev/null | grep -q '[m]essenger:consume' \
  || falhar 'Processo do consumidor Messenger não identificado no trabalhador.'

log 'VALIDANDO ENDPOINTS PÚBLICOS'
HOME_HTTP="$(curl --silent --show-error --max-time 15 --output /dev/null \
  --write-out '%{http_code}' https://intranet.crt01.gov.br/)" \
  || falhar 'Falha HTTP na página inicial.'
AUTH_HTTP="$(curl --silent --show-error --max-time 15 --output /dev/null \
  --write-out '%{http_code}' https://intranet.crt01.gov.br/api/v1/auth/me)" \
  || falhar 'Falha HTTP na API.'
[[ "$HOME_HTTP" == 200 && "$AUTH_HTTP" == 401 ]] \
  || falhar "HTTP inesperado: home=$HOME_HTTP auth=$AUTH_HTTP"

log 'REGISTRANDO VERSÃO IMPLANTADA'
ARQUIVO_TEMP="$(mktemp "$CONTROLE/.intranet-crt01.XXXXXXXX")"
printf 'versao=%s\ncommit=%s\ndata=%s\nestado_anterior=%s\n' \
  "$VERSAO" "$ALVO" "$(date -Is)" "$DIR" > "$ARQUIVO_TEMP"
chmod 600 "$ARQUIVO_TEMP"
mv -f "$ARQUIVO_TEMP" "$CONTROLE/intranet-crt01"
IMPLANTACAO_INICIADA=0
trap - EXIT
printf '\nImplantação %s validada tecnicamente.\n' "$VERSAO"
printf 'Realize testes manuais de login, permissões e fluxos críticos.\n'
