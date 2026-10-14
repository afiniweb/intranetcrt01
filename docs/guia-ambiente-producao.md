# Manual de instalação e operação no servidor da intranet

Este manual descreve a implantação da Intranet CRT-01 em um servidor Linux usando Docker Engine e Docker Compose. O ambiente de produção é independente do `compose.yaml` de desenvolvimento e usa o arquivo `compose.prod.yaml`.

## 1. Arquitetura

O ambiente possui os serviços `proxy` (entrada HTTP), `frontend` (Vue/Nginx), `backend` (Symfony/PHP-FPM), `trabalhador` (tarefas assíncronas), `postgres` (banco) e `redis` (fila). Somente o `proxy` publica uma porta; PostgreSQL e Redis ficam restritos à rede Docker.

Os dados permanentes ficam nos volumes:

- `intranet-crt01-prod_dados_postgres`: banco de dados;
- `intranet-crt01-prod_uploads_backend`: PDFs das publicações;
- `intranet-crt01-prod_dados_redis`: fila de tarefas.

Banco e uploads devem sempre fazer parte do backup. O Redis pode ser recriado, mas tarefas pendentes serão perdidas.

## 2. Requisitos do servidor

Recomendação inicial para uma instalação de pequeno ou médio porte:

- Linux 64 bits atualizado;
- 4 CPUs, 8 GB de RAM e 50 GB livres em armazenamento persistente;
- Docker Engine e plugin Docker Compose;
- IP fixo ou reserva DHCP e, preferencialmente, DNS interno;
- acesso administrativo por SSH e horário sincronizado por NTP.

Valide o Docker:

```bash
docker version
docker compose version
systemctl is-enabled docker
systemctl is-active docker
```

O usuário da implantação deve pertencer ao grupo `docker` ou possuir privilégios equivalentes. Membros desse grupo têm controle administrativo sobre o host.

## 3. Preparar os arquivos

Copie ou extraia o projeto em um diretório dedicado:

```bash
sudo mkdir -p /opt/intranet-crt01
sudo chown "$USER":"$USER" /opt/intranet-crt01
cd /opt/intranet-crt01
```

A entrega deve conter `compose.prod.yaml`, `.env.prod.example`, os diretórios `backend/`, `frontend/`, `docker/` e `scripts/`, além deste manual. Não envie `.env`, `.env.prod`, bancos, dumps ou senhas ao Git.

## 4. Configurar o ambiente

```bash
cp .env.prod.example .env.prod
chmod 600 .env.prod
```

Gere três valores diferentes. O formato hexadecimal evita caracteres que precisariam ser codificados nas URLs do PostgreSQL e Redis:

```bash
openssl rand -hex 32
openssl rand -hex 32
openssl rand -hex 32
```

Edite `.env.prod`:

```dotenv
IP_BIND=0.0.0.0
PORTA_HTTP=8080
VERSAO_IMAGEM=local
APP_SECRET=primeiro_valor_gerado
BANCO_NOME=intranet
BANCO_USUARIO=intranet
BANCO_SENHA=segundo_valor_gerado
REDIS_SENHA=terceiro_valor_gerado
```

- `IP_BIND=0.0.0.0` permite acesso direto pela rede interna.
- `IP_BIND=127.0.0.1` deve ser usado quando Nginx, Apache ou outro proxy reverso no host fornecer HTTPS.
- `PORTA_HTTP` define a porta TCP da aplicação.
- `VERSAO_IMAGEM` identifica as imagens entregues, especialmente no modo offline.

Para uso institucional, prefira DNS interno e HTTPS. Se usar HTTP inicialmente, restrinja a porta no firewall às redes do órgão.

```bash
docker compose --env-file .env.prod -f compose.prod.yaml config --quiet
```

Não publique a saída completa de `docker compose config`, pois ela contém os segredos resolvidos.

## 5. Instalação com internet

```bash
chmod +x scripts/*.sh
./scripts/instalar-producao.sh
```

O instalador valida a configuração, constrói as imagens, inicia os serviços, aplica as migrações, valida o esquema e solicita os dados do primeiro administrador quando necessário. A senha é digitada de forma oculta, deve ter entre 12 e 72 caracteres e não fica no histórico do terminal.

## 6. Instalação sem internet

Em uma máquina Linux com Docker, internet e o projeto configurado:

```bash
cp .env.prod.example .env.prod
# Preencha temporariamente os campos obrigatórios.
chmod 600 .env.prod
chmod +x scripts/*.sh
./scripts/gerar-pacote-offline.sh 1.0.0
```

O gerador criará `intranet-crt01-offline-1.0.0.tar.gz` e seu arquivo `.sha256`. Copie os dois ao servidor pelo canal aprovado pelo órgão e execute nele:

```bash
sha256sum -c intranet-crt01-offline-1.0.0.tar.gz.sha256
tar -xzf intranet-crt01-offline-1.0.0.tar.gz
cd 1.0.0
sha256sum -c SHA256SUMS
sudo mkdir -p /opt/intranet-crt01
sudo chown "$USER":"$USER" /opt/intranet-crt01
tar -xzf codigo-fonte.tar.gz -C /opt/intranet-crt01
docker load -i imagens-docker.tar
cat VERSAO.env
cd /opt/intranet-crt01
cp .env.prod.example .env.prod
chmod 600 .env.prod
```

Edite `.env.prod`, coloque em `VERSAO_IMAGEM` o valor de `VERSAO.env` e execute:

```bash
chmod +x scripts/*.sh
./scripts/instalar-producao.sh --sem-build
```

## 7. Validação

```bash
docker compose --env-file .env.prod -f compose.prod.yaml ps
curl -I http://127.0.0.1:8080/
curl -I http://127.0.0.1:8080/api/v1/health
```

Se `PORTA_HTTP` foi alterada, ajuste os comandos. As rotas devem responder com HTTP 200. Depois:

1. acesse por outro computador da rede;
2. autentique-se com o administrador criado;
3. cadastre um usuário Publicador e um tipo de conteúdo;
4. publique um PDF de teste e confirme sua visualização;
5. reinicie os serviços e confirme que os dados permanecem.

```bash
docker compose --env-file .env.prod -f compose.prod.yaml restart
```

## 8. Backup

```bash
./scripts/backup-producao.sh
```

O backup fica em `backups/AAAAMMDD-HHMMSS/` e contém:

- `banco.dump`: dump comprimido do PostgreSQL;
- `uploads.tar.gz`: PDFs enviados;
- `SHA256SUMS`: verificação de integridade.

Copie-o para armazenamento externo ao servidor. Automatize a execução diária, defina retenção e teste periodicamente a restauração em ambiente separado.

## 9. Restauração

Confirme cuidadosamente a data escolhida: a restauração substitui os registros atuais do banco.

```bash
cd /opt/intranet-crt01
sha256sum -c backups/AAAAMMDD-HHMMSS/SHA256SUMS

docker compose --env-file .env.prod -f compose.prod.yaml stop proxy backend trabalhador

docker compose --env-file .env.prod -f compose.prod.yaml exec -T postgres \
  sh -c 'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists --no-owner' \
  < backups/AAAAMMDD-HHMMSS/banco.dump

docker compose --env-file .env.prod -f compose.prod.yaml run --rm --no-deps -T backend \
  tar -xzf - -C /aplicacao/var/uploads \
  < backups/AAAAMMDD-HHMMSS/uploads.tar.gz

docker compose --env-file .env.prod -f compose.prod.yaml up -d
```

Repita as validações da seção anterior.

## 10. Atualização

Antes de atualizar, gere um backup, registre a versão instalada, leia as notas da nova versão e programe uma janela de manutenção.

```bash
./scripts/backup-producao.sh
docker compose --env-file .env.prod -f compose.prod.yaml build
docker compose --env-file .env.prod -f compose.prod.yaml up -d
docker compose --env-file .env.prod -f compose.prod.yaml exec -T backend \
  php bin/console doctrine:migrations:migrate --no-interaction
docker compose --env-file .env.prod -f compose.prod.yaml ps
```

Para atualização offline, carregue as novas imagens, substitua os arquivos da aplicação, ajuste `VERSAO_IMAGEM` e execute `./scripts/instalar-producao.sh --sem-build`. Não substitua `.env.prod`.

## 11. Operação diária

```bash
# Estado
docker compose --env-file .env.prod -f compose.prod.yaml ps

# Logs
docker compose --env-file .env.prod -f compose.prod.yaml logs --tail=200 proxy backend trabalhador postgres redis

# Parar e iniciar preservando dados
docker compose --env-file .env.prod -f compose.prod.yaml stop
docker compose --env-file .env.prod -f compose.prod.yaml start

# Recriar contêineres preservando volumes
docker compose --env-file .env.prod -f compose.prod.yaml down
docker compose --env-file .env.prod -f compose.prod.yaml up -d
```

Nunca use `docker compose down -v` em produção. A opção `-v` remove banco, uploads e fila.

## 12. Diagnóstico

Aplicação indisponível:

```bash
docker compose --env-file .env.prod -f compose.prod.yaml ps
docker compose --env-file .env.prod -f compose.prod.yaml logs --tail=200 proxy backend
ss -ltnp | grep 8080
```

Banco indisponível:

```bash
docker compose --env-file .env.prod -f compose.prod.yaml logs --tail=200 backend postgres
docker compose --env-file .env.prod -f compose.prod.yaml exec -T postgres \
  sh -c 'pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB"'
```

Se funcionar no servidor, mas não em outras máquinas, confira `IP_BIND`, firewall, regras da rede, DNS e `PORTA_HTTP`. Se PDFs não abrirem após restauração, confirme que `uploads.tar.gz` foi restaurado junto com o banco.

## 13. Checklist de entrega

- servidor inventariado e com IP fixo;
- Docker iniciando automaticamente;
- `.env.prod` protegido e segredos no cofre institucional;
- DNS e HTTPS configurados ou HTTP limitado à intranet;
- firewall permitindo somente portas necessárias;
- administrador criado e senha entregue por canal separado;
- backup automático de banco e uploads configurado;
- restauração testada;
- responsáveis técnicos e procedimento de suporte registrados;
- monitoramento de disco, serviços e certificado configurado.
