-- Mini ERP Schema
-- Database para gerenciar Produtos, Pedidos, Cupons e Estoque

-- ===== PRODUTOS =====
CREATE TABLE IF NOT EXISTS `produtos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL,
  `preco` DECIMAL(10, 2) NOT NULL,
  `imagem_url` VARCHAR(500),
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_nome` (`nome`),
  INDEX `idx_preco` (`preco`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== VARIAÇÕES =====
CREATE TABLE IF NOT EXISTS `variacoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE CASCADE,
  INDEX `idx_produto_id` (`produto_id`),
  INDEX `idx_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== ESTOQUES =====
CREATE TABLE IF NOT EXISTS `estoques` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT NOT NULL,
  `variacao_id` INT DEFAULT NULL,
  `quantidade` INT NOT NULL DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`variacao_id`) REFERENCES `variacoes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_produto_variacao` (`produto_id`, `variacao_id`),
  INDEX `idx_quantidade` (`quantidade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== CUPONS =====
CREATE TABLE IF NOT EXISTS `cupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) NOT NULL UNIQUE,
  `valor_desconto` DECIMAL(10, 2) NOT NULL,
  `minimo_subtotal` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `validade` DATE NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_codigo` (`codigo`),
  INDEX `idx_validade` (`validade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== PEDIDOS =====
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  `frete` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `desconto` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `total` DECIMAL(10, 2) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pendente',
  `cliente_email` VARCHAR(255) DEFAULT NULL,
  `cep` VARCHAR(9),
  `endereco` TEXT,
  `produtos_texto` TEXT NOT NULL,
  `cupom_usado` VARCHAR(50),
  `cancelado_em` TIMESTAMP NULL DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`),
  INDEX `idx_cliente_email` (`cliente_email`),
  INDEX `idx_criado_em` (`criado_em`),
  INDEX `idx_total` (`total`),
  FULLTEXT INDEX `ft_produtos_texto` (`produtos_texto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== ITENS DOS PEDIDOS =====
CREATE TABLE IF NOT EXISTS `pedido_itens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NOT NULL,
  `produto_id` INT NOT NULL,
  `variacao_id` INT DEFAULT NULL,
  `nome_item` VARCHAR(255) NOT NULL,
  `preco_unitario` DECIMAL(10, 2) NOT NULL,
  `quantidade` INT NOT NULL,
  `total_linha` DECIMAL(10, 2) NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`variacao_id`) REFERENCES `variacoes`(`id`) ON DELETE SET NULL,
  INDEX `idx_pedido_id` (`pedido_id`),
  INDEX `idx_produto_id` (`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== LOGS (OPCIONAL - para auditoria) =====
CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nivel` VARCHAR(20) NOT NULL,
  `mensagem` TEXT NOT NULL,
  `contexto` JSON,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_nivel` (`nivel`),
  INDEX `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
