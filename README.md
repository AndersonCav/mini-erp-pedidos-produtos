# Mini ERP Commerce

Mini ERP de ecommerce construído com PHP puro (MVC artesanal), MySQL, Bootstrap e JavaScript.

O projeto gerencia produtos, variações, estoque, cupons e pedidos com foco em regras reais de operação e segurança básica de produção para um portfólio técnico.

## Visão geral

Este sistema cobre o fluxo completo:

- cadastro e manutenção de produtos com variações
- gestão de estoque por produto e por variação
- carrinho de compras em sessão
- cálculo de subtotal, frete, desconto e total
- aplicação de cupons com validação de validade e subtotal mínimo
- criação de pedidos com persistência dos itens
- alteração de status e cancelamento com reversão de estoque
- endpoint de webhook autenticado por token

## Stack

- PHP 7.4+
- MySQL 5.7+ ou MariaDB 10.3+
- Bootstrap 5
- JavaScript (Fetch API)
- MVC artesanal (sem framework pesado)

## Funcionalidades principais

- Produtos:
- CRUD de produtos
- upload de imagem
- suporte a variações

- Estoque:
- estoque para produto simples
- estoque para variações
- atualização por tela administrativa

- Cupons:
- cadastro e edição
- validação por data
- validação por subtotal mínimo

- Pedidos:
- fechamento de pedido em transação
- persistência de itens em tabela dedicada
- alteração de status com validação
- cancelamento com reversão de estoque

- Segurança:
- prepared statements em repositories
- CSRF em ações sensíveis
- validação de payload no webhook
- autenticação por header X-Webhook-Token
- logs centralizados

## Arquitetura atual

Estrutura principal:

- config/: bootstrap, constantes, configuração
- app/core/: Database, Request, Response, Logger
- app/repositories/: persistência de dados (prepared statements)
- app/services/: regras de negócio
- app/controllers/: orquestração HTTP
- app/validators/: validação de domínio e inputs
- app/views/: templates
- routes/: roteamento principal
- public/: entrypoint e assets
- database/: schema e scripts

Responsabilidades:

- Controller: recebe requisição, valida entrada, chama service, responde
- Service: regra de negócio, fluxo transacional
- Repository: SQL e persistência
- Validator: validação de campos e regras básicas

## Instalação

1. Clone o repositório

```bash
git clone https://github.com/AndersonCav/mini-erp-pedidos-produtos.git
cd mini-erp-pedidos-produtos
```

2. Configure ambiente

```bash
cp .env.example .env
```

Edite o arquivo .env:

```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=mini_erp
APP_DEBUG=false
TIMEZONE=America/Sao_Paulo
ADMIN_EMAIL=admin@mini-erp.com.br
WEBHOOK_TOKEN=troque_por_um_token_forte
```

Para gerar token seguro:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

3. Crie banco e tabelas

Opção A (manual):

```sql
CREATE DATABASE mini_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Depois importe:

```bash
mysql -u root mini_erp < database/schema.sql
```

Opção B (script auxiliar):

```bash
php database/run-schema.php
```

Para banco já existente (sem recriar tudo), aplique também a migration:

```bash
mysql -u root mini_erp < database/migrations/20260405_add_order_items_and_order_columns.sql
```

4. Execute localmente

```bash
php -S localhost:8000 -t public/
```

Acesse:

- http://localhost:8000

## Banco de dados

Schema oficial:

- database/schema.sql

Tabelas principais:

- produtos
- variacoes
- estoques
- cupons
- pedidos
- pedido_itens
- logs

Observações importantes:

- pedidos mantém snapshot textual (produtos_texto) para visualização
- pedido_itens mantém estrutura detalhada para auditoria e reversão de estoque
- cancelamento usa pedido_itens para devolver quantidades ao estoque

## Webhook seguro

Endpoint:

- POST /index.php?rota=webhook

Headers obrigatórios:

- Content-Type: application/json
- X-Webhook-Token: token definido em WEBHOOK_TOKEN

Payload mínimo:

```json
{
  "id": 123,
  "status": "cancelado"
}
```

Status aceitos:

- pendente
- processando
- enviado
- entregue
- cancelado
- devolvido

Alias aceitos no webhook:

- cancelled -> cancelado
- processing -> processando
- shipped -> enviado
- completed -> entregue
- pending -> pendente

Exemplo curl:

```bash
curl -X POST "http://localhost:8000/index.php?rota=webhook" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Token: SEU_TOKEN" \
  -d '{"id":1,"status":"cancelado"}'
```

Resposta JSON padrão:

```json
{
  "success": true,
  "message": "Pedido cancelado com sucesso",
  "data": {
    "orderId": 1,
    "status": "cancelado"
  }
}
```

## Checklist de testes manuais

Use também CHECKLIST_TESTS.md para roteiro completo.

Cenários mínimos:

- cadastrar produto
- cadastrar variação
- cadastrar cupom
- atualizar estoque
- adicionar ao carrinho
- aplicar cupom válido
- rejeitar cupom inválido
- finalizar pedido
- alterar status de pedido
- cancelar pedido com reversão de estoque
- chamar webhook com token válido
- rejeitar webhook sem token
- rejeitar webhook com status inválido

## Smoke test rápido

Script para validação de sanidade do projeto:

```bash
php scripts/smoke-test.php
```

Para validar também fluxo real de negócio com escrita temporária (cria pedido e cancela com reversão de estoque):

```bash
php scripts/smoke-test.php --write
```

## Limitações atuais

- autenticação/autorização administrativa não implementada
- envio de e-mail usa mail() (adequado para ambiente simples, não ideal para produção)
- cobertura de testes automatizados ainda não completa
- sem fila para reprocessamento de webhook

## Melhorias futuras

- autenticação com controle de acesso por perfil
- testes automatizados de integração e domínio
- provider de e-mail transacional (SMTP/API)
- paginação e filtros avançados no módulo de pedidos
- trilha de auditoria de alterações de status
- idempotência de webhook por chave de evento

## Qualidade e segurança

O projeto foi estruturado para manter stack simples com boas práticas:

- prepared statements
- separação em camadas (controllers/services/repositories)
- validação de entrada
- CSRF em ações sensíveis
- webhook autenticado
- logs de eventos e falhas

## Licença

MIT
