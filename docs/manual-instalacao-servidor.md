# Manual passo a passo de instalação da Intranet CRT-01

Este documento orienta a instalação da Intranet CRT-01 em um servidor Linux da rede interna do órgão. Os comandos devem ser executados por um técnico com acesso administrativo ao servidor.

## 1. Registrar as informações do servidor

Antes da instalação, registre:

1. nome do servidor;
2. endereço IP fixo;
3. distribuição e versão do Linux;
4. nome DNS interno que será usado;
5. porta liberada para a aplicação;
6. responsável técnico pelo servidor;
7. local externo onde os backups serão armazenados.

Exemplo de endereço final:

```text
http://intranet.crt01.local:8080
```

## 2. Conferir os requisitos mínimos

Confirme que o servidor possui, preferencialmente:

1. Linux 64 bits atualizado;
2. quatro CPUs;
3. 8 GB de memória RAM;
4. pelo menos 50 GB livres em disco;
5. IP fixo ou reserva DHCP;
6. acesso administrativo por SSH;
7. data e hora sincronizadas por NTP.

Use os seguintes comandos para conferir o equipamento:

```bash
uname -a
cat /etc/os-release
nproc
free -h
df -h
ip address
timedatectl
```

## 3. Instalar e validar o Docker

Instale no servidor o Docker Engine e o plugin Docker Compose de acordo com a distribuição Linux utilizada. Configure o serviço para iniciar automaticamente.

Valide:

```bash
docker version
docker compose version
systemctl is-enabled docker
systemctl is-active docker
```

Os dois últimos comandos devem retornar `enabled` e `active`.

Se o técnico operar sem `sudo`, adicione-o ao grupo `docker` e abra uma nova sessão:

```bash
sudo usermod -aG docker "$USER"
```

O grupo `docker` concede controle administrativo sobre o host e deve ser restrito aos responsáveis autorizados.

## 4. Criar o diretório da aplicação

Crie um diretório exclusivo:

```bash
sudo mkdir -p /opt/intranet-crt01
sudo chown "$USER":"$USER" /opt/intranet-crt01
cd /opt/intranet-crt01
```

Evite instalar a aplicação dentro de diretórios temporários ou na pasta pessoal de um usuário que possa ser removido.

## 5. Transferir os arquivos

Transfira para `/opt/intranet-crt01` o pacote entregue pela equipe de desenvolvimento. Ao final, confira se existem:

```text
backend/
docker/
docs/
frontend/
scripts/
.env.prod.example
compose.prod.yaml
```

Confira:

```bash
cd /opt/intranet-crt01
ls -la
```

Não reutilize arquivos `.env.prod` de ambientes de teste e não armazene senhas no Git.

## 6. Escolher o modo de instalação

Escolha somente uma opção:

1. instalação online, quando o servidor acessa a internet e registros de imagens;
2. instalação offline, quando o servidor está isolado da internet.

Para a instalação offline, a equipe de desenvolvimento deve fornecer estes dois arquivos:

```text
intranet-crt01-offline-VERSAO.tar.gz
intranet-crt01-offline-VERSAO.tar.gz.sha256
```

O primeiro arquivo reúne o código-fonte, todas as imagens Docker e os checksums internos. O segundo permite validar o pacote antes de abri-lo.

## 7. Validar o pacote offline

Esta etapa é necessária somente na instalação offline. No diretório que contém os arquivos entregues, substitua `1.0.0` pela versão recebida e execute:

```bash
sha256sum -c intranet-crt01-offline-1.0.0.tar.gz.sha256
tar -xzf intranet-crt01-offline-1.0.0.tar.gz
cd 1.0.0
sha256sum -c SHA256SUMS
```

O pacote externo, `imagens-docker.tar` e `codigo-fonte.tar.gz` devem apresentar `OK`. Interrompa a instalação caso exista qualquer divergência.

Extraia o código e carregue as imagens:

```bash
sudo mkdir -p /opt/intranet-crt01
sudo chown "$USER":"$USER" /opt/intranet-crt01
tar -xzf codigo-fonte.tar.gz -C /opt/intranet-crt01
docker load -i imagens-docker.tar
cat VERSAO.env
```

Anote o valor de `VERSAO_IMAGEM` apresentado.

## 8. Criar o arquivo de configuração

Entre no diretório da aplicação e copie o modelo:

```bash
cd /opt/intranet-crt01
cp .env.prod.example .env.prod
chmod 600 .env.prod
```

Gere três segredos diferentes:

```bash
openssl rand -hex 32
openssl rand -hex 32
openssl rand -hex 32
```

Guarde os valores em cofre institucional. Eles serão utilizados para:

1. `APP_SECRET`;
2. `BANCO_SENHA`;
3. `REDIS_SENHA`.

## 9. Preencher a configuração

Edite `.env.prod`:

```bash
nano .env.prod
```

Exemplo para acesso direto pela rede interna:

```dotenv
IP_BIND=0.0.0.0
PORTA_HTTP=8080
VERSAO_IMAGEM=local
APP_SECRET=PRIMEIRO_SEGREDO
BANCO_NOME=intranet
BANCO_USUARIO=intranet
BANCO_SENHA=SEGUNDO_SEGREDO
REDIS_SENHA=TERCEIRO_SEGREDO
```

Na instalação offline, substitua `local` pela versão informada em `VERSAO.env`.

Use `IP_BIND=127.0.0.1` quando existir um proxy reverso no servidor fornecendo HTTPS. Use `0.0.0.0` somente quando a aplicação precisar responder diretamente na rede interna.

## 10. Validar a configuração

Execute:

```bash
docker compose --env-file .env.prod -f compose.prod.yaml config --quiet
```

O comando deve terminar sem mensagens de erro. Não compartilhe a saída completa de `docker compose config`, pois ela pode mostrar os segredos.

## 11. Liberar a porta no firewall

Se a aplicação responder diretamente na intranet, libere a porta definida em `PORTA_HTTP` somente para as redes autorizadas pelo órgão.

Exemplo com `firewalld` para a rede `192.168.0.0/16` e porta `8080`:

```bash
sudo firewall-cmd --permanent \
  --add-rich-rule='rule family="ipv4" source address="192.168.0.0/16" port port="8080" protocol="tcp" accept'
sudo firewall-cmd --reload
```

Adapte a rede e a ferramenta de firewall à infraestrutura local. Não exponha PostgreSQL ou Redis.

## 12. Executar a instalação online

Para servidor com internet:

```bash
cd /opt/intranet-crt01
chmod +x scripts/*.sh
./scripts/instalar-producao.sh
```

O processo construirá as imagens, iniciará os serviços, aplicará as migrações e solicitará o primeiro administrador.

## 13. Executar a instalação offline

Para servidor sem internet, depois de carregar `imagens-docker.tar`:

```bash
cd /opt/intranet-crt01
chmod +x scripts/*.sh
./scripts/instalar-producao.sh --sem-build
```

Não execute o modo comum em um servidor offline, pois ele tentará reconstruir as imagens e poderá precisar baixar dependências.

## 14. Criar o primeiro administrador

Durante a primeira instalação, responda às perguntas exibidas no terminal:

1. nome e sigla da instituição;
2. nome, UF e cidade do escritório sede;
3. nome e e-mail do administrador;
4. senha inicial do administrador;
5. confirmação da senha.

A senha deve ter entre 12 e 72 caracteres. Ela não será exibida enquanto for digitada.

Caso seja necessário executar essa etapa separadamente:

```bash
docker compose --env-file .env.prod -f compose.prod.yaml exec backend \
  php bin/console app:criar-admin-inicial
```

O comando não cria outro administrador quando um já existe.

## 15. Conferir os contêineres

Execute:

```bash
docker compose --env-file .env.prod -f compose.prod.yaml ps
```

Confirme que `proxy`, `frontend`, `backend`, `trabalhador`, `postgres` e `redis` estão ativos. Backend e PostgreSQL não devem apresentar falhas de saúde.

Se houver erro:

```bash
docker compose --env-file .env.prod -f compose.prod.yaml logs --tail=200
```

## 16. Testar no próprio servidor

Considerando a porta `8080`:

```bash
curl -I http://127.0.0.1:8080/
curl -I http://127.0.0.1:8080/api/v1/health
```

As duas requisições devem responder com HTTP 200.

Confirme também a porta em escuta:

```bash
ss -ltnp | grep 8080
```

## 17. Testar em outra máquina da intranet

Em um computador cliente:

1. abra `http://IP_DO_SERVIDOR:8080` ou o nome DNS definido;
2. autentique-se com o administrador criado;
3. confirme o acesso à área administrativa;
4. verifique se não há erros no navegador;
5. encerre a sessão e autentique-se novamente.

Se funcionar no servidor, mas não no cliente, revise o firewall, `IP_BIND`, DNS, rota de rede e regras institucionais.

## 18. Realizar o teste funcional

Depois do primeiro acesso:

1. confirme os dados da instituição;
2. cadastre um usuário com perfil Publicador;
3. crie um tipo de conteúdo e associe o Publicador;
4. entre com o usuário Publicador;
5. crie uma publicação com PDF de teste;
6. confirme que ela aparece na página inicial;
7. abra o PDF;
8. arquive e reative a publicação;
9. confirme que as permissões dos perfis estão corretas.

## 19. Testar a persistência

Reinicie os serviços:

```bash
docker compose --env-file .env.prod -f compose.prod.yaml restart
```

Após a reinicialização:

1. acesse novamente a aplicação;
2. faça login;
3. confirme que usuários, tipos e publicações continuam cadastrados;
4. confirme que o PDF continua disponível.

## 20. Gerar o primeiro backup

Execute:

```bash
cd /opt/intranet-crt01
./scripts/backup-producao.sh
```

O script criará um diretório em `backups/` contendo:

```text
banco.dump
uploads.tar.gz
SHA256SUMS
```

Valide o backup:

```bash
cd backups/AAAAMMDD-HHMMSS
sha256sum -c SHA256SUMS
```

Copie o backup para armazenamento externo ao servidor.

## 21. Configurar o backup periódico

A persistência em volume Docker não substitui backup. Nenhuma rotina garante perda zero: nesta rotina diária, uma falha pode exigir recuperar o último backup válido e perder as alterações posteriores. Se esse intervalo não for aceitável, a infraestrutura deve implantar backups mais frequentes ou recuperação contínua do PostgreSQL (PITR).

### 21.1. Preparar o destino e os responsáveis

Adote a seguinte rotina para o servidor offline:

1. backup diário às 23h, com uma breve indisponibilidade para manter banco e arquivos consistentes;
2. cópia para um NAS ou outro servidor da rede interna, em equipamento e disco diferentes;
3. retenção no destino externo de pelo menos 30 cópias diárias e 12 mensais, com snapshots protegidos contra exclusão pela conta da aplicação;
4. cópia semanal adicional em mídia criptografada, desconectada após a cópia e guardada em local seguro;
5. verificação diária pelo responsável técnico e teste mensal de restauração;
6. backup adicional antes de atualizações ou manutenção de volumes.

Prepare com a infraestrutura uma montagem persistente do compartilhamento externo em `/mnt/backup-intranet`. O destino deve estar montado e permitir escrita pelo administrador. Instale previamente `mountpoint`, `flock` e as ferramentas usuais utilizadas abaixo. Isso não exige internet durante a execução dos backups. Restrinja o compartilhamento aos responsáveis pelo backup e configure criptografia conforme a política institucional.

A rotina abaixo não apaga backups automaticamente. Configure a retenção no equipamento externo e acompanhe o espaço livre nos dois servidores. Não exclua a última cópia válida nem o histórico antes de confirmar uma restauração.

### 21.2. Criar a rotina automática

Crie `/usr/local/sbin/backup-intranet` com o conteúdo abaixo. Ajuste `/opt/intranet-crt01` caso a instalação use outro diretório.

```bash
sudo tee /usr/local/sbin/backup-intranet > /dev/null <<'EOF'
#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

exec 9>/run/lock/backup-intranet.lock
flock -n 9 || exit 1
cd /opt/intranet-crt01
COMPOSE=(docker compose --env-file .env.prod -f compose.prod.yaml)
EXTERNO=/mnt/backup-intranet
mountpoint -q "$EXTERNO" || { echo "ERRO: destino externo não montado" >&2; exit 1; }
DATA=$(date +%Y%m%d-%H%M%S)
LOCAL="/opt/intranet-crt01/backups/rotina-$DATA"
REMOTO="$EXTERNO/rotina-$DATA"
mkdir -p "$LOCAL"

# O backend permanece ativo para permitir a leitura dos uploads.
# O proxy e o trabalhador são parados para suspender novas gravações.
retomar() {
  resultado=$?
  trap - EXIT
  if ! "${COMPOSE[@]}" start trabalhador proxy; then
    echo "ERRO: verificar retomada da aplicação" >&2
    resultado=1
  fi
  if (( resultado != 0 )); then
    echo "ERRO: backup incompleto; consultar o log" >&2
  fi
  exit "$resultado"
}
trap retomar EXIT
"${COMPOSE[@]}" stop -t 120 proxy trabalhador
# Aguarda requisições já iniciadas antes de copiar o banco e os arquivos.
sleep 30
"${COMPOSE[@]}" exec -T postgres sh -c \
  'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > "$LOCAL/banco.dump"
"${COMPOSE[@]}" exec -T backend tar -czf - -C /aplicacao/var/uploads . \
  > "$LOCAL/uploads.tar.gz"
# Usa caminhos relativos: os checksums funcionam também no outro equipamento.
(cd "$LOCAL" && sha256sum banco.dump uploads.tar.gz > SHA256SUMS)
(cd "$LOCAL" && sha256sum -c SHA256SUMS)
tar -tzf "$LOCAL/uploads.tar.gz" > /dev/null
"${COMPOSE[@]}" exec -T postgres pg_restore --list < "$LOCAL/banco.dump" > /dev/null
"${COMPOSE[@]}" start trabalhador proxy
trap - EXIT

# O marcador só é criado após conferir os arquivos no destino externo.
mkdir "$REMOTO"
cp "$LOCAL/banco.dump" "$LOCAL/uploads.tar.gz" "$LOCAL/SHA256SUMS" "$REMOTO/"
(cd "$REMOTO" && sha256sum -c SHA256SUMS)
touch "$REMOTO/CONCLUIDO"
echo "Backup externo concluído: $REMOTO"
EOF
sudo chmod 700 /usr/local/sbin/backup-intranet
```

Execute apenas uma rotina de backup por vez, inclusive nas execuções manuais. Durante essa janela, não rode importações, comandos administrativos que alterem dados nem atualizações. Confirme que não há operações longas ainda em execução no backend; a espera de 30 segundos não garante o término de uma operação arbitrariamente longa.

Faça uma execução manual em horário de manutenção:

```bash
sudo /usr/local/sbin/backup-intranet
```

Confirme a mensagem de conclusão, o arquivo `CONCLUIDO` no equipamento externo e o retorno do acesso à aplicação. Diretórios sem esse marcador devem ser tratados como cópias incompletas. Se houver interrupção de energia durante o processo, confira e inicie novamente `trabalhador` e `proxy` após recuperar o servidor.

### 21.3. Agendar e acompanhar

Edite o agendamento do administrador com `sudo crontab -e` e adicione:

```cron
0 23 * * * /usr/local/sbin/backup-intranet >> /var/log/backup-intranet.log 2>&1
```

Configure a rotação desse log no servidor. Toda manhã, o responsável deve verificar:

1. se existe uma cópia externa com `CONCLUIDO` das últimas 24 horas;
2. se os checksums dessa cópia passam;
3. se o log registra erros e se a aplicação está acessível;
4. se há espaço disponível para os próximos backups.

Configure o monitoramento interno para alertar quando não houver backup concluído há mais de 26 horas. Um agendamento cadastrado ou um arquivo de dump existente, isoladamente, não comprovam sucesso. Em caso de falha, corrija a causa e execute novamente a rotina.

Guarde também, em cofre institucional separado, as configurações de produção e o pacote offline da versão correspondente. O backup do banco e dos uploads não inclui `.env.prod` nem as imagens da aplicação.

### 21.4. Testar a restauração mensalmente

Use outro computador ou uma máquina virtual isolada, sem acesso de escrita aos volumes de produção:

1. instale o pacote offline correspondente e configure um ambiente de teste;
2. copie do equipamento externo um backup com `CONCLUIDO`;
3. entre na pasta copiada e execute `sha256sum -c SHA256SUMS`;
4. no ambiente de teste, pare `proxy` e `trabalhador`, mantendo `postgres` e `backend` ativos;
5. restaure o banco e os uploads com os comandos abaixo;
6. inicie novamente `trabalhador` e `proxy`, faça login e confira usuários, publicações e a abertura de PDFs;
7. registre data, backup utilizado, resultado e tempo de recuperação.

**Execute estes comandos exclusivamente no ambiente de teste descartável. A restauração substitui os dados do banco de destino.** Ajuste `COPIA` para a pasta do backup e execute no diretório da instalação de teste:

```bash
COPIA=/caminho/do/backup/rotina-AAAAMMDD-HHMMSS
COMPOSE=(docker compose --env-file .env.prod -f compose.prod.yaml)
"${COMPOSE[@]}" stop proxy trabalhador
"${COMPOSE[@]}" exec -T postgres sh -c \
  'pg_restore --exit-on-error --clean --if-exists --no-owner --no-privileges -U "$POSTGRES_USER" -d "$POSTGRES_DB"' \
  < "$COPIA/banco.dump"
"${COMPOSE[@]}" exec -T backend tar -xzf - -C /aplicacao/var/uploads \
  < "$COPIA/uploads.tar.gz"
"${COMPOSE[@]}" start trabalhador proxy
```

Use um volume de uploads inicialmente vazio no teste. Se qualquer comando falhar, interrompa o procedimento e investigue antes de prosseguir. Checksums e listagem do dump detectam alguns problemas, mas somente uma restauração com validação funcional demonstra a utilidade do backup.

### 21.5. Preservar os volumes nas manutenções

Não execute `docker compose down -v`, `docker volume rm` nem limpezas de volumes no servidor de produção. Mantenha o nome do projeto `intranet-crt01-prod`: mudar esse nome pode fazer a aplicação utilizar outro volume e apresentar um banco vazio.

Antes de atualizar ou reinstalar o ambiente Docker, confirme uma cópia externa recente e um teste de restauração bem-sucedido. Se o banco aparecer vazio inesperadamente, interrompa novas gravações e examine os volumes existentes antes de inicializar ou remover qualquer volume.

## 22. Configurar DNS e HTTPS

Solicite o registro DNS interno apontando para o IP fixo do servidor. Para HTTPS:

1. instale o certificado emitido ou aprovado pelo órgão no proxy reverso do host;
2. configure o proxy para encaminhar ao endereço `127.0.0.1:8080`;
3. altere `IP_BIND` para `127.0.0.1`;
4. recrie os contêineres;
5. valide o certificado em outro computador da rede.

Não coloque chaves privadas de certificados no Git ou dentro da imagem da aplicação.

## 23. Registrar a entrega

Ao finalizar, registre:

1. data da instalação;
2. versão instalada;
3. IP, porta e nome DNS;
4. localização do projeto no servidor;
5. localização e rotina dos backups;
6. responsáveis técnicos;
7. resultado dos testes;
8. pendências identificadas.

Entregue a senha inicial do administrador por canal separado do manual e solicite sua troca conforme a política institucional.

## 24. Comandos operacionais essenciais

Estado dos serviços:

```bash
docker compose --env-file .env.prod -f compose.prod.yaml ps
```

Logs:

```bash
docker compose --env-file .env.prod -f compose.prod.yaml logs --tail=200
```

Parar e iniciar preservando dados:

```bash
docker compose --env-file .env.prod -f compose.prod.yaml stop
docker compose --env-file .env.prod -f compose.prod.yaml start
```

Nunca execute o comando abaixo em produção:

```text
docker compose down -v
```

A opção `-v` remove os volumes e pode apagar definitivamente o banco e os PDFs.

## 25. Checklist final

1. Docker ativo e configurado para iniciar com o Linux.
2. Arquivos instalados em `/opt/intranet-crt01`.
3. `.env.prod` preenchido, protegido e fora do Git.
4. Segredos guardados no cofre institucional.
5. Todos os serviços ativos.
6. Home e health check respondendo com HTTP 200.
7. Acesso confirmado em outra máquina da intranet.
8. Administrador inicial criado.
9. Teste com Publicador e PDF concluído.
10. Persistência confirmada após reinicialização.
11. Primeiro backup gerado e validado.
12. Backup externo e periódico configurado.
13. Firewall, DNS e HTTPS revisados.
14. Documentação e responsáveis registrados.
