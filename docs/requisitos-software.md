# Intranet - CRT-01

## Especificação inicial de requisitos

**Status:** em refinamento, aguardando aprovação  
**Objetivo:** registrar o escopo inicial do sistema e servir de base para o detalhamento dos requisitos funcionais.

## 1. Visão geral

A Intranet - CRT-01 será uma plataforma de comunicação institucional para publicação e consulta de conteúdos internos. Inicialmente, a aplicação atenderá uma instituição com escritórios em três estados, mas sua arquitetura deverá permitir:

- inclusão de novos estados e unidades organizacionais;
- utilização por outras instituições;
- criação de novos tipos de conteúdo sem alteração no código-fonte;
- definição de responsáveis distintos para a publicação de cada tipo de conteúdo.

A solução será desenvolvida com Symfony no backend, PostgreSQL como banco de dados e Vue 3 com PrimeVue no frontend.

## 2. Objetivos do sistema

- Centralizar a comunicação institucional.
- Organizar as publicações por tipos de conteúdo configuráveis.
- Delegar a responsabilidade editorial por tipo de conteúdo.
- Disponibilizar conteúdos textuais e documentos associados.
- Controlar o acesso e as ações conforme o perfil do usuário.
- Suportar crescimento geográfico e adoção por múltiplas instituições.

## 3. Escopo inicial

O sistema deverá contemplar:

- gestão de instituições;
- gestão dos estados ou unidades territoriais atendidas por cada instituição;
- gestão de usuários e perfis de acesso;
- cadastro de tipos de conteúdo;
- atribuição de usuário responsável a cada tipo de conteúdo;
- criação, edição, publicação, consulta e retirada de publicações;
- publicação de título, texto e link para documento PDF ou DOC/DOCX;
- identificação da instituição e do alcance territorial de cada publicação;
- registro das principais operações administrativas e editoriais.

## 4. Atores

### 4.1 Administrador

Usuário com permissão para configurar a estrutura do sistema, cadastrar tipos de conteúdo, definir responsáveis editoriais, administrar usuários e controlar instituições e estados.

### 4.2 Responsável por conteúdo

Usuário designado pelo administrador para gerenciar as publicações de um ou mais tipos de conteúdo.

### 4.3 Usuário leitor

Usuário autenticado que consulta os conteúdos disponibilizados para sua instituição e, quando aplicável, para seu estado ou unidade.

## 5. Requisitos funcionais preliminares

Estes requisitos serão revisados e complementados após o recebimento da relação detalhada de requisitos funcionais.

### RF-001 — Autenticar usuários

O sistema deverá permitir a autenticação de usuários e impedir o acesso de contas inativas.

### RF-002 — Gerenciar instituições

O administrador deverá poder cadastrar, consultar, editar, ativar e inativar instituições.

### RF-003 — Gerenciar estados ou unidades territoriais

O administrador deverá poder vincular estados ou unidades territoriais a uma instituição, sem limitação fixa de quantidade.

### RF-004 — Gerenciar usuários

O administrador deverá poder cadastrar, consultar, editar, ativar e inativar usuários, vinculando-os a uma instituição e, quando aplicável, a um estado ou unidade.

### RF-005 — Gerenciar perfis e permissões

O sistema deverá controlar as funcionalidades disponíveis conforme o perfil e as permissões atribuídas ao usuário.

### RF-006 — Gerenciar tipos de conteúdo

O administrador deverá poder cadastrar, consultar, editar, ativar e inativar tipos de conteúdo.

### RF-007 — Definir responsável pelo tipo de conteúdo

Ao cadastrar ou editar um tipo de conteúdo, o administrador deverá atribuir um usuário ativo como responsável pelas respectivas publicações.

### RF-008 — Gerenciar publicações

O usuário autorizado deverá poder criar, consultar, editar e retirar publicações pertencentes aos tipos de conteúdo pelos quais é responsável.

### RF-009 — Informar dados da publicação

Cada publicação deverá admitir, conforme as regras definidas para o tipo de conteúdo:

- título;
- texto;
- link para arquivo PDF, DOC ou DOCX.

As regras de obrigatoriedade de cada campo deverão ser detalhadas nos requisitos funcionais definitivos.

### RF-010 — Controlar o estado da publicação

O sistema deverá diferenciar, no mínimo, publicações em rascunho, publicadas e retiradas de publicação.

### RF-011 — Definir o público da publicação

O sistema deverá permitir que uma publicação seja destinada a toda a instituição ou a estados/unidades específicos vinculados a ela.

### RF-012 — Consultar publicações

O usuário leitor deverá visualizar somente publicações ativas destinadas à sua instituição e ao seu âmbito territorial.

### RF-013 — Pesquisar e filtrar publicações

O sistema deverá permitir pesquisa e filtros por, no mínimo, tipo de conteúdo, título, período de publicação e estado/unidade.

### RF-014 — Registrar auditoria

O sistema deverá registrar o usuário e a data das operações relevantes, incluindo criação, alteração, publicação, retirada e mudança de responsável.

## 6. Regras de negócio preliminares

- RN-001: todo tipo de conteúdo ativo deverá possuir ao menos um responsável ativo. A definição entre responsável único ou múltiplos responsáveis será confirmada no detalhamento funcional.
- RN-002: somente o administrador poderá cadastrar tipos de conteúdo e atribuir seus responsáveis.
- RN-003: o responsável somente poderá administrar publicações dos tipos de conteúdo que lhe foram atribuídos, salvo permissão administrativa superior.
- RN-004: instituições, estados, tipos de conteúdo ou usuários que possuam histórico não deverão ser excluídos fisicamente; deverão ser inativados.
- RN-005: uma publicação não poderá ser direcionada a um estado/unidade que não pertença à mesma instituição da publicação.
- RN-006: apenas publicações no estado "publicada" poderão ser exibidas aos leitores.
- RN-007: links de documentos deverão apontar para formatos permitidos e usar endereço válido.
- RN-008: todas as entidades principais deverão registrar datas de criação e atualização e o usuário responsável pela operação, quando aplicável.
- RN-009: dados de uma instituição não deverão ser acessíveis por usuários de outra instituição, exceto por um eventual perfil de administração global explicitamente autorizado.

## 7. Requisitos não funcionais iniciais

### RNF-001 — Arquitetura e tecnologias

- Backend: Symfony, expondo API para o frontend.
- Banco de dados: PostgreSQL.
- Frontend: Vue.js com biblioteca de componentes PrimeVue.
- A API deverá possuir contratos versionados e documentação técnica.

### RNF-002 — Segurança

- O tráfego em produção deverá utilizar HTTPS.
- Senhas deverão ser armazenadas exclusivamente por meio de algoritmo de hash seguro suportado pelo Symfony.
- Toda operação protegida deverá validar autenticação, instituição e autorização no backend.
- O sistema deverá aplicar proteção contra vulnerabilidades comuns, incluindo injeção de código, XSS, CSRF quando aplicável e acesso indevido entre instituições.

### RNF-003 — Escalabilidade

A estrutura de dados não deverá possuir limitação fixa de instituições, estados, usuários, tipos de conteúdo ou publicações.

### RNF-004 — Integridade

O banco deverá empregar chaves estrangeiras, restrições e transações para preservar a consistência dos relacionamentos.

### RNF-005 — Usabilidade e responsividade

A interface deverá ser responsiva e utilizável em computadores, tablets e celulares, seguindo padrões consistentes de acessibilidade e feedback ao usuário.

### RNF-006 — Desempenho

Listagens deverão utilizar paginação, filtros executados no servidor e índices adequados no banco de dados.

### RNF-007 — Observabilidade e auditoria

Erros e eventos relevantes deverão ser registrados sem exposição de senhas, tokens ou outros dados sensíveis.

### RNF-008 — Manutenibilidade

O código deverá possuir separação clara de responsabilidades, migrações versionadas de banco, testes automatizados e configuração por ambiente.

### RNF-009 — Proteção de dados

O tratamento de dados pessoais deverá observar a LGPD, incluindo minimização de dados, controle de acesso, rastreabilidade e política de retenção.

## 8. Modelo conceitual inicial

As principais entidades previstas são:

- **Instituição:** organização proprietária dos dados e conteúdos.
- **Estado/Unidade:** área territorial ou unidade organizacional vinculada à instituição.
- **Usuário:** pessoa com acesso ao sistema.
- **Perfil/Permissão:** conjunto de autorizações do usuário.
- **Tipo de conteúdo:** classificação configurável das publicações.
- **Responsabilidade editorial:** vínculo entre tipo de conteúdo e usuário responsável.
- **Publicação:** conteúdo composto por título, texto e/ou link de documento.
- **Destinatário da publicação:** vínculo que determina se o alcance é institucional ou restrito a estados/unidades.
- **Histórico de auditoria:** registro das operações relevantes.

## 9. Premissas sujeitas a confirmação

- O sistema será uma aplicação web responsiva.
- Cada usuário pertencerá a uma instituição.
- Uma publicação pertencerá a uma única instituição.
- O vínculo de documentos será feito inicialmente por URL; o envio e armazenamento de arquivos pelo próprio sistema ainda precisa ser definido.
- PDF, DOC e DOCX serão os formatos documentais iniciais.
- O conteúdo poderá passar por rascunho antes de ser publicado.
- A solução será preparada para múltiplas instituições, mesmo que a primeira implantação atenda somente o CRT-01.

## 10. Pontos para detalhamento

Os requisitos funcionais seguintes deverão esclarecer especialmente:

- se um tipo de conteúdo poderá possuir mais de um responsável;
- se haverá fluxo de aprovação antes da publicação;
- quais campos da publicação serão obrigatórios;
- se documentos serão enviados à plataforma ou apenas referenciados por links;
- se publicações terão agendamento, expiração, destaque e ordenação;
- se haverá notificações por e-mail, aplicativo ou pela própria intranet;
- se usuários poderão pertencer a mais de um estado/unidade;
- quais perfis administrativos existirão em contexto global e institucional;
- quais relatórios e indicadores serão necessários;
- quais regras de retenção e versionamento do conteúdo serão adotadas.

## 11. Critérios gerais de aceite

- Um administrador consegue cadastrar a estrutura institucional sem depender de alteração no código.
- Um administrador consegue criar um tipo de conteúdo e atribuir seu responsável.
- O responsável consegue gerenciar somente as publicações sob sua responsabilidade.
- Uma publicação pode conter os campos e documentos previstos e ser direcionada ao público autorizado.
- Um leitor visualiza somente conteúdos publicados e destinados ao seu contexto.
- As operações relevantes permanecem rastreáveis.
- A inclusão de novas instituições e estados não exige mudança estrutural no banco ou na aplicação.



## 12. Revisão funcional vigente — versão 2

Esta seção consolida os requisitos fornecidos pelo responsável do projeto e prevalece sobre trechos anteriores em caso de conflito. Nenhum desenvolvimento deverá ser iniciado antes da aprovação integral dos requisitos.

### RF-015 — Instituições e escritórios regionais

O Admin poderá cadastrar, consultar, editar, ativar e inativar instituições e seus escritórios regionais. Cada escritório será vinculado a uma instituição e a um estado. A quantidade de instituições, escritórios e estados não será fixa.

### RF-016 — Perfis de acesso

O sistema terá somente dois perfis: **Admin** e **Publicador**. O Admin gerenciará usuários, instituições, escritórios, tipos de conteúdo e responsáveis. O Publicador poderá consultar conteúdos e gerenciar publicações apenas dos tipos que lhe forem atribuídos.

### RF-017 — Tipos de conteúdo e responsável

O Admin poderá cadastrar tipos de conteúdo dinâmicos. Cada tipo ativo deverá possuir exatamente um Publicador ativo como responsável. A troca de responsável preservará autoria e histórico anteriores. A desativação do responsável exigirá sua substituição prévia.

### RF-018 — Publicações

O Publicador poderá criar, editar e excluir logicamente publicações dos tipos sob sua responsabilidade. Cada publicação será vinculada ao tipo, autor, instituição e escritório regional do autor e terá título curto obrigatório, corpo em Rich Text e anexo opcional por upload ou link nos formatos PDF, DOC ou DOCX.

### RF-019 — Consulta, busca e anexos

Admins e Publicadores poderão consultar as publicações dentro do escopo de acesso definido. A busca oferecerá palavra-chave e filtros por instituição, estado, escritório regional, tipo e período, com paginação e ordenação. Usuários autorizados poderão visualizar ou baixar anexos, após validação de acesso pelo backend.

### RF-020 — Auditoria e exclusão lógica

O sistema preservará usuário, data e natureza das operações relevantes. Registros com histórico, incluindo publicações, não serão removidos fisicamente.

### RNF-010 — Arquitetura REST em camadas

O fluxo de dados seguirá: `Componente Vue → Service do frontend → DTO do frontend → Controller REST → Service do backend → DTO do backend → Repository → PostgreSQL`.

- Componentes Vue 3 concentrarão apresentação e interação, sem chamadas HTTP diretas.
- Services do frontend encapsularão a API.
- DTOs definirão contratos de entrada e saída.
- Controllers serão enxutos e delegarão casos de uso.
- Services do backend concentrarão autorização, validação e regras de negócio.
- Repositories encapsularão persistência e consultas.
- Entidades não serão expostas diretamente pela API.

### Decisões recomendadas aguardando aprovação

- Isolar dados por instituição; usuários verão todas as publicações de sua instituição, não de outras instituições.
- Vincular cada usuário a uma instituição e a um escritório regional.
- Manter exatamente um responsável por tipo de conteúdo.
- Usar exclusão lógica e auditoria.
- Adotar os estados rascunho, publicada e arquivada.
- Permitir ao Admin somente leitura das publicações; publicação continuará sendo responsabilidade do Publicador.

### Pontos ainda não definidos

- se o corpo Rich Text será obrigatório;
- limite do título e tamanho máximo dos anexos;
- armazenamento dos uploads;
- possibilidade de um usuário integrar mais de um escritório;
- existência de Admin global ou somente Admin por instituição;
- necessidade de aprovação editorial, agendamento, expiração e notificações.


## 13. Decisões aprovadas — versão 3

Esta seção complementa a versão 2 e prevalece em caso de conflito.

### RF-021 — Validação do conteúdo

- O título será obrigatório e terá no máximo 150 caracteres.
- O corpo em Rich Text será obrigatório.
- O anexo continuará opcional e aceitará PDF, DOC e DOCX.
- O upload terá limite padrão de 10 MB, alterável por parâmetro administrativo, sem necessidade de alterar o código-fonte.
- O backend validará extensão, MIME type, tamanho e integridade básica do arquivo; o nome fornecido pelo usuário não será utilizado como nome físico de armazenamento.

### RF-022 — Vínculo e remanejamento de usuário

Cada usuário pertencerá a exatamente uma instituição e um escritório regional por vez. O Admin poderá remanejá-lo para outro escritório da mesma instituição. O remanejamento preservará o histórico anterior e passará a valer para novos registros, sem alterar retroativamente a instituição ou o escritório registrados nas publicações existentes.

### RF-023 — Administração global

O perfil Admin poderá possuir escopo **global** ou **institucional**, sem criação de um terceiro perfil:

- o Admin global poderá gerenciar todas as instituições, escritórios, usuários e configurações globais;
- o Admin institucional ficará restrito à sua instituição;
- somente um Admin global poderá conceder ou retirar o escopo global de outro Admin.

### RF-024 — Fluxo editorial

As publicações seguirão os estados:

1. **Rascunho:** editável pelo Publicador responsável e ainda invisível aos demais usuários;
2. **Aguardando aprovação:** enviada pelo Publicador para análise e bloqueada para publicação direta;
3. **Aprovada/agendada:** aprovada pelo Admin, com publicação imediata ou programada;
4. **Publicada:** disponível para consulta no escopo autorizado;
5. **Expirada:** retirada automaticamente da consulta ao atingir a data de expiração;
6. **Arquivada:** retirada manualmente, preservando conteúdo e auditoria;
7. **Rejeitada:** devolvida pelo Admin ao Publicador com justificativa obrigatória.

O Admin responsável pelo escopo da instituição aprovará ou rejeitará a publicação. O autor não poderá aprovar sua própria publicação caso também possua privilégios administrativos. Toda transição será validada no backend e registrada na auditoria.

### RF-025 — Agendamento e expiração

- A data e hora de publicação poderão ser imediatas ou futuras.
- A data e hora de expiração serão opcionais e, quando informadas, deverão ser posteriores à publicação.
- Datas serão armazenadas em UTC e exibidas no fuso horário configurado para a instituição.
- Um processamento assíncrono ativará publicações agendadas e expirará publicações vencidas.

### RF-026 — Notificações

O sistema emitirá notificações internas e, quando habilitado, por e-mail nos seguintes eventos:

- publicação enviada para aprovação;
- publicação aprovada, rejeitada ou arquivada;
- publicação agendada efetivamente publicada;
- publicação próxima da expiração ou expirada;
- atribuição ou troca do responsável por tipo de conteúdo;
- remanejamento ou desativação de usuário.

Os canais, destinatários e antecedência dos avisos de expiração serão configuráveis. Falha no envio de e-mail não deverá desfazer a operação principal e deverá ser registrada para nova tentativa.

### RNF-011 — Armazenamento seguro de anexos

Os uploads serão armazenados em bucket privado compatível com S3:

- MinIO nos ambientes locais e de homologação executados em Docker;
- serviço S3 compatível configurável no ambiente de produção;
- objetos identificados por chave não previsível, sem exposição direta do caminho físico;
- download intermediado pelo backend ou realizado por URL assinada de curta duração após autorização;
- criptografia em trânsito e, quando suportada pelo provedor, em repouso;
- metadados, hash, MIME type, tamanho e nome original registrados no PostgreSQL;
- exclusão lógica do vínculo e política separada de retenção do objeto físico;
- varredura antimalware antes de disponibilizar o arquivo para download.

### RNF-012 — Parametrização

Parâmetros operacionais, como limite de upload, canais de notificação, antecedência de expiração e fuso horário, deverão ser persistidos por escopo global ou institucional. Alterações deverão ser validadas, auditadas e aplicadas sem nova compilação ou implantação.

### RNF-013 — Docker

O projeto utilizará Docker e Docker Compose. A estrutura inicial deverá contemplar serviços separados para:

- proxy web;
- frontend Vue 3/PrimeVue;
- backend Symfony/PHP-FPM;
- worker Symfony para filas e tarefas assíncronas;
- PostgreSQL;
- Redis para filas, cache e coordenação de tarefas;
- MinIO para armazenamento S3 local;
- serviço SMTP de desenvolvimento para testes de e-mail;
- ClamAV para varredura antimalware dos anexos.

Somente os serviços que precisarem de acesso externo terão portas publicadas. Credenciais serão fornecidas por variáveis ou secrets, volumes persistentes serão definidos explicitamente e os containers executarão, sempre que possível, como usuários não privilegiados. Os ambientes de desenvolvimento e produção poderão usar arquivos Compose complementares, mantendo imagens e configurações-base consistentes.

### Decisões substituídas na versão 2

- O corpo Rich Text passa a ser obrigatório.
- O limite inicial de upload passa a ser 10 MB parametrizável.
- O usuário pertence a um único escritório por vez, com remanejamento permitido.
- Existirá Admin global, implementado como escopo do perfil Admin.
- O fluxo editorial, agendamento, expiração e notificações passam a fazer parte do escopo funcional.


### RNF-014 — OpenAPI e Swagger

A API REST deverá possuir contrato OpenAPI e documentação interativa por Swagger UI. Cada endpoint deverá informar finalidade, autenticação, parâmetros, DTOs de entrada e saída, exemplos e possíveis códigos de resposta. A documentação deverá ser atualizada junto com a implementação e validada automaticamente para reduzir divergências entre contrato e comportamento.

No ambiente de desenvolvimento, a interface será disponibilizada em `/api/documentacao` e o contrato em `/api/documentacao.json`. Em produção, o acesso à interface poderá ser desabilitado ou restrito, sem impedir a geração do contrato durante o processo de entrega.

## 14. Decisões aprovadas — versão 4

Esta seção substitui as regras anteriores de aprovação, agendamento e rejeição em caso de conflito.

### RF-027 — Publicação imediata

Toda publicação válida criada por um Publicador responsável será ativada automaticamente no estado `PUBLICADA`, sem aprovação prévia do Admin.

### RF-028 — Controle de disponibilidade

O Admin, respeitando seu escopo institucional, e o Publicador responsável pelo tipo poderão desabilitar logicamente uma publicação ativa e reativar uma publicação desabilitada. A operação preservará o conteúdo e registrará usuário, data e estados de origem e destino.

### Regras substituídas

- A fila e os endpoints de aprovação e rejeição deixam de existir.
- Rascunho, aguardando aprovação, aprovada/agendada e rejeitada deixam de ser estados do fluxo funcional.
- Agendamento e expiração automática deixam de fazer parte do fluxo vigente; registros históricos permanecem preservados.
- Notificações editoriais futuras deverão considerar criação imediata, desabilitação e reativação.
