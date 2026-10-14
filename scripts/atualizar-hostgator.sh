#!/usr/bin/env bash
set -Eeuo pipefail

# Intranet CRT-01 — implantação controlada HostGator.
# Uso: ./scripts/atualizar-hostgator.sh 1.0.1
# Não executa migrations nem rollback automático.

falhar() { printf 'ERRO: %s\n' "$*" >&2; exit 1; }
VERSAO="${1:-}"
[[ "$VERSAO" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || falhar 'Uso: atualizar-hostgator.sh X.Y.Z'
for cmd in git docker curl flock sha256sum awk; do
  command -v "$cmd" >/dev/null || falhar "Ferramenta ausente: $cmd"
done
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$RAIZ"
[[ -f .env.prod && -f compose.hostgator.yaml ]] || falhar 'Faltam .env.prod ou compose.hostgator.yaml.'
CONTROLE=/opt/infraestrutura/versoes
mkdir -p "$CONTROLE"
chmod 700 "$CONTROLE"
exec 9>"$CONTROLE/intranet-crt01.lock"
flock -n 9 || falhar 'Outra implantação está em andamento.'
COMPOSE=(docker compose --env-file .env.prod -f compose.prod.yaml -f compose.hostgator.yaml)
compose() { VERSAO_IMAGEM="$VERSAO" "${COMPOSE[@]}" "$@"; }

# A configuração exclusiva da VPS pode ser o único arquivo não rastreado.
[[ -z "$(git status --porcelain --untracked-files=normal | grep -v '^?? compose.hostgator.yaml$' || true)" ]] || falhar 'Árvore Git não está limpa (exceto compose.hostgator.yaml).'

git fetch --tags origin
TAG="v$VERSAO"
ALVO="$(git rev-parse -q --verify "refs/tags/$TAG^{commit}")" || falhar "Tag $TAG não encontrada."
ATUAL="$(git rev-parse HEAD)"
if [[ "$ALVO" != "$ATUAL" ]]; then
  git merge-base --is-ancestor "$ATUAL" "$ALVO" || falhar 'Atualização não linear; interrompida.'
fi
compose config --quiet

BACKUP="$(find /opt/backups/intranet-crt01/diarios -mindepth 1 -maxdepth 1 -type d -name '20*' | sort | tail -1)"
[[ -n "$BACKUP" && -f "$BACKUP/SHA256SUMS" ]] || falhar 'Backup diário não encontrado.'
IDADE=$(( $(date +%s) - $(stat -c %Y "$BACKUP/SHA256SUMS") ))
(( IDADE >= 0 && IDADE <= 129600 )) || falhar 'Backup com mais de 36 horas ou data inválida.'
(cd "$BACKUP" && sha256sum -c SHA256SUMS) || falhar 'Falha na integridade do backup.'

DIR="$CONTROLE/pre-$VERSAO-$(date +%Y%m%d-%H%M%S)"
mkdir -m 700 "$DIR"
cp compose.prod.yaml compose.hostgator.yaml "$DIR/"
printf '%s\n' "$ATUAL" > "$DIR/commit-anterior"
"${COMPOSE[@]}" ps --format json > "$DIR/containers-antes.json"
for servico in backend frontend trabalhador; do
  id="$("${COMPOSE[@]}" ps -q "$servico")"
  [[ -n "$id" ]] || falhar "Container ausente: $servico"
  imagem="$(docker inspect -f '{{.Image}}' "$id")"
  docker image tag "$imagem" "intranet-crt01-rollback-${servico}:pre-$VERSAO"
done
printf 'Commit atual: %s\nDestino: %s\nBackup: %s\nEstado anterior: %s\n' "$ATUAL" "$ALVO" "$BACKUP" "$DIR"
read -r -p 'Preparar código e imagens? [SIM]: ' resposta
[[ "$resposta" == SIM ]] || exit 0

if [[ "$ALVO" != "$ATUAL" ]]; then
  git merge --ff-only "$ALVO"
fi
compose config --quiet
compose build backend frontend

# O Doctrine não oferece uma interface JSON universal para status em todas as versões.
# Interpretar SOMENTE a linha "New" da tabela e falhar em caso de formato inesperado.
STATUS="$(compose run --rm --no-deps --entrypoint php backend bin/console doctrine:migrations:status --no-interaction)" || falhar 'Falha ao consultar migrations.'
printf '%s\n' "$STATUS"
PENDENTES="$(printf '%s\n' "$STATUS" | awk -F'|' '
  function trim(s) {gsub(/^[[:space:]]+|[[:space:]]+$/, "", s); return s}
  NF >= 4 && trim($2) == "New" {print trim($3)}
')"
[[ "$PENDENTES" =~ ^[0-9]+$ ]] || falhar 'Não foi possível interpretar migrations pendentes.'
[[ "$PENDENTES" == 0 ]] || falhar "Existem $PENDENTES migrations pendentes. Implantação bloqueada."

compose --dry-run up -d --no-build --no-deps backend frontend trabalhador
read -r -p 'Recriar os três serviços? [IMPLANTAR]: ' resposta
[[ "$resposta" == IMPLANTAR ]] || { echo 'Código/imagens preparados, mas containers não atualizados.'; exit 0; }

IMPLANTACAO_INICIADA=1
trap 'rc=$?; if (( rc != 0 )) && [[ "${IMPLANTACAO_INICIADA:-0}" == 1 ]]; then echo "ATENÇÃO: implantação pode estar parcial. Estado anterior em: $DIR" >&2; echo "Verifique containers e imagens; rollback NÃO é automático." >&2; fi' EXIT
compose up -d --no-build --no-deps backend frontend trabalhador

for servico in backend frontend trabalhador; do
  id="$(compose ps -q "$servico")"
  [[ -n "$id" ]] || falhar "Container ausente: $servico"
  case "$servico" in
    frontend) esperado="intranet-crt01-frontend:$VERSAO" ;;
    *) esperado="intranet-crt01-backend:$VERSAO" ;;
  esac
  esperado_id="$(docker image inspect -f '{{.Id}}' "$esperado")"
  atual_id="$(docker inspect -f '{{.Image}}' "$id")"
  [[ "$esperado_id" == "$atual_id" ]] || falhar "Imagem incorreta: $servico"
  pronto=0
  for _ in $(seq 1 24); do
    estado="$(docker inspect -f '{{.State.Status}}' "$id")"
    saude="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}sem-healthcheck{{end}}' "$id")"
    if [[ "$estado" == running && ( "$saude" == healthy || "$saude" == sem-healthcheck ) ]]; then pronto=1; break; fi
    sleep 5
  done
  (( pronto == 1 )) || falhar "Serviço não está pronto: $servico (estado=$estado, saúde=$saude)"
done

http_home="$(curl --silent --show-error --max-time 15 --output /dev/null --write-out '%{http_code}' https://intranet.crt01.gov.br/)" || falhar 'Falha HTTP na página inicial.'
http_auth="$(curl --silent --show-error --max-time 15 --output /dev/null --write-out '%{http_code}' https://intranet.crt01.gov.br/api/v1/auth/me)" || falhar 'Falha HTTP na API.'
[[ "$http_home" == 200 && "$http_auth" == 401 ]] || falhar "HTTP inesperado: home=$http_home auth=$http_auth"

printf 'versao=%s\ncommit=%s\ndata=%s\n' "$VERSAO" "$ALVO" "$(date -Is)" > "$CONTROLE/intranet-crt01.tmp"
mv "$CONTROLE/intranet-crt01.tmp" "$CONTROLE/intranet-crt01"
IMPLANTACAO_INICIADA=0
trap - EXIT
printf 'Implantação %s validada tecnicamente. Teste login e fluxos críticos manualmente.\n' "$VERSAO"
