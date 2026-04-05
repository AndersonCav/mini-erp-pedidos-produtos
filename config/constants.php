<?php
/**
 * Constantes de Configuração do Mini ERP
 * Define padrões, valores e regras de negócio
 */

// ===== PADRÕES DE NEGÓCIO =====

// Status válidos de pedido
define('VALID_ORDER_STATUSES', [
    'pendente',
    'processando',
    'enviado',
    'entregue',
    'cancelado',
    'devolvido'
]);

// Regras de frete
define('SHIPPING_RULES', [
    'subtotal_min_free' => 200.00,       // Frete grátis acima de R$200
    'subtotal_min_reduced' => 52.00,     // Frete reduzido entre R$52 e R$166,59
    'subtotal_max_reduced' => 166.59,
    'reduced_shipping' => 15.00,          // R$15 para frete reduzido
    'standard_shipping' => 20.00          // R$20 para frete padrão
]);

// ===== VALIDAÇÕES =====

define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ===== PAGINAÇÃO =====

define('DEFAULT_PAGE_LIMIT', 10);
define('DEFAULT_PRODUCTS_PER_PAGE', 9);

// ===== WEBHOOK =====

define('WEBHOOK_TOKEN_HEADER', 'X-Webhook-Token');

// ===== SESSÃO =====

define('CART_SESSION_KEY', 'carrinho');
define('MESSAGE_SESSION_KEY', 'mensagem');

// ===== TIMEZONE =====

if (!ini_get('date.timezone')) {
    date_default_timezone_set('America/Sao_Paulo');
}
