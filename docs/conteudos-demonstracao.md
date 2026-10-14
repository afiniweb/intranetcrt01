# Conteúdos de demonstração

No ambiente local, execute com o ID de um usuário ativo da instituição que deseja apresentar:

```bash
docker compose exec backend php bin/console app:carregar-demonstracao 2
```

A carga cria 32 publicações em seis categorias identificadas com `[Demo]`. O autor informado será também o responsável pelas novas categorias. Os conteúdos aparecem publicados na instituição e no escritório desse usuário, utilizando o login existente.

A categoria Comunicados contém 12 itens para testar a paginação de 10 linhas. As demais possuem quatro itens cada. Há títulos curtos e extensos, acentos, textos com parágrafos, listas e uma publicação com link ilustrativo para example.com. Não há PDFs ou anexos fictícios que apontem para arquivos inexistentes.

O comando funciona apenas nos ambientes `dev` e `test`. Pode ser repetido: itens com o mesmo título na mesma categoria são preservados, inclusive se estiverem desabilitados. Não altera usuários nem publicações anteriores. Execute uma carga por vez. Se os títulos ou nomes das categorias forem alterados manualmente, uma nova execução poderá recriar os exemplos originais.

Para a apresentação, abra a página inicial, navegue pelas categorias `[Demo]`, busque por “integração”, ordene os títulos e confira a segunda página de Comunicados. Na gestão de publicações, os itens podem ser desabilitados com o fluxo habitual. Todos os textos são fictícios e não representam anúncios oficiais.

O catálogo pode ser ajustado em `backend/resources/conteudos-demonstracao.php` antes da primeira carga.
