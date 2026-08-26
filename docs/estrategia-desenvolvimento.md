# Intranet - CRT-01

## Estratégia de desenvolvimento

**Status:** proposta para execução  
**Base:** requisitos funcionais e não funcionais registrados em `docs/requisitos-software.md`

## 1. Diretriz principal

O desenvolvimento será incremental, orientado por casos de uso e executado em fatias verticais. Cada funcionalidade será concluída em todas as camadas antes do início da próxima:

`Componente Vue → Service do frontend → DTO do frontend → Controller REST → Service do backend → DTO do backend → Repository → PostgreSQL`

Uma fatia somente será considerada concluída quando possuir validação, autorização, persistência, interface, testes e documentação da API.

## 2. Idioma e nomenclatura

O idioma oficial do domínio funcional será português do Brasil. Código de infraestrutura, integrações, variáveis auxiliares e convenções técnicas poderão utilizar inglês quando esse for o padrão natural do framework, biblioteca ou ecossistema.

### 2.1 Código

- classes, componentes, variáveis e métodos ligados ao domínio funcional serão nomeados em português: `Instituicao`, `EscritorioRegional`, `PublicacaoService`, `instituicaoAtual`, `buscarPublicacoes`;
- elementos puramente técnicos poderão seguir o inglês convencional: `HealthController`, `HealthService`, `request`, `response`, `payload`, `loading`;
- constantes em `SCREAMING_SNAKE_CASE`: `TAMANHO_MAXIMO_ANEXO`;
- rotas e arquivos, quando aplicável, em `kebab-case`: `/api/v1/tipos-conteudo`;
- campos JSON em `camelCase`: `escritorioRegionalId`, `dataExpiracao`;
- valores de enum em português e caixa alta: `RASCUNHO`, `AGUARDANDO_APROVACAO`, `PUBLICADA`;
- nomes sem acentos ou cedilha em identificadores técnicos.

### 2.2 Banco de dados

- tabelas e colunas em português, no plural e em `snake_case`;
- chaves primárias: `id`;
- chaves estrangeiras: `<entidade>_id`, como `instituicao_id`;
- datas de controle: `criado_em`, `atualizado_em`, `excluido_em`;
- indicadores booleanos com sentido afirmativo: `ativo`, `administrador_global`;
- índices: `idx_<tabela>_<colunas>`;
- restrições únicas: `unq_<tabela>_<colunas>`;
- chaves estrangeiras: `fk_<tabela>_<referencia>`;
- tabelas associativas nomeadas pelo relacionamento, como `tipos_conteudo_responsaveis`, caso venham a ser necessárias.

Tabelas iniciais previstas:

- `instituicoes`;
- `estados`;
- `escritorios_regionais`;
- `usuarios`;
- `tipos_conteudo`;
- `publicacoes`;
- `anexos`;
- `historicos_publicacao`;
- `notificacoes`;
- `tentativas_notificacao`;
- `parametros_sistema`;
- `registros_auditoria`.

### 2.3 Exceções obrigatórias

Nomes impostos por contratos externos serão preservados para manter compatibilidade, por exemplo:

- métodos exigidos por interfaces do Symfony, como `getUserIdentifier()` e `getRoles()`;
- chaves padronizadas de bibliotecas, Docker e protocolos;
- cabeçalhos HTTP, códigos de status e palavras reservadas de SQL;
- nomes de pacotes de terceiros e variáveis obrigatórias das imagens Docker.

O critério de separação será semântico: funcionalidades e regras do negócio permanecem em português; infraestrutura e mecanismos técnicos podem usar inglês. Termos não serão traduzidos artificialmente quando isso reduzir clareza ou contrariar o padrão da tecnologia.

## 3. Organização do repositório

Estrutura proposta:

```text
intranet-crt01/
├── backend/                 # Symfony e API REST
├── frontend/                # Vue 3 e PrimeVue
├── docker/                  # Dockerfiles e configurações dos serviços
├── docs/                    # requisitos, decisões e contratos
├── compose.yaml
├── compose.desenvolvimento.yaml
├── .env.exemplo
└── README.md
```

O backend e o frontend permanecerão no mesmo repositório para simplificar versionamento dos contratos, ambiente Docker e entrega inicial.

## 4. Arquitetura

### 4.1 Backend Symfony

Cada módulo terá separação entre:

- `Controller`: protocolo HTTP, leitura da requisição e formatação da resposta;
- `DTO`: contratos de entrada, saída, filtros e paginação;
- `Service`: casos de uso, transações, autorização e regras de negócio;
- `Repository`: consultas e persistência com Doctrine;
- `Entity`: estado persistido e invariantes simples;
- `Voter`: autorização contextual por instituição, perfil e responsabilidade editorial;
- `Message` e `MessageHandler`: tarefas assíncronas;
- `Event` e assinantes: efeitos secundários desacoplados, especialmente auditoria e notificações.

Controllers não acessarão repositories diretamente. Entidades Doctrine não serão serializadas diretamente nas respostas da API.

### 4.2 Frontend Vue 3

O frontend utilizará Composition API e TypeScript. Cada módulo conterá:

- páginas e componentes visuais;
- composables para estado de tela reutilizável;
- services para chamadas HTTP;
- DTOs e mapeadores para contratos da API;
- validações de formulário;
- testes unitários e de componentes.

Componentes não chamarão o cliente HTTP diretamente e não implementarão regras de autorização como única camada de proteção.

### 4.3 API REST

- prefixo versionado: `/api/v1`;
- recursos e campos em português;
- autenticação protegida e autorização sempre validada no backend;
- paginação, ordenação e filtros padronizados;
- respostas de erro no padrão Problem Details, com mensagens de domínio em português;
- contrato OpenAPI atualizado na mesma entrega do endpoint;
- documentação interativa com Swagger UI, gerada a partir do contrato da API;
- rota de desenvolvimento sugerida: `/api/documentacao` para a interface e `/api/documentacao.json` para o contrato OpenAPI;
- exemplos de requisição, resposta, paginação, filtros, autenticação e erros documentados em cada operação;
- operações que alterem estado editorial expressas por ações claras, como `POST /api/v1/publicacoes/{id}/enviar-aprovacao`.

### 4.4 Multi-instituição

- toda entidade institucional terá vínculo explícito com `instituicoes`;
- consultas institucionais aplicarão o escopo no backend e no repository;
- Admin global poderá selecionar o contexto institucional;
- Admin institucional e Publicador não poderão atravessar o limite da instituição;
- testes automatizados deverão tentar acesso cruzado para evitar vazamento de dados.

## 5. Infraestrutura Docker

Serviços planejados:

- `proxy`: Nginx;
- `frontend`: Vue 3/Vite;
- `backend`: Symfony/PHP-FPM;
- `trabalhador`: Symfony Messenger;
- `postgres`: PostgreSQL;
- `redis`: filas e cache;
- `minio`: armazenamento S3 local;
- `correio`: servidor SMTP de desenvolvimento;
- `antivirus`: ClamAV.

Serão utilizados healthchecks, volumes nomeados, rede interna, usuários não privilegiados quando suportados e configuração por ambiente. Segredos reais não serão versionados.

## 6. Etapas de desenvolvimento

### Etapa 0 — Fundação e governança

Entregas:

- inicialização do Git;
- arquivos básicos do repositório;
- Docker Compose e healthchecks;
- projetos Symfony e Vue 3;
- OpenAPI e Swagger UI integrados ao Symfony;
- lint, análise estática, testes e build;
- convenções, decisões arquiteturais e modelo de contribuição;
- endpoint técnico de health check integrado ao ambiente Docker e documentado no Swagger.

Critério de saída: todos os containers essenciais iniciam e frontend, backend e PostgreSQL comunicam-se corretamente.

### Etapa 1 — Autenticação, autorização e escopo institucional

Entregas:

- usuários Admin e Publicador;
- login e renovação segura da sessão/token;
- Admin global e Admin institucional;
- voters e isolamento entre instituições;
- auditoria inicial;
- proteção das rotas Vue.

Critério de saída: matriz de permissões coberta por testes, inclusive tentativas de acesso entre instituições.

### Etapa 2 — Cadastros estruturais

Ordem das fatias:

1. instituições;
2. estados;
3. escritórios regionais;
4. usuários e remanejamento.

Cada fatia terá CRUD com inativação, paginação, filtros, validação, auditoria e telas administrativas.

### Etapa 3 — Tipos de conteúdo

Entregas:

- CRUD de tipos dinâmicos;
- atribuição de exatamente um Publicador responsável;
- bloqueio de desativação do responsável sem transferência;
- histórico de mudança de responsável.

### Etapa 4 — Publicações e fluxo editorial

Entregas:

- editor Rich Text;
- título de até 150 caracteres e corpo obrigatório;
- rascunho, envio, aprovação, rejeição, publicação e arquivamento;
- agendamento e expiração;
- filtros, pesquisa, paginação e ordenação;
- histórico completo das transições.

### Etapa 5 — Anexos seguros

Entregas:

- upload de PDF, DOC e DOCX;
- limite padrão de 10 MB parametrizável;
- validação de extensão, MIME type, tamanho e hash;
- varredura pelo ClamAV;
- armazenamento privado no MinIO/S3;
- download autorizado e auditado;
- link externo validado como alternativa ao upload.

### Etapa 6 — Notificações e processamento assíncrono

Entregas:

- central interna de notificações;
- e-mails configuráveis;
- filas, repetição com espera progressiva e fila de falhas;
- publicação agendada e expiração automática;
- alertas de expiração e eventos administrativos.

### Etapa 7 — Parametrização e administração global

Entregas:

- parâmetros globais e institucionais;
- precedência entre valores globais e institucionais;
- edição auditada;
- limite de upload, fuso horário e notificações configuráveis.

### Etapa 8 — Qualidade, segurança e entrega

Entregas:

- revisão de acessibilidade e responsividade;
- testes de segurança e isolamento institucional;
- testes de carga das listagens e downloads;
- backup e restauração de PostgreSQL e objetos;
- observabilidade, logs e métricas;
- documentação de operação e implantação;
- homologação orientada pelos critérios de aceite.

## 7. Estratégia de testes

- testes unitários para regras e transições de estado;
- testes de integração para repositories, banco, filas e armazenamento;
- testes funcionais da API para autenticação, validação e autorização;
- testes de componentes Vue para formulários e estados de interface;
- testes ponta a ponta dos fluxos críticos;
- teste obrigatório de isolamento entre instituições em todo recurso institucional;
- análise estática e verificação de tipos executadas continuamente.

Ambientes de teste não dependerão de serviços de produção.

## 8. Controle de versão e entregas

- branch principal protegida e sempre executável;
- branches curtas por funcionalidade ou correção;
- commits pequenos, em português e relacionados a uma única intenção;
- integração frequente para reduzir divergências;
- migrations acompanhando a entrega que altera o modelo;
- nenhuma migration já compartilhada será reescrita; correções serão feitas em nova migration;
- versões marcadas após cada marco homologado.

## 9. Definição de pronto

Uma tarefa estará pronta somente quando:

- atender aos critérios de aceite;
- respeitar a separação de idioma entre domínio funcional e infraestrutura técnica;
- possuir autorização no backend;
- possuir testes proporcionais ao risco;
- passar por lint, análise estática, testes e build;
- possuir migration e índices quando necessários;
- atualizar OpenAPI e documentação relevante;
- exibir o endpoint corretamente no Swagger UI, com parâmetros, respostas e requisitos de autenticação;
- tratar carregamento, sucesso, vazio e erro na interface;
- não expor segredos nem dados de outra instituição;
- estar executável pelo ambiente Docker documentado.

## 10. Primeira entrega recomendada

A primeira entrega será exclusivamente a fundação técnica da Etapa 0. Ela não implementará ainda os cadastros do negócio. Seu objetivo será provar o ambiente e os padrões com:

- repositório Git;
- Compose;
- Symfony;
- Vue 3, TypeScript e PrimeVue;
- PostgreSQL;
- endpoint `/api/v1/health`;
- Swagger UI e contrato OpenAPI contendo o endpoint técnico de health check;
- tela simples que consome o endpoint por meio de service e DTO;
- verificações automatizadas de backend e frontend.

Depois dessa validação, a primeira fatia funcional será autenticação e isolamento institucional.
