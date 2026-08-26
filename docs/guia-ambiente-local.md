# Guia do ambiente local

Este guia reúne os passos necessários para deixar a Intranet CRT-01 funcional depois de ligar ou reiniciar o computador.

## 1. Pré-requisitos

- Docker instalado e com o serviço em execução.
- Docker Compose disponível pelo comando `docker compose`.
- Portas locais livres, principalmente a `8082`.
- Terminal aberto na raiz do projeto:

```bash
cd /home/jaime/projetos/intranet-crt01
```

O PHP, Composer, Node e PostgreSQL do host não precisam ser usados. Execute as ferramentas do projeto dentro dos containers.

## 2. Preparação inicial

Esta etapa é necessária apenas na primeira execução. Se o arquivo `.env` já existir, não o substitua.

```bash
cp .env.exemplo .env
```

Confira no `.env` a porta e as credenciais locais. A porta padrão é:

```dotenv
PORTA_HTTP=8082
```

## 3. Iniciar o ambiente

Com o Docker funcionando, execute na raiz do projeto:

```bash
docker compose up -d
```

Se for a primeira execução, se o `Dockerfile` tiver mudado ou se ainda não existirem as imagens locais, construa-as:

```bash
docker compose up -d --build
```

Caso o Docker informe que o plugin `buildx` não foi encontrado, use temporariamente o construtor interno do Compose:

```bash
COMPOSE_BAKE=false docker compose up -d --build
```

Na primeira construção, o backend compila extensões PHP e pode levar alguns minutos. Não interrompa o comando enquanto houver progresso.

## 4. Conferir os serviços

```bash
docker compose ps
```

Os serviços principais são:

- `proxy`: publica a aplicação na porta `8082`;
- `frontend`: interface Vue;
- `backend`: API Symfony, que deve ficar `healthy`;
- `postgres`: banco de dados, que deve ficar `healthy`;
- `redis`: cache e mensageria, que deve ficar `healthy`;
- `trabalhador`: consumidor de mensagens assíncronas;
- `minio`, `correio` e `antivirus`: serviços auxiliares.

O antivírus pode permanecer com estado `health: starting` por algum tempo logo após a inicialização.

## 5. Preparar o banco de dados

Depois que o backend e o PostgreSQL estiverem saudáveis, aplique as migrações:

```bash
docker compose exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec -T backend php bin/console doctrine:schema:validate
```

As migrações preservam os dados existentes e aplicam somente alterações ainda pendentes.

## 6. Endereços locais

- Aplicação: `http://localhost:8082/`
- Swagger UI: `http://localhost:8082/api/documentacao`
- OpenAPI JSON: `http://localhost:8082/api/documentacao.json`
- Health check: `http://localhost:8082/api/v1/health`

Teste rápido pelo terminal:

```bash
curl -I http://localhost:8082/
```

Uma aplicação funcional deve responder com `HTTP/1.1 200 OK`.

## 7. Credenciais locais de demonstração

| Perfil | E-mail | Senha |
|---|---|---|
| Admin global | `admin@crt01.local` | `CRT01@Admin#2026` |
| Publicador | `publicador@crt01.local` | `CRT01@Publica#2026` |

Essas credenciais são exclusivas do ambiente local e não devem ser usadas em produção.

## 8. Validações úteis

```bash
docker compose exec -T backend composer testar
docker compose exec -T frontend npm run verificar-tipos
docker compose exec -T frontend npm run testar
docker compose exec -T frontend npm run build
```

## 9. Diagnóstico de problemas

### A porta 8082 não responde

Confira se o proxy está ativo e se a porta foi publicada:

```bash
docker compose ps
ss -ltnp | grep 8082
```

Consulte os logs:

```bash
docker compose logs --tail=200 proxy backend frontend
```

### A porta 8082 já está em uso

Identifique o processo com `ss -ltnp | grep 8082`. Pare o serviço conflitante ou altere `PORTA_HTTP` no `.env` e acesse a nova porta configurada.

### O backend não fica saudável

```bash
docker compose logs --tail=200 backend postgres redis
```

Depois de corrigir a causa, reinicie os serviços afetados:

```bash
docker compose restart backend proxy
```

### Uma construção foi interrompida

Primeiro tente novamente:

```bash
COMPOSE_BAKE=false docker compose up -d --build
```

Se a construção ficar parada por muito tempo sem novas mensagens, verifique se existe uma tentativa antiga ainda ativa:

```bash
ps -eo pid,etime,stat,cmd | grep 'docker compose up'
```

Encerre somente um processo antigo que tenha sido confirmado como órfão; não finalize builds que ainda estejam progredindo.

### Recriar somente os containers

```bash
docker compose down
docker compose up -d
```

O comando `docker compose down` não remove os volumes por padrão, portanto os dados locais são preservados. Não use a opção `-v` a menos que queira apagar os volumes e reinicializar os dados.

## 10. Encerrar ao terminar

É permitido deixar os containers ativos. Para desligar o ambiente e preservar os dados:

```bash
docker compose down
```

Para apenas interromper os serviços, mantendo os containers criados:

```bash
docker compose stop
```

Na próxima utilização, execute `docker compose start` ou novamente `docker compose up -d`.

