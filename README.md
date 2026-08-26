# Intranet - CRT-01

Plataforma de comunicação institucional construída com Symfony, PostgreSQL, Vue 3 e PrimeVue.

## Ambiente de desenvolvimento

1. Copie `.env.exemplo` para `.env` e ajuste as credenciais locais.
2. Execute `docker compose up --build`.
3. Acesse a aplicação em `http://localhost:8082`.
4. Acesse o Swagger em `http://localhost:8082/api/documentacao`.

O health check da API está disponível em `http://localhost:8082/api/v1/health`.

## Verificações

```bash
docker compose exec backend composer testar
docker compose exec frontend npm run verificar-tipos
docker compose exec frontend npm run testar
docker compose exec frontend npm run build
```

Consulte `docs/estrategia-desenvolvimento.md` para as etapas e convenções do projeto.
