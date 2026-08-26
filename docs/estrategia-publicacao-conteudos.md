# Estratégia de publicação de conteúdos

**Definida em:** 18/08/2026  
**Decisão vigente:** publicação imediata, sem aprovação prévia.

## 1. Objetivo

Todo conteúdo válido criado por um Publicador fica disponível imediatamente para os usuários da mesma instituição. Admins e Publicadores responsáveis podem retirar o acesso sem excluir o registro e podem reativá-lo posteriormente.

## 2. Papéis

### Publicador

- Cria conteúdos somente para os tipos pelos quais é o responsável ativo.
- Toda criação válida entra diretamente como `PUBLICADA`.
- Lista as publicações dos tipos sob sua responsabilidade, inclusive as desabilitadas.
- Desabilita e reativa publicações desses tipos, mesmo quando a autoria original pertence ao responsável anterior.

### Admin institucional

- Lista as publicações da própria instituição.
- Desabilita e reativa qualquer publicação de sua instituição.
- Não cria publicações.

### Admin global

- Lista, desabilita e reativa publicações de qualquer instituição.
- Não cria publicações.

## 3. Estados vigentes

| Estado | Significado | Visível no dashboard |
|---|---|---|
| `PUBLICADA` | Conteúdo ativo e disponível | Sim |
| `ARQUIVADA` | Acesso desabilitado, com registro preservado | Não |

Fluxo permitido:

```text
criação → PUBLICADA ⇄ ARQUIVADA
```

Os estados antigos (`RASCUNHO`, `AGUARDANDO_APROVACAO`, `APROVADA_AGENDADA` e `REJEITADA`) deixam de fazer parte do fluxo funcional. A migração `Version20260818190000` converte registros antigos nesses estados para `PUBLICADA`. `EXPIRADA` permanece apenas para compatibilidade histórica.

## 4. Regras de criação

- Tipo de conteúdo ativo e pertencente à instituição do Publicador.
- Publicador autenticado deve ser o responsável atual pelo tipo.
- Título obrigatório com até 150 caracteres.
- Corpo obrigatório.
- Link para conteúdo externo opcional com até 500 caracteres.
- Arquivo PDF opcional, validado por extensão, MIME, assinatura e limite de 10 MB.
- Link externo e arquivo PDF são independentes: é obrigatório informar pelo menos um deles, e ambos podem coexistir na mesma publicação.
- Autor, instituição e escritório são obtidos exclusivamente da sessão autenticada.
- A data efetiva de publicação recebe o instante da criação.
- A criação como `PUBLICADA` é registrada na auditoria.

## 5. Desabilitação e reativação

- Desabilitar muda `PUBLICADA` para `ARQUIVADA`.
- Reativar muda `ARQUIVADA` para `PUBLICADA` e atualiza a data efetiva.
- Apenas Admins no escopo correto ou o Publicador responsável atual podem executar essas ações.
- Uma ação incompatível com o estado atual é recusada.
- Toda alteração registra usuário, instante, estado de origem e destino.
- Registros e históricos nunca são removidos fisicamente por essas operações.

## 6. Consulta e visibilidade

- O dashboard mostra exclusivamente publicações `PUBLICADA` da instituição autenticada.
- A gestão mostra ativas e desabilitadas conforme o escopo do usuário.
- O Admin global pode consultar todas as instituições.
- O escritório do autor é informação histórica e não limita a leitura dentro da instituição.
- Publicações são ordenadas pelas datas mais recentes primeiro.

## 7. API vigente

- `GET /api/v1/publicacoes`: lista conteúdos gerenciáveis pelo usuário.
- `POST /api/v1/publicacoes`: cria e publica imediatamente; exclusivo de Publicador.
- `DELETE /api/v1/publicacoes/{id}`: desabilita o acesso.
- `POST /api/v1/publicacoes/{id}/reativar`: reativa o acesso.

Os endpoints de envio, aprovação e rejeição foram removidos.

## 8. Segurança e integridade

- O backend sempre valida perfil, instituição e responsabilidade pelo tipo.
- IDs de autor, instituição ou aprovador enviados pelo cliente não são aceitos.
- A mudança de disponibilidade e a auditoria são persistidas juntas.
- Rich Text futuro deve ser sanitizado no backend.
- O download do PDF é intermediado por uma rota autenticada e exige publicação ativa no escopo institucional.
- A etapa atual armazena PDFs em volume persistente local; a evolução prevista é migrar para MinIO/S3, acrescentar hash e varredura antimalware.

## 9. Experiência de uso

- O Publicador acessa `Publicações` pelo dashboard.
- O Admin acessa `Publicações` pela área administrativa.
- A criação informa que o conteúdo será disponibilizado imediatamente.
- A listagem apresenta tipo, autor, instituição, escritório, data e situação.
- As ações contextuais são `Desabilitar` e `Reativar`.
- Na visualização, tanto o conteúdo externo quanto o PDF apresentam a ação `Visualizar` quando estiverem disponíveis.

## 10. Critérios de aceite

- Uma publicação válida aparece no dashboard imediatamente após a criação.
- Admin não consegue criar conteúdo.
- Publicador não cria ou altera conteúdo de tipo fora de sua responsabilidade.
- Admin institucional não altera conteúdo de outra instituição.
- Admin global pode gerenciar publicações de qualquer instituição.
- Desabilitar retira imediatamente o conteúdo do dashboard sem apagá-lo.
- Reativar devolve o conteúdo ao dashboard.
- Todas as mudanças de disponibilidade possuem auditoria.
- Estados e endpoints do fluxo de aprovação não são apresentados na interface.
