<?php
/**
 * public/index.php
 * Arquivo de entrada da aplicação
 */

// Inicializa aplicação
require_once '../config/bootstrap.php';

// Autoload de helpers
require_once '../app/helpers/functions.php';

// Autoload de classes core (já carregadas no bootstrap)
// Carrega repositories
require_once '../app/repositories/ProductRepository.php';
require_once '../app/repositories/VariationRepository.php';
require_once '../app/repositories/StockRepository.php';
require_once '../app/repositories/CouponRepository.php';
require_once '../app/repositories/OrderRepository.php';
require_once '../app/repositories/OrderItemRepository.php';

// Carrega services
require_once '../app/services/ShippingService.php';
require_once '../app/services/CouponService.php';
require_once '../app/services/StockService.php';
require_once '../app/services/OrderService.php';
require_once '../app/services/EmailService.php';
require_once '../app/services/WebhookService.php';

// Carrega validators
require_once '../app/validators/CsrfValidator.php';
require_once '../app/validators/ProductValidator.php';
require_once '../app/validators/OrderValidator.php';
require_once '../app/validators/CouponValidator.php';

// Rotas
try {
    require_once '../routes/web.php';
} catch (Throwable $e) {
    Logger::error('Uncaught exception: ' . $e->getMessage());
    http_response_code(500);
    echo "<h1>Erro interno do servidor</h1>";
    if ($_ENV['APP_DEBUG'] ?? false) {
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
}