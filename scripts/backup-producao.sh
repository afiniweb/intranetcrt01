#!/usr/bin/env bash
set -euo pipefail

RAIZ=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$RAIZ"

if [[ ! -f .env.prod ]]; then
  echo "Arquivo .env.prod não encontrado." >&2
  exit 1
fi

COMPOSE=(docker compose --env-file .env.prod -f compose.prod.yaml)
DATA=$(date +%Y%m%d-%H%M%S)
DESTINO="$RAIZ/backups/$DATA"
mkdir -p "$DESTINO"

"${COMPOSE[@]}" exec -T postgres sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > "$DESTINO/banco.dump"
"${COMPOSE[@]}" exec -T backend tar -czf - -C /aplicacao/var/uploads . > "$DESTINO/uploads.tar.gz"
sha256sum "$DESTINO/banco.dump" "$DESTINO/uploads.tar.gz" > "$DESTINO/SHA256SUMS"

echo "Backup criado em: $DESTINO"
