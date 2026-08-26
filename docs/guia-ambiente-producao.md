# Guia do ambiente de produção

Este documento descreve a configuração Docker preparada para a Intranet CRT-01 no VPS. O ambiente de produção é independente do `compose.yaml` usado no desenvolvimento.

## Arquivos principais

- `compose.prod.yaml`: serviços e volumes de produção;
- `.env.prod.example`: modelo das variáveis obrigatórias, sem segredos reais;
- `docker/nginx/production.conf`: entrada HTTP interna da aplicação;
- `backend/Dockerfile`, alvo `producao`: Symfony em PHP-FPM, sem dependências de desenvolvimento;
- `frontend/Dockerfile`, alvo `producao`: Vue compilado e servido pelo Nginx.

## Serviços

- `proxy`: encaminha `/api/` ao PHP-FPM e as demais rotas ao frontend;
- `backend`: API Symfony em `APP_ENV=prod`;
- `trabalhador`: consumidor assíncrono do Symfony Messenger;
- `frontend`: arquivos estáticos compilados do Vue;
- `postgres`: banco de dados persistente;
- `redis`: mensageria persistente e protegida por senha.

O banco, o Redis e os uploads não publicam portas externas. A aplicação fica vinculada a `127.0.0.1` e deve receber HTTPS por um proxy reverso instalado no VPS.

## Variáveis obrigatórias

No VPS, crie o arquivo `.env.prod` a partir do exemplo:

```bash
cp .env.prod.example .env.prod
chmod 600 .env.prod
```

Substitua todos os valores de exemplo. Gere valores independentes para `APP_SECRET`, `BANCO_SENHA` e `REDIS_SENHA`. O arquivo `.env.prod` está ignorado pelo Git e não deve ser enviado ao GitHub.

## Comandos manuais de validação

```bash
docker compose --env-file .env.prod -f compose.prod.yaml config --quiet
docker compose --env-file .env.prod -f compose.prod.yaml build
docker compose --env-file .env.prod -f compose.prod.yaml up -d
docker compose --env-file .env.prod -f compose.prod.yaml exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
docker compose --env-file .env.prod -f compose.prod.yaml ps
```

O pipeline automatizará esses comandos depois que o VPS, o domínio e os secrets do GitHub forem configurados.

## Persistência e backup

Os volumes que precisam de backup são:

- `intranet-crt01-prod_dados_postgres`;
- `intranet-crt01-prod_uploads_backend`.

O volume do Redis pode ser incluído no backup, mas não substitui o backup do PostgreSQL. Nunca execute `docker compose down -v` em produção, pois essa opção remove os volumes.
