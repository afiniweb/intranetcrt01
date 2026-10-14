#!/usr/bin/env bash
set -euo pipefail

RAIZ=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
cd "$RAIZ"

if [[ ! -f .env.prod ]]; then
  echo "Arquivo .env.prod não encontrado. Copie .env.prod.example e defina os segredos." >&2
  exit 1
fi

COMPOSE=(docker compose --env-file .env.prod -f compose.prod.yaml)

"${COMPOSE[@]}" config --quiet
if [[ "${1:-}" != "--sem-build" ]]; then
  "${COMPOSE[@]}" build
fi
"${COMPOSE[@]}" up -d
"${COMPOSE[@]}" exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
"${COMPOSE[@]}" exec -T backend php bin/console doctrine:schema:validate

echo
echo "Verificando o administrador inicial..."
"${COMPOSE[@]}" exec backend php bin/console app:criar-admin-inicial

"${COMPOSE[@]}" ps
echo "Instalação concluída."
