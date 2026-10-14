#!/usr/bin/env bash
set -euo pipefail

RAIZ=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$RAIZ"

if [[ ! -f .env.prod ]]; then
  echo "Arquivo .env.prod não encontrado. Ele é necessário para validar a composição." >&2
  exit 1
fi

VERSAO=${1:-$(date +%Y%m%d-%H%M%S)}
DESTINO="$RAIZ/pacote-instalacao/$VERSAO"
mkdir -p "$DESTINO"

COMPOSE=(docker compose --env-file .env.prod -f compose.prod.yaml)
VERSAO_IMAGEM="$VERSAO" "${COMPOSE[@]}" config --quiet
VERSAO_IMAGEM="$VERSAO" "${COMPOSE[@]}" build backend frontend
docker pull nginx:1.27-alpine
docker pull postgres:16-alpine
docker pull redis:7.4-alpine

docker save \
  "intranet-crt01-backend:$VERSAO" \
  "intranet-crt01-frontend:$VERSAO" \
  nginx:1.27-alpine postgres:16-alpine redis:7.4-alpine \
  -o "$DESTINO/imagens-docker.tar"

tar \
  --exclude=.git \
  --exclude=.env \
  --exclude=.env.prod \
  --exclude=backups \
  --exclude=pacote-instalacao \
  --exclude=backend/vendor \
  --exclude=backend/var \
  --exclude=frontend/node_modules \
  --exclude=frontend/dist \
  -czf "$DESTINO/codigo-fonte.tar.gz" .

printf 'VERSAO_IMAGEM=%s\n' "$VERSAO" > "$DESTINO/VERSAO.env"
(cd "$DESTINO" && sha256sum imagens-docker.tar codigo-fonte.tar.gz > SHA256SUMS)

ARQUIVO_FINAL="$RAIZ/pacote-instalacao/intranet-crt01-offline-$VERSAO.tar.gz"
tar -C "$RAIZ/pacote-instalacao" -czf "$ARQUIVO_FINAL" "$VERSAO"
(cd "$RAIZ/pacote-instalacao" && sha256sum "$(basename "$ARQUIVO_FINAL")" > "$(basename "$ARQUIVO_FINAL").sha256")

echo "Pacote offline criado em: $ARQUIVO_FINAL"
