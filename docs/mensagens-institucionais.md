# Mensagens institucionais

Na Administração, abra **Mensagens aos usuários**. Cadastre título, texto, instituição, início, fim e obrigatoriedade. Os horários seguem o fuso do navegador e são enviados com fuso explícito à API. Admins institucionais gerenciam sua instituição; Admins globais podem selecionar qualquer instituição ativa. A instituição de uma mensagem existente não pode mudar.

Todos os perfis recebem avisos vigentes de sua instituição antes de acessar os módulos, em ordem de início e identificador. O backend bloqueia as demais operações com HTTP 423 e código `MENSAGEM_PENDENTE`; autenticação, saída e resposta às mensagens continuam disponíveis.

O contador de 30 segundos começa na apresentação e é validado no servidor. Recarregar preserva o contador da mesma versão na sessão. Editar durante a leitura exige nova apresentação. Após o prazo, o usuário marca **Li e estou ciente** e continua. Mensagens opcionais oferecem **Agora não**, dispensando a versão atual na sessão; voltam a aparecer em novo login.

O aceite é único por usuário e mensagem e guarda uma cópia do conteúdo. Editar e renovar preservam aceites. Para exigir nova ciência, crie outra mensagem. Excluir retira o aviso da fila sem apagar ciências. A ação **Ciências** mostra usuários, datas e conteúdo aceito, inclusive após exclusão.

## API

Prefixo `/api/v1/mensagens`, sessão autenticada e proteção CSRF nas escritas.

| Método e sufixo | Uso |
| --- | --- |
| `GET` sem sufixo | Listagem administrativa; `pagina` e `busca`, páginas de 20 itens |
| `POST` sem sufixo | Criação administrativa |
| `PUT /{id}` | Edição ou renovação administrativa |
| `DELETE /{id}` | Exclusão lógica administrativa |
| `GET /{id}/ciencias` | Histórico administrativo; parâmetro `pagina` |
| `GET /pendente` | Próxima mensagem ou `mensagem: null` |
| `POST /{id}/apresentar` | Conteúdo e segundos restantes |
| `POST /{id}/responder` | `aceitar` booleano e `versao` inteira; resposta 204 |

Criação e edição recebem `instituicaoId`, `titulo` (até 150 caracteres), `texto` (até 20.000), `inicioEm`, `fimEm` (ISO 8601 com fuso) e `obrigatoria` booleano. Edição exige `versao` para impedir sobrescrita concorrente. O fim deve ser posterior ao início.

Respostas: 201 na criação; 204 na exclusão/resposta; 400 para dados inválidos; 403 para falta de permissão ou dispensa obrigatória; 404 para mensagem indisponível no escopo; 409 para versão, fila, vigência ou tempo de leitura incompatíveis.

## Validação

Execute no ambiente Docker de desenvolvimento:

```bash
docker compose exec -T backend sh -c 'MENSAGENS_TEST_DATABASE_URL="$DATABASE_URL" php vendor/bin/phpunit'
docker compose exec -T frontend npm run testar
docker compose exec -T frontend npm run verificar-tipos
docker compose exec -T frontend npm run build
docker compose exec -T backend php bin/console doctrine:schema:validate
```

Os testes de mensagens criam e removem schemas isolados no banco configurado, exigindo permissão para criação de schemas. Sem `MENSAGENS_TEST_DATABASE_URL`, esses 18 testes são ignorados.

Para conferir visualmente, crie um aviso como Admin e entre como Leitor da mesma instituição. Confira a espera e o checkbox, consulte a ciência, edite e verifique a preservação do conteúdo aceito. Teste também a dispensa opcional seguida de novo login e o isolamento de usuários de outra instituição. Avisos futuros, encerrados e excluídos não devem aparecer.
