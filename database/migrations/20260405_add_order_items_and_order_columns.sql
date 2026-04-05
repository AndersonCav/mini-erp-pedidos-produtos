-- Migration: add order item tracking and cancellation metadata
-- Date: 2026-04-05

ALTER TABLE pedidos
    ADD COLUMN IF NOT EXISTS cliente_email VARCHAR(255) DEFAULT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS cancelado_em TIMESTAMP NULL DEFAULT NULL AFTER cupom_usado;

CREATE INDEX IF NOT EXISTS idx_cliente_email ON pedidos (cliente_email);

CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    variacao_id INT DEFAULT NULL,
    nome_item VARCHAR(255) NOT NULL,
    preco_unitario DECIMAL(10, 2) NOT NULL,
    quantidade INT NOT NULL,
    total_linha DECIMAL(10, 2) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedido_itens_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pedido_itens_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_pedido_itens_variacao FOREIGN KEY (variacao_id) REFERENCES variacoes(id) ON DELETE SET NULL,
    INDEX idx_pedido_id (pedido_id),
    INDEX idx_produto_id (produto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
