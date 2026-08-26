# Validações de segurança

## Escopo

Esta auditoria cruza a checklist de segurança para Symfony, Vue, PrimeVue e Docker com o código-fonte, as configurações, os arquivos Docker e a documentação atual da Intranet CRT-01.

Foram realizadas inspeções estáticas e as seguintes verificações automatizadas:

- `composer audit`: nenhuma vulnerabilidade conhecida encontrada;
- `npm audit --omit=dev`: nenhuma vulnerabilidade conhecida encontrada;
- teste anônimo do endpoint de arquivos: resposta `401 Unauthorized`.

Esta avaliação representa o estado atual do repositório e do ambiente local. Controles dependentes da futura infraestrutura de produção devem ser reavaliados depois da preparação da VPS.

## Legenda

- ✅ Implementado;
- 🟡 Parcialmente implementado ou atendido por recurso equivalente;
- ❌ Ausente;
- ⚪ Dependente da infraestrutura de produção/VPS.

## Resultado por área

| Área | Situação | Avaliação |
|---|---:|---|
| 1. Arquitetura e ambientes | ❌ | Existe somente configuração de desenvolvimento, com `APP_ENV=dev`, segredo fixo e servidores de desenvolvimento. Não há Compose de homologação ou produção. |
| 2. Docker | 🟡 | Não usa `privileged` nem monta o Docker socket, utiliza imagens oficiais e alguns health checks. Faltam usuário não-root, `no-new-privileges`, `cap_drop`, limites de recursos, filesystem somente leitura e imagens próprias de produção. |
| 3. Redes Docker | 🟡 | PostgreSQL e Redis não publicam portas no host e somente o proxy publica HTTP. Entretanto, todos os serviços compartilham uma única rede Docker. |
| 4. Reverse proxy e HTTPS | ❌ | Há Nginx e limite de requisição de 11 MB, mas faltam HTTPS, HSTS, CSP, headers gerais de segurança, timeouts e ocultação da versão do servidor. |
| 5. Autenticação Symfony | 🟡 | Usa Symfony Security, verificação de usuário ativo, Argon2id com migração transparente de bcrypt, resposta genérica e limite de cinco falhas por minuto por IP+e-mail. Ainda faltam MFA, recuperação segura de senha e auditoria de login. |
| 6. Sessões, cookies e tokens | 🟡 | A autenticação usa sessão e cookie `HttpOnly`, `SameSite=Lax` e `Secure=auto`. Não usa JWT ou `localStorage`. Faltam expiração explícita e revogação das demais sessões após mudança de senha. |
| 7. Autorização e controle de acesso | 🟡 | Há Roles, `IsGranted`, regras por endpoint e validação institucional de publicações e downloads. Existe falha importante de escopo nos cadastros administrativos. |
| 8. Entrada e validação de dados | 🟡 | Usa DTOs, Symfony Validator, campos explícitos, limites e regras no backend. Campos extras são ignorados, não rejeitados. O corpo da publicação não possui tamanho máximo explícito. |
| 9. Banco de dados e Doctrine | ✅/🟡 | Usa Doctrine QueryBuilder parametrizado, migrations e usuário próprio do banco. Faltam política formal de privilégio mínimo, backup antes de migrations críticas e credenciais fortes específicas de produção. |
| 10. Vue e PrimeVue | ✅/🟡 | Não foram encontrados `v-html`, armazenamento de tokens ou segredos no frontend. Usa interpolação segura. Existe build, mas ainda não há imagem que sirva o frontend compilado em produção. |
| 11. API | 🟡 | API versionada em `/api/v1`, métodos explícitos, autenticação, paginação e limites máximos implementados. Faltam rate limiting, padrão consistente de erros e limites globais de payload. |
| 12. CSRF e XSS | ✅/🟡 | Vue escapa textos por padrão, não há conteúdo HTML rico e todas as operações mutáveis da API, inclusive login e logout, exigem token CSRF fornecido pelo backend e enviado em header pelo Vue. |
| 13. Upload de arquivos | 🟡 | PDF possui validação de extensão, MIME real, assinatura `%PDF-`, limite de 10 MB, nome aleatório, armazenamento fora de `public` e download autorizado. O ClamAV existe, mas ainda não participa do fluxo de upload. |
| 14. Secrets e credenciais | 🟡 | `.env` está ignorado pelo Git. O Compose contém `APP_SECRET` fixo de desenvolvimento e não existem Docker Secrets ou mecanismo equivalente. |
| 15. Logs e auditoria | 🟡 | Há histórico de transições editoriais, remanejamentos, responsáveis e parâmetros. Faltam logs de login, falhas, downloads, IP, ações administrativas gerais, centralização e retenção. |
| 16. Backup e recuperação | ❌ | Há apenas previsão documental. Não existem rotinas automáticas, retenção, criptografia, cópia externa ou teste de restauração. |
| 17. Dependências | ✅/🟡 | As auditorias atuais de Composer e npm não encontraram vulnerabilidades. Faltam automação recorrente e scanner de imagens. |
| 18. CI/CD e deploy | ❌ | Não existe pipeline, PHPStan, scanner de imagens, registry, versionamento de imagens ou rollback automatizado. |
| 19. Servidor Docker | ⚪ | Depende da VPS: SSH, firewall, atualizações, monitoramento, VPN, alertas e certificados. |
| 20. LGPD e dados pessoais | ❌/🟡 | Os princípios estão documentados, mas faltam inventário de dados, retenção, anonimização ou exclusão, auditoria de acesso e procedimentos operacionais. |

## Achados prioritários

### 1. Falha de isolamento administrativo — alta prioridade

Publicações, parâmetros e downloads aplicam escopo institucional. Entretanto, instituições, escritórios, usuários e tipos de conteúdo aceitam qualquer `ROLE_ADMIN`, e seus serviços não recebem o administrador autenticado para validar a instituição.

Na prática, um administrador institucional pode tentar alterar IDs manualmente e acessar ou modificar registros de outra instituição. Isso representa risco de IDOR/BOLA.

Arquivos relacionados:

- `backend/config/packages/security_intranet.yaml`;
- `backend/src/Service/Usuario/UsuarioService.php`;
- `backend/src/Service/Escritorio/EscritorioService.php`;
- `backend/src/Service/TipoConteudo/TipoConteudoService.php`;
- `backend/src/Service/Instituicao/InstituicaoService.php`.

Recomendação: aplicar Voters ou validação institucional em listagem, criação, alteração e exclusão. Operações globais devem exigir `adminGlobal`, não apenas `ROLE_ADMIN`.

### 2. Proteção CSRF — resolvido

O backend fornece token vinculado à sessão em `/api/v1/auth/csrf`. O cliente Vue envia o token no header `X-CSRF-TOKEN`, e um subscriber o valida antes do firewall em todas as operações `POST`, `PUT`, `PATCH` e `DELETE`, incluindo login e logout. Requisições ausentes ou inválidas recebem `403`.

### 3. Configuração atual não pode ser publicada — alta prioridade

O Compose atual executa:

- `APP_ENV=dev`;
- segredo previsível;
- PHP Built-in Server;
- Vite Development Server;
- código montado por bind mount;
- erros detalhados e stack traces.

O teste anônimo realizado durante a auditoria retornou a página detalhada de exceção do Symfony. Isso é aceitável no ambiente local, mas crítico se exposto publicamente.

Recomendação: criar imagens e Compose específicos de produção, executar com `APP_ENV=prod` e `APP_DEBUG=0`, desabilitar ferramentas de desenvolvimento e servir apenas conteúdo compilado.

### 4. URL externa sem validação de protocolo — média/alta prioridade

`anexoUrl` possui apenas limite de 500 caracteres. Não existe `Assert\Url` nem restrição explícita a `https://`. Um Publicador pode cadastrar protocolos ou endereços indesejados.

Arquivo relacionado: `backend/src/Dto/Publicacao/SalvarPublicacaoDto.php`.

Recomendação: aceitar somente HTTPS, permitindo HTTP apenas no ambiente local quando necessário.

### 5. PasswordHasher e Argon2id — resolvido

O cadastro e a alteração de senhas usam `UserPasswordHasherInterface` com o algoritmo `sodium`/Argon2id. O repositório implementa `PasswordUpgraderInterface`, mantendo compatibilidade com hashes bcrypt existentes e migrando-os automaticamente após autenticação bem-sucedida.

### 6. Proteção contra força bruta — resolvido parcialmente

O login limita cinco falhas por minuto para a combinação IP+e-mail e mantém um limite global adicional por IP. Respostas inválidas não revelam se o usuário existe. Ao atingir o limite, a API retorna `429 Too Many Requests` e `Retry-After`, evitando pausas bloqueantes dentro do processo PHP.

### 7. ClamAV ainda não protege os uploads

O contêiner ClamAV existe, mas o fluxo de upload não envia o PDF para análise. O armazenamento implementado atualmente usa o filesystem local, enquanto parte da documentação descreve MinIO/S3.

Arquivo relacionado: `backend/src/Service/Publicacao/PublicacaoService.php`.

Recomendação: integrar a verificação antimalware antes de efetivar a publicação e tratar falhas ou indisponibilidade do antivírus de forma segura.

## Pontos fortes atuais

- Downloads exigem autenticação e instituição correta;
- visitantes sem sessão recebem `401 Unauthorized`;
- PDFs ficam fora do diretório público;
- MIME, assinatura, tamanho e extensão são validados;
- nomes de arquivos são aleatórios;
- respostas de PDF usam cache privado, `no-store` e `X-Content-Type-Options: nosniff`;
- QueryBuilder utiliza parâmetros;
- não há `v-html`, JWT ou credenciais armazenadas no navegador;
- PostgreSQL e Redis não publicam portas externas;
- API é versionada e paginada;
- dependências PHP e JavaScript não apresentaram vulnerabilidades conhecidas na auditoria atual;
- alterações de responsáveis, remanejamentos, transições editoriais e parâmetros possuem histórico específico.

## Ordem recomendada de correção

1. Corrigir o isolamento institucional dos cadastros administrativos;
2. criar configuração e imagens específicas de produção;
3. implementar auditoria do login;
4. validar URLs externas e seus protocolos;
5. configurar HTTPS e headers de segurança no proxy;
6. integrar o ClamAV ao fluxo de upload;
7. ampliar logs e auditoria operacional;
8. criar backups, pipeline CI/CD e scanners recorrentes;
9. formalizar controles LGPD, retenção, anonimização e recuperação.

## Revisões futuras

Esta documentação deve ser revisada:

- antes da primeira publicação em homologação;
- antes da publicação em produção;
- depois de mudanças na autenticação ou autorização;
- depois de mudanças no fluxo de upload e armazenamento;
- periodicamente após auditorias de dependências e imagens;
- após qualquer incidente de segurança relevante.
