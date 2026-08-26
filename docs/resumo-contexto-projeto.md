# Intranet CRT-01 — resumo para retomada

**Atualizado em:** 17/08/2026  
**Objetivo:** registrar o estado funcional e técnico do projeto para retomadas futuras.

## 1. Visão geral

A Intranet CRT-01 é uma plataforma de comunicação institucional multi-instituição e multi-escritório. Usuários autenticados consultam os conteúdos publicados de sua instituição. Administradores mantêm os cadastros e o fluxo editorial; Publicadores criam publicações somente para os tipos de conteúdo pelos quais são responsáveis.

As regras completas estão em [requisitos-software.md](./requisitos-software.md) e a estratégia inicial em [estrategia-desenvolvimento.md](./estrategia-desenvolvimento.md).

As instruções para iniciar e diagnosticar o ambiente local estão em [guia-ambiente-local.md](./guia-ambiente-local.md).

A estratégia editorial e o ciclo de vida das publicações estão em [estrategia-publicacao-conteudos.md](./estrategia-publicacao-conteudos.md).

## 2. Tecnologias e arquitetura

- Backend: Symfony 7.4, PHP 8.3, Doctrine ORM e API REST.
- Banco: PostgreSQL 16.
- Frontend: Vue 3, TypeScript e PrimeVue 4.
- Autenticação: Symfony Security com sessão e cookie HTTP-only.
- Infraestrutura: Docker Compose, Nginx, Redis e serviços previstos para MinIO, Mailpit e ClamAV.
- Documentação: OpenAPI/Nelmio e Swagger UI.
- Fluxo arquitetural: `Componente Vue → service frontend → DTO frontend → controller → service backend → DTO backend → repository → PostgreSQL`.
- Nomes técnicos podem seguir padrões em inglês; termos diretamente ligados ao domínio permanecem em português.
- Tabelas e colunas de domínio usam português.

## 3. Endereços locais

- Aplicação: `http://localhost:8082`
- Swagger UI: `http://localhost:8082/api/documentacao`
- OpenAPI JSON: `http://localhost:8082/api/documentacao.json`
- Health check: `http://localhost:8082/api/v1/health`

## 4. Credenciais locais de demonstração

Estas credenciais são exclusivas do ambiente local e não devem ser usadas em produção.

| Perfil | E-mail | Senha |
|---|---|---|
| Admin global | `admin@crt01.local` | `CRT01@Admin#2026` |
| Publicador | `publicador@crt01.local` | `CRT01@Publica#2026` |

Os dois usuários pertencem ao CRT-01 e ao escritório regional sede em Brasília.

## 5. Funcionalidades implementadas

### Autenticação e autorização

- Login e logout por sessão.
- Bloqueio de usuários inativos.
- Separação dos perfis `ADMIN` e `PUBLICADOR`.
- Admin global e Admin institucional pertencem ao mesmo perfil, diferenciados por escopo.
- Endpoints administrativos protegidos com `ROLE_ADMIN`.
- Criação e envio de publicações protegidos com `ROLE_PUBLICADOR`.
- Dashboard disponível para qualquer usuário autenticado (`ROLE_USER`).

### Área administrativa

CRUDs com listagem, pesquisa, paginação, criação/edição em dialog e inativação lógica:

- Instituições.
- Escritórios regionais.
- Usuários.
- Tipos de conteúdo.

Regras adicionais:

- Cada usuário pertence a uma instituição e a um escritório.
- Remanejamento só pode ocorrer entre escritórios da mesma instituição e gera histórico.
- Cada tipo ativo possui um Publicador ativo responsável da mesma instituição.
- Trocas de responsável geram histórico.
- Não é possível inativar ou alterar o perfil de um Publicador ainda responsável por tipos ativos.

### Dashboard comum

- É a página inicial após o login para Admin e Publicador.
- Todo o conteúdo do dashboard é visível por todos os usuários autenticados da mesma instituição.
- Exibe todos os tipos ativos da instituição em cards.
- Cada card possui borda com cor diferente.
- Os cards mostram somente o título do tipo, a quantidade de publicações e a opção `Ver publicações`.
- Para o Publicador responsável pelo tipo, o card também mostra `Nova publicação` e abre o formulário com o tipo previamente selecionado.
- As bordas são finas no topo e na base (`2px`) e um pouco mais largas nas laterais (`4px`).
- Os cards possuem efeito sutil ao passar o mouse, com pequeno deslocamento vertical e reforço da sombra.
- A opção `Ver publicações` abre um modal com as publicações daquele tipo.
- Somente publicações com estado `PUBLICADA` aparecem no dashboard.
- Publicações desabilitadas não são expostas.
- O Admin possui botão para alternar entre Dashboard e Administração.

### Publicação e disponibilidade

- Toda publicação válida criada pelo Publicador responsável fica `PUBLICADA` e disponível imediatamente.
- Não existe aprovação prévia pelo Admin.
- Publicador responsável e Admin no escopo correto podem desabilitar (`ARQUIVADA`) e reativar conteúdos.
- Admin global pode gerenciar publicações de qualquer instituição.
- A retirada é lógica e preserva conteúdo e histórico.
- Criação, desabilitação e reativação são auditadas.
- A tela de Publicações está disponível ao Publicador pelo dashboard e ao Admin pela área administrativa.
- Uma publicação pode conter simultaneamente um link externo e um arquivo PDF opcional de até 10 MB.
- O PDF é validado no frontend e no backend e disponibilizado por rota autenticada com a ação `Visualizar`.

### Parâmetros do sistema

- Configuração global e institucional.
- Limite de upload de 1 a 100 MB; padrão atual de 10 MB.
- Notificações internas e por e-mail.
- Pelo menos um canal deve permanecer ativo.
- Antecedência de expiração de 0 a 365 dias; padrão atual de 7 dias.
- Fuso horário IANA; padrão `America/Sao_Paulo`.
- Alterações auditadas com valores anteriores, novos valores, usuário e data.

## 6. Dados de apresentação existentes

Instituição e escritório:

- Conselho Regional dos Técnicos Industriais da 1ª Região (`CRT-01`).
- Escritório Regional - Sede, Brasília/DF.

Tipos ativos atribuídos ao Publicador de demonstração:

| Tipo | Publicadas |
|---|---:|
| Atas | 2 |
| Comunicados | 3 |
| Relatórios | 2 |
| Tutoriais | 3 |

Esses dados foram criados para apresentação do dashboard e do fluxo editorial.

## 7. Principais endpoints

### Autenticação

- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/logout`

### Cadastros administrativos

- `/api/v1/instituicoes`
- `/api/v1/escritorios`
- `/api/v1/usuarios`
- `/api/v1/tipos-conteudo`
- `/api/v1/parametros`

Os CRUDs usam `GET`, `POST`, `PUT /{id}` e, quando aplicável, `DELETE /{id}` para inativação lógica.

### Dashboard e publicação

- `GET /api/v1/dashboard/tipos-conteudo`
- `GET /api/v1/dashboard/tipos-conteudo/{id}/publicacoes`
- `GET /api/v1/publicacoes`
- `POST /api/v1/publicacoes`
- `DELETE /api/v1/publicacoes/{id}`
- `POST /api/v1/publicacoes/{id}/reativar`
- `GET /api/v1/publicacoes/{id}/arquivo`

## 8. Banco de dados e migrações

Migrações existentes em `backend/migrations`:

1. Instituições.
2. Escritórios regionais.
3. Usuários e remanejamentos.
4. Tipos de conteúdo e histórico de responsáveis.
5. Publicações e transições editoriais.
6. Parâmetros e auditoria.
7. Conversão do fluxo anterior para publicação imediata.

O schema estava sincronizado com o mapeamento Doctrine na última validação.

## 9. Layout atual

- Container central limitado a `1200px`.
- Cabeçalho alinhado à mesma grade.
- Os cabeçalhos do Dashboard, da Administração e da página legada do Publicador utilizam a logomarca `frontend/public/images/logo-crt-01-alfa.png`.
- A logomarca possui tamanho responsivo, limite de altura e alinhamento à esquerda no cabeçalho.
- Recuos responsivos de 16px em celulares e 24px em telas maiores.
- Tabelas com rolagem horizontal em telas estreitas.
- Dashboard responsivo com grade automática de cards, cores de borda distintas e efeito sutil de `hover`.
- Dialogs PrimeVue para formulários, confirmações e consulta de conteúdo.
- Painéis laterais exibem publicações e detalhes sem retirar o usuário do contexto atual.
- A gestão editorial identifica visualmente links externos e PDFs e mostra o progresso percentual do upload.

## 10. Como executar e validar

Na raiz do projeto:

```bash
docker compose up -d
docker compose ps
```

Validações principais:

```bash
docker compose exec -T backend php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec -T backend php bin/console doctrine:schema:validate
docker compose exec -T frontend npm run verificar-tipos
docker compose exec -T frontend npm run testar
docker compose exec -T frontend npm run build
```

O host não possui necessariamente as mesmas versões/dependências; prefira executar PHP e Node dentro dos containers.

## 11. Próximas etapas recomendadas

1. Evoluir a tela de publicações com edição de conteúdo ativo e confirmação antes de desabilitar.
2. Implementar editor Rich Text e sanitização segura do HTML no backend.
3. Implementar upload real de PDF/DOC/DOCX com MinIO/S3, validação MIME, hash, tamanho e ClamAV.
4. Implementar notificações para criação, desabilitação e reativação de conteúdos.
5. Implementar notificações internas e por e-mail através do Messenger/Redis.
6. Adicionar consulta e visualização do histórico de auditoria.
7. Exigir troca da senha inicial no primeiro acesso e criar recuperação de senha.
8. Ampliar testes backend, testes de componentes e testes end-to-end.
9. Melhorar a documentação OpenAPI de cada operação com schemas, exemplos e códigos de resposta.
10. Aplicar divisão de bundles/rotas no frontend; o build alerta que o bundle principal ultrapassa 500 kB.
11. Revisar CSRF, cookies seguros, secrets e configurações específicas de produção.

## 12. Observações importantes para retomada

- O conteúdo comum é sempre isolado por instituição.
- Admin não cria publicações; pode desabilitar e reativar conteúdos dentro do seu escopo.
- Publicador administra somente os tipos sob sua responsabilidade.
- Não adicionar IDs de usuário enviados pelo frontend para decisões editoriais; o backend deve sempre usar o usuário autenticado.
- O Nginx encaminha `Host` usando `$http_host` para preservar a porta local nos redirecionamentos.
- O layout e as regras principais estão concentrados em `frontend/src/style.css`.
- O diretório público existente para imagens é `frontend/public/images` (em inglês), e não `frontend/public/imagens`.
- A última alteração visual foi validada com a verificação de tipos e o build do frontend; ambos concluíram com sucesso. Permanece apenas o aviso conhecido de bundle principal acima de 500 kB.
- O repositório Git foi inicializado, porém todo o projeto ainda aparece como não rastreado e não há commit inicial. Antes de continuar por muito tempo, revisar os arquivos e criar o primeiro commit.
