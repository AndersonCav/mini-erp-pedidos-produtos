# 🧪 Checklist de Testes Manuais - Mini ERP

Documento para validar funcionamento crítico do sistema após refatoração.

## ✅ Pré-Requisitos

- [ ] Banco de dados criado e schema.sql importado
- [ ] `.env` configurado com credenciais corretas
- [ ] Servidor rodando: `php -S localhost:8000 -t public/`
- [ ] Navegador atualizado (Chrome, Firefox, Edge, Safari)
- [ ] Email configurado (testar notificações)
- [ ] Token webhook gerado e configurado

---

## 🛒 TESTE 1: Cadastro de Produto

### Cenário 1.1: Criar produto simples (sem variações)

**Passos:**
1. Acesse `http://localhost:8000?rota=produto_form`
2. Preencha:
   - Nome: "Notebook Dell"
   - Preço: "2500.00"
   - Estoque: "5"
3. Clique "Salvar Produto"

**Resultado Esperado:**
- ✅ Redireciona para lista de produtos
- ✅ Mensagem "Produto salvo com sucesso"
- ✅ Produto aparece na lista

---

### Cenário 1.2: Criar produto com variações

**Passos:**
1. Acesse `http://localhost:8000?rota=produto_form`
2. Preencha:
   - Nome: "T-Shirt"
   - Preço: "49.90"
   - Variações: "Tamanho P" (estoque 10), "Tamanho M" (estoque 20), "Tamanho G" (estoque 15)
3. Clique "Salvar Produto"

**Resultado Esperado:**
- ✅ Produto criado com 3 variações
- ✅ Estoques associados corretamente
- ✅ Listagem mostra produto e variações

---

### Cenário 1.3: Upload de imagem

**Passos:**
1. Ao criar/editar produto, selecione uma imagem (PNG, JPG maxo 5MB)
2. Salve o produto

**Resultado Esperado:**
- ✅ Arquivo salvo em `public/uploads/`
- ✅ Nome do arquivo gerado (img_xxxxx.ext)
- ✅ Imagem exibida na listagem

---

## 📝 TESTE 2: Edição e Atualização

### Cenário 2.1: Editar produto existente

**Passos:**
1. Listar produtos
2. Clique "Editar" em um produto
3. Modifique: Nome, Preço, Variações
4. Salve

**Resultado Esperado:**
- ✅ Dados atualizados no banco
- ✅ Variações antigas removidas
- ✅ Novas variações criadas
- ✅ Mensagem de sucesso

---

### Cenário 2.2: Remover variação

**Passos:**
1. Editar produto com variações
2. Remova uma variação
3. Salve

**Resultado Esperado:**
- ✅ Variação deletada do banco
- ✅ Estoque associado removido
- ✅ Sem erro de foreign key

---

## 🗑️ TESTE 3: Deleção

### Cenário 3.1: Deletar produto

**Passos:**
1. Na listagem, clique "Deletar" em um produto
2. Confirme (ou use tela de confirmação)

**Resultado Esperado:**
- ✅ Produto removido
- ✅ Variações removidas (cascata)
- ✅ Estoques removidos (cascata)
- ✅ Imagem deletada do servidor

---

## 🛍️ TESTE 4: Carrinho

### Cenário 4.1: Adicionar ao carrinho

**Passos:**
1. Acesse página de produtos
2. Clique "Adicionar ao Carrinho" em um produto simples
3. Defina quantidade: 3
4. Confirme

**Resultado Esperado:**
- ✅ Item adicionado ao carrinho
- ✅ Sessão salva
- ✅ Carrinho exibe quantidade

---

### Cenário 4.2: Adicionar variação ao carrinho

**Passos:**
1. Acesse produto com variações
2. Selecione variação (ex: "Tamanho M")
3. Quantidade: 2
4. "Adicionar ao Carrinho"

**Resultado Esperado:**
- ✅ Variação registrada corretamente
- ✅ Chave do carrinho: `{produto_id}:{variacao_id}`
- ✅ Quantidade correta no carrinho

---

### Cenário 4.3: Atualizar quantidade no carrinho

**Passos:**
1. No carrinho, modifique quantidade de um item
2. Clique "Atualizar"

**Resultado Esperado:**
- ✅ Quantidade atualizada
- ✅ Subtotal recalculado
- ✅ Frete recalculado

---

### Cenário 4.4: Remover item do carrinho

**Passos:**
1. No carrinho, clique "Remover" em um item

**Resultado Esperado:**
- ✅ Item removido
- ✅ Totais atualizados
- ✅ Sem erro se carrinho fica vazio

---

### Cenário 4.5: Limpar carrinho

**Passos:**
1. No carrinho, clique "Limpar Carrinho"

**Resultado Esperado:**
- ✅ Todos os itens removidos
- ✅ Carrinho vazio
- ✅ Sessão climppa

---

## 💵 TESTE 5: Cálculo de Frete

### Cenários de Frete

| Subtotal | Frete | Status |
|----------|-------|--------|
| R$ 30,00 | R$ 20,00 | ❌ Não Testado |
| R$ 100,00 | R$ 15,00 | ❌ Não Testado |
| R$ 250,00 | R$ 0,00 | ❌ Não Testado |

**Passos:**
1. Adicione produtos ao carrinho totalizando cada valor acima
2. Verifique frete exibido

**Resultado Esperado:**
- ✅ Frete calculado corretamente
- ✅ Valor exibido no carrinho
- ✅ Incluído no total final

---

## 🎟️ TESTE 6: Cupons

### Cenário 6.1: Crear cupom

**Passos:**
1. Acesse `?rota=cupons` (criar cupom)
2. Preencha:
   - Código: "PROMO10"
   - Desconto: "50.00"
   - Subtotal mínimo: "100.00"
   - Validade: data futura
3. Salve

**Resultado Esperado:**
- ✅ Cupom criado no BD
- ✅ Pode ser listado
- ✅ Sem erro de validação

---

### Cenário 6.2: Aplicar cupom válido

**Passos:**
1. Adicione produtos totalizando R$ 150,00
2. No carrinho, aplique cupom "PROMO10"
3. Clique "Validar Cupom"

**Resultado Esperado:**
- ✅ Cupom aceito
- ✅ Desconto de R$ 50 aplicado
- ✅ Total reduzido corretamente

---

### Cenário 6.3: Cupom expirado

**Passos:**
1. Crie cupom com "Validade" = data passada
2. Tente aplicar no carrinho

**Resultado Esperado:**
- ✅ Mensagem: "Cupom expirado"
- ✅ Desconto não aplicado

---

### Cenário 6.4: Subtotal mínimo não atingido

**Passos:**
1. Crie cupom com "Subtotal Mínimo" = R$ 200
2. Adicione produtos totalizando R$ 100
3. Tente aplicar cupom

**Resultado Esperado:**
- ✅ Mensagem: "Subtotal insuficiente"
- ✅ Desconto não aplicado

---

### Cenário 6.5: Editar cupom

**Passos:**
1. Liste cupons
2. Clique "Editar" em um cupom
3. Modifique valores
4. Salve

**Resultado Esperado:**
- ✅ Cupom atualizado
- ✅ Valores refletem mudanças

---

### Cenário 6.6: Deletar cupom

**Passos:**
1. Liste cupons
2. Clique "Deletar"

**Resultado Esperado:**
- ✅ Cupom removido
- ✅ Não pode mais ser aplicado

---

## 📦 TESTE 7: Finalização de Pedido

### Cenário 7.1: Checkout completo

**Passos:**
1. Adicione itens ao carrinho (total > R$ 100)
2. Clique "Finalizar Pedido"
3. Preencha:
   - CEP: "12345-678"
   - Endereço: "Rua Principal, 123, São Paulo, SP"
   - Email: "cliente@teste.com"
4. Sem cupom, clique "Finalizar"

**Resultado Esperado:**
- ✅ Pedido criado com ID
- ✅ Status: "pendente"
- ✅ Dados salvos no BD
- ✅ Estoque reduzido
- ✅ Email enviado (verificar corpo)
- ✅ Página de sucesso exibida

---

### Cenário 7.2: Finalizar com cupom

**Passos:**
1. Adicione itens
2. Aplique cupom válido
3. Finalize pedido

**Resultado Esperado:**
- ✅ Desconto incluído no total
- ✅ Campo `cupom_usado` preenchido
- ✅ Email inclui desconto

---

### Cenário 7.3: Validação de CEP

**Passos:**
1. Tente finalizar com CEP inválido: "123" ou "abc"

**Resultado Esperado:**
- ✅ Erro de validação
- ✅ Mensagem: "CEP inválido"
- ✅ Pedido não criado

---

### Cenário 7.4: Estoque insuficiente

**Passos:**
1. Produto tem estoque: 2
2. Tente comprar: 5
3. Finalize pedido

**Resultado Esperado:**
- ✅ Aviso/erro durante finalização
- ✅ Pedido não criado
- ✅ Estoque preservado

---

## 📊 TESTE 8: Listagem de Pedidos

### Cenário 8.1: Listar pedidos

**Passos:**
1. Finalize 3-5 pedidos
2. Acesse `?rota=pedidos`

**Resultado Esperado:**
- ✅ Todos os pedidos listados
- ✅ Ordenação por "mais recente"
- ✅ Paginação funciona (se > 10 pedidos)

---

### Cenário 8.2: Filtro por status

**Passos:**
1. Na lista, selecione status "pendente"
2. Clique "Filtrar"

**Resultado Esperado:**
- ✅ Apenas pedidos com status "pendente"

---

### Cenário 8.3: Busca por produto

**Passos:**
1. Digite um nome de produto
2. Clique "Buscar"

**Resultado Esperado:**
- ✅ Pedidos contendo produto aparecem
- ✅ Campo `produtos_texto` buscável

---

### Cenário 8.4: Alterar status

**Passos:**
1. Em um pedido, modifique status para "enviado"
2. Clique "Salvar"

**Resultado Esperado:**
- ✅ Status atualizado
- ✅ BD reflete mudança

---

## 🔗 TESTE 9: Webhook

### Cenário 9.1: Webhook sem token

**Comando:**
```bash
curl -X POST http://localhost:8000/public/index.php?rota=webhook \
  -H "Content-Type: application/json" \
  -d '{"id": 1, "status": "enviado"}'
```

**Resultado Esperado:**
- ✅ HTTP 401: "Não autorizado"
- ✅ Pedido NÃO atualizado

---

### Cenário 9.2: Webhook com token inválido

**Comando:**
```bash
curl -X POST http://localhost:8000/public/index.php?rota=webhook \
  -H "X-Webhook-Token: token_errado" \
  -H "Content-Type: application/json" \
  -d '{"id": 1, "status": "enviado"}'
```

**Resultado Esperado:**
- ✅ HTTP 401: Acesso negado

---

### Cenário 9.3: Webhook com token válido - Atualizar Status

**Comando:**
```bash
curl -X POST http://localhost:8000/public/index.php?rota=webhook \
  -H "X-Webhook-Token: SEU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{"id": 1, "status": "entregue"}'
```

**Resultado Esperado:**
- ✅ HTTP 200: "Sucesso"
- ✅ Status do pedido mudou para "entregue"
- ✅ Response JSON válido

---

### Cenário 9.4: Webhook - Cancelar pedido

**Comando:**
```bash
curl -X POST http://localhost:8000/public/index.php?rota=webhook \
  -H "X-Webhook-Token: SEU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{"id": 2, "status": "cancelado"}'
```

**Resultado Esperado:**
- ✅ HTTP 200: "Sucesso"
- ✅ Status mudou para "cancelado"

---

### Cenário 9.5: Webhook - Pedido inexistente

**Comando:**
```bash
curl -X POST http://localhost:8000/public/index.php?rota=webhook \
  -H "X-Webhook-Token: SEU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{"id": 99999, "status": "enviado"}'
```

**Resultado Esperado:**
- ✅ HTTP 404: "Pedido não encontrado"

---

## 📦 TESTE 10: Estoque

### Cenário 10.1: Listar estoques

**Passos:**
1. Acesse `?rota=estoque`

**Resultado Esperado:**
- ✅ Todos os produtos/variações listados
- ✅ Quantidades exibidas
- ✅ Paginação

---

### Cenário 10.2: Atualizar quantidade

**Passos:**
1. Modifique quantidade de um estoque
2. Clique "Salvar"

**Resultado Esperado:**
- ✅ Quantidade atualizada no BD
- ✅ Próximo checkout respeita novo estoque

---

## 🔒 TESTE 11: Segurança

### Cenário 11.1: SQL Injection

**Tentativa:**
- Nome do produto: `"; DROP TABLE produtos; --`

**Resultado Esperado:**
- ✅ Rejeitada/escapada
- ✅ Tabela NOT dropada
- ✅ Sem erro SQL exposto

---

### Cenário 11.2: CSRF Protection

**Passos:**
1. Simule form POST sem token CSRF (se implementado)

**Resultado Esperado:**
- ✅ Requisição rejeitada OU
- ✅ Formulários conter token válido

---

### Cenário 11.3: Validação de Email

**Tentativa:**
- Email: `not_an_email`

**Resultado Esperado:**
- ✅ Validação falha
- ✅ Pedido não criado

---

## 📊 TESTE 12: Performance

### Cenário 12.1: Listar 1000 produtos

**Passos:**
1. Se banco tiver 1000+ produtos
2. Acesse listagem

**Resultado Esperado:**
- ✅ Página carrega em < 2s
- ✅ Paginação funciona
- ✅ Sem timeout

---

## 📝 Resultado Final

Após executar todos os testes:

- [ ] 100% de sucesso - ✅ Sistema pronto para produção
- [ ] 90-99% sucesso - ⚠️ Pequenos issues, mas funcional
- [ ] < 90% sucesso - ❌ Não pronto, revisar issues

---

**Data do Teste:** ___/___/______  
**Testador:**  ______________  
**Observações:**  
_________________________________
_________________________________
_________________________________
