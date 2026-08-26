# Resumo das funcionalidades da Intranet CRT-01

Este documento resume o escopo funcional vigente definido em `requisitos-software.md`. Em caso de divergência entre versões, foram consideradas as decisões mais recentes, especialmente a versão 4.

## 1. Finalidade do sistema

A Intranet CRT-01 é uma plataforma web responsiva destinada a centralizar a comunicação institucional. Ela permite organizar, publicar e consultar conteúdos internos, com controle de acesso por instituição, escritório regional, perfil e responsabilidade editorial.

A solução é preparada para atender várias instituições, estados e escritórios, sem limites fixos ou necessidade de alteração estrutural no código para ampliar essas unidades.

## 2. Perfis e controle de acesso

O sistema possui dois perfis:

- **Admin:** administra a estrutura institucional, os usuários, os tipos de conteúdo, os responsáveis e as configurações. Também pode controlar a disponibilidade das publicações dentro de seu escopo.
- **Publicador:** consulta conteúdos e gerencia publicações somente dos tipos de conteúdo pelos quais é responsável.

O perfil Admin pode ter dois escopos:

- **Global:** acesso administrativo a todas as instituições, escritórios, usuários e configurações globais.
- **Institucional:** atuação restrita à instituição à qual está vinculado.

Somente um Admin global pode conceder ou retirar o escopo global de outro Admin. Contas inativas não podem acessar o sistema, e toda autorização deve ser validada pelo backend.

## 3. Gestão da estrutura institucional

O Admin pode:

- cadastrar, consultar, editar, ativar e inativar instituições;
- cadastrar, consultar, editar, ativar e inativar escritórios regionais;
- vincular cada escritório a uma instituição e a um estado;
- ampliar a quantidade de instituições, estados e escritórios sem alteração no código-fonte.

Registros que possuam histórico não são excluídos fisicamente. A inativação preserva os relacionamentos e a rastreabilidade.

## 4. Gestão de usuários

O Admin pode cadastrar, consultar, editar, ativar, inativar e remanejar usuários.

Cada usuário pertence a exatamente uma instituição e a um escritório regional por vez. O remanejamento somente pode ocorrer entre escritórios da mesma instituição e passa a valer para novos registros. Publicações anteriores mantêm a instituição e o escritório registrados no momento de sua criação.

A desativação de um Publicador responsável por um tipo de conteúdo exige a indicação prévia de um substituto.

## 5. Gestão de tipos de conteúdo

O Admin pode cadastrar, consultar, editar, ativar e inativar tipos de conteúdo de forma dinâmica, sem necessidade de alterar o código-fonte.

Cada tipo de conteúdo ativo deve possuir exatamente um Publicador ativo como responsável. Esse responsável pode administrar apenas as publicações vinculadas aos tipos que lhe foram atribuídos. A troca de responsável preserva a autoria e todo o histórico anterior.

## 6. Gestão de publicações

O Publicador responsável pode:

- criar publicações;
- consultar e editar publicações dos tipos sob sua responsabilidade;
- desabilitar e reativar publicações;
- realizar exclusão lógica, preservando o histórico.

Toda publicação válida é disponibilizada imediatamente no estado `PUBLICADA`. Não existe, no fluxo vigente, aprovação prévia pelo Admin, rascunho, rejeição, agendamento ou expiração automática.

O Admin, respeitando seu escopo institucional, também pode desabilitar e reativar publicações. Essas operações registram o usuário, a data e os estados de origem e destino.

Cada publicação é vinculada a:

- tipo de conteúdo;
- autor;
- instituição do autor;
- escritório regional do autor.

O conteúdo possui:

- título obrigatório com até 150 caracteres;
- corpo obrigatório em Rich Text;
- anexo opcional, enviado por upload ou informado por link, nos formatos PDF, DOC ou DOCX.

## 7. Anexos e documentos

Uploads possuem limite inicial de 10 MB, configurável administrativamente sem recompilar ou implantar novamente o sistema.

Antes de disponibilizar um arquivo, o backend valida extensão, tipo MIME, tamanho e integridade básica, além de submetê-lo à varredura antimalware. O nome original é mantido apenas como metadado e não é usado como nome físico do objeto.

Os arquivos são armazenados em bucket privado compatível com S3 — MinIO no ambiente local e um serviço compatível configurável em produção. O acesso ocorre por intermédio do backend ou por URL assinada de curta duração, sempre após a autorização do usuário.

O sistema registra metadados como nome original, hash, tipo MIME e tamanho. A exclusão do vínculo é lógica, com retenção do arquivo físico tratada por política própria.

## 8. Consulta e pesquisa

Admins e Publicadores podem consultar publicações dentro de seu escopo de acesso. O usuário visualiza apenas conteúdos ativos da sua instituição, sem acesso aos dados de outras instituições, salvo quando possuir escopo administrativo global.

A consulta oferece:

- busca por palavra-chave;
- filtro por instituição;
- filtro por estado;
- filtro por escritório regional;
- filtro por tipo de conteúdo;
- filtro por período;
- paginação e ordenação;
- visualização ou download autorizado de anexos.

## 9. Auditoria e rastreabilidade

O sistema registra usuário, data e natureza das operações administrativas e editoriais relevantes, incluindo:

- criação e alteração de registros;
- publicação imediata;
- desabilitação e reativação de publicações;
- troca de responsável por tipo de conteúdo;
- remanejamento e inativação de usuários;
- alterações de parâmetros.

Entidades com histórico utilizam exclusão lógica. Eventos e erros são registrados sem expor senhas, tokens ou outros dados sensíveis.

## 10. Configurações administrativas

Parâmetros operacionais podem ser mantidos em escopo global ou institucional e aplicados sem alteração do código, recompilação ou nova implantação. Entre os parâmetros previstos estão:

- limite de upload;
- canais de notificação;
- antecedência de avisos;
- fuso horário institucional.

Toda alteração de configuração deve ser validada e auditada.

## 11. Notificações

O sistema prevê notificações internas e, quando habilitadas, por e-mail. Com a substituição do antigo fluxo de aprovação, as notificações editoriais devem considerar eventos como:

- criação e publicação imediata;
- desabilitação e reativação de publicação;
- atribuição ou troca de responsável;
- remanejamento ou desativação de usuário.

Falhas de e-mail não desfazem a operação principal e devem ser registradas para nova tentativa. Canais e destinatários são configuráveis.

## 12. Segurança e proteção de dados

O sistema deve:

- utilizar HTTPS em produção;
- armazenar senhas somente por hash seguro;
- validar autenticação, instituição e autorização no backend;
- impedir acesso indevido entre instituições;
- proteger contra injeção de código, XSS, CSRF quando aplicável e outras vulnerabilidades comuns;
- aplicar minimização de dados, controle de acesso, rastreabilidade e retenção compatíveis com a LGPD;
- proteger anexos por armazenamento privado, autorização de download e varredura antimalware.

## 13. Características técnicas e operacionais

A aplicação utiliza:

- frontend em Vue 3 com componentes PrimeVue;
- API REST em Symfony;
- PostgreSQL para persistência;
- Redis para filas, cache e coordenação;
- MinIO ou serviço S3 compatível para anexos;
- ClamAV para análise antimalware;
- worker Symfony para tarefas assíncronas;
- Docker e Docker Compose para execução dos serviços.

A arquitetura separa componentes, services e DTOs no frontend, e controllers, services, DTOs e repositories no backend. As listagens usam paginação e filtros no servidor. O banco emprega restrições, chaves estrangeiras, índices e transações para garantir integridade e desempenho.

A API possui contrato OpenAPI versionado e documentação interativa por Swagger, incluindo autenticação, parâmetros, exemplos, DTOs e códigos de resposta.

## 14. Capacidades de expansão

A estrutura prevista permite evoluir o sistema para cenários comuns em intranets e plataformas institucionais, tais como:

- inclusão de novas instituições, estados e escritórios regionais;
- criação de novos tipos de conteúdo e parâmetros sem alteração do código;
- novos canais de notificação e integrações com serviços corporativos;
- políticas de retenção e versionamento de publicações e anexos;
- relatórios gerenciais, indicadores de acesso e painéis administrativos;
- ampliação dos critérios de segmentação do público;
- fluxos editoriais com aprovação, rascunho, rejeição e revisão, caso voltem a ser aprovados;
- agendamento, expiração automática e destaque de conteúdos;
- novos formatos documentais e provedores de armazenamento;
- integração com diretórios de usuários, autenticação corporativa e outros sistemas institucionais.

Esses itens representam possibilidades de evolução, não funcionalidades vigentes, e dependem de definição e aprovação de requisitos específicos.

