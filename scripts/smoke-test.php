<?php

declare(strict_types=1);

/**
 * Smoke test do Mini ERP.
 *
 * Uso:
 *   php scripts/smoke-test.php           -> valida estrutura e segurança básica
 *   php scripts/smoke-test.php --write   -> executa fluxo real de negócio com criação/cancelamento
 */

require_once __DIR__ . '/../config/bootstrap.php';

require_once __DIR__ . '/../app/repositories/ProductRepository.php';
require_once __DIR__ . '/../app/repositories/VariationRepository.php';
require_once __DIR__ . '/../app/repositories/StockRepository.php';
require_once __DIR__ . '/../app/repositories/CouponRepository.php';
require_once __DIR__ . '/../app/repositories/OrderRepository.php';
require_once __DIR__ . '/../app/repositories/OrderItemRepository.php';

require_once __DIR__ . '/../app/services/ShippingService.php';
require_once __DIR__ . '/../app/services/CouponService.php';
require_once __DIR__ . '/../app/services/StockService.php';
require_once __DIR__ . '/../app/services/OrderService.php';
require_once __DIR__ . '/../app/services/WebhookService.php';

$runWriteFlow = in_array('--write', $argv, true);
$db = Database::getInstance()->getConnection();

$results = [];

function add_result(array &$results, string $name, bool $ok, string $details = ''): void {
    $results[] = [
        'name' => $name,
        'ok' => $ok,
        'details' => $details,
    ];
}

function table_exists(mysqli $db, string $table): bool {
    $safe = $db->real_escape_string($table);
    $result = $db->query("SHOW TABLES LIKE '{$safe}'");
    $exists = $result && $result->num_rows > 0;
    if ($result instanceof mysqli_result) {
        $result->free();
    }
    return $exists;
}

function column_exists(mysqli $db, string $table, string $column): bool {
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = $db->real_escape_string($column);
    $result = $db->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    $exists = $result && $result->num_rows > 0;
    if ($result instanceof mysqli_result) {
        $result->free();
    }
    return $exists;
}

$requiredTables = ['produtos', 'variacoes', 'estoques', 'cupons', 'pedidos', 'pedido_itens'];
foreach ($requiredTables as $table) {
    add_result($results, "Tabela {$table}", table_exists($db, $table));
}

$requiredPedidoColumns = ['subtotal', 'frete', 'desconto', 'total', 'status', 'cliente_email', 'cancelado_em'];
foreach ($requiredPedidoColumns as $column) {
    add_result($results, "Coluna pedidos.{$column}", column_exists($db, 'pedidos', $column));
}

$webhookToken = trim((string)($_ENV['WEBHOOK_TOKEN'] ?? ''));
$webhookTokenConfigured = $webhookToken !== '' && $webhookToken !== 'seu_token_secreto_aqui_replace_isso';
add_result(
    $results,
    'WEBHOOK_TOKEN configurado',
    $webhookTokenConfigured,
    $webhookTokenConfigured ? '' : 'Defina WEBHOOK_TOKEN com valor forte no .env'
);

$couponService = new CouponService();
$invalidCoupon = $couponService->validate('INVALID_CODE', 1000);
add_result(
    $results,
    'Cupom inválido rejeitado',
    $invalidCoupon['valid'] === false,
    $invalidCoupon['message'] ?? ''
);

$webhookService = new WebhookService();
add_result(
    $results,
    'Webhook sem token rejeitado',
    $webhookService->validateToken(null) === false
);

$invalidPayload = $webhookService->processPayload(['id' => 1, 'status' => 'status_invalido']);
add_result(
    $results,
    'Webhook status inválido rejeitado',
    ($invalidPayload['success'] ?? false) === false
);

if ($runWriteFlow) {
    $productRepo = new ProductRepository();
    $variationRepo = new VariationRepository();
    $stockService = new StockService();
    $couponRepo = new CouponRepository();
    $orderService = new OrderService();
    $orderRepo = new OrderRepository();
    $orderItemRepo = new OrderItemRepository();

    $runId = date('YmdHis');
    $productId = null;
    $variationId = null;
    $orderId = null;
    $couponCode = 'SMOKE' . $runId;

    try {
        $productId = $productRepo->create('Produto Smoke ' . $runId, 99.90, '');
        add_result($results, 'Cadastrar produto', $productId > 0, 'ID ' . (string)$productId);

        $variationId = $variationRepo->create((int)$productId, 'Padrao');
        add_result($results, 'Cadastrar variação', $variationId > 0, 'ID ' . (string)$variationId);

        $stockService->create((int)$productId, 20, (int)$variationId);
        $qtyAfterCreate = $stockService->getAvailability((int)$productId, (int)$variationId);
        add_result($results, 'Atualizar estoque', $qtyAfterCreate === 20, 'Quantidade ' . (string)$qtyAfterCreate);

        $couponRepo->create($couponCode, 10.00, 50.00, date('Y-m-d', strtotime('+10 days')));
        $validCoupon = $couponService->validate($couponCode, 100.00);
        add_result($results, 'Cadastrar e validar cupom', ($validCoupon['valid'] ?? false) === true);

        $cart = [
            $productId . ':' . $variationId => 2,
        ];

        $orderCreated = $orderService->create($cart, '01310-100', 'Av Paulista, 1000 - Sao Paulo', 'pendente', $couponCode, 'cliente@example.com');
        $orderId = (int)($orderCreated['orderId'] ?? 0);

        add_result($results, 'Finalizar pedido', ($orderCreated['success'] ?? false) === true && $orderId > 0);

        $orderItems = $orderItemRepo->findByOrderId($orderId);
        add_result($results, 'Persistir itens do pedido', count($orderItems) > 0, 'Itens ' . (string)count($orderItems));

        $qtyAfterOrder = $stockService->getAvailability((int)$productId, (int)$variationId);
        add_result($results, 'Baixar estoque na compra', $qtyAfterOrder === 18, 'Quantidade ' . (string)$qtyAfterOrder);

        $statusUpdate = $orderService->updateStatus($orderId, 'processando');
        add_result($results, 'Alterar status', ($statusUpdate['success'] ?? false) === true);

        $cancelResult = $orderService->cancel($orderId);
        add_result($results, 'Cancelar pedido com reversão', ($cancelResult['success'] ?? false) === true);

        $qtyAfterCancel = $stockService->getAvailability((int)$productId, (int)$variationId);
        add_result($results, 'Reverter estoque no cancelamento', $qtyAfterCancel === 20, 'Quantidade ' . (string)$qtyAfterCancel);

        $orderData = $orderRepo->findById($orderId);
        add_result(
            $results,
            'Status cancelado persistido',
            strtolower((string)($orderData['status'] ?? '')) === 'cancelado'
        );
    } catch (Throwable $e) {
        add_result($results, 'Fluxo write smoke', false, 'Erro: ' . $e->getMessage());
    } finally {
        if ($orderId) {
            try {
                $orderRepo->delete($orderId);
            } catch (Throwable $e) {
            }
        }

        if ($couponCode) {
            try {
                $couponRepo->delete($couponCode);
            } catch (Throwable $e) {
            }
        }

        if ($productId) {
            try {
                $productRepo->delete($productId);
            } catch (Throwable $e) {
            }
        }
    }
}

$passed = 0;
$failed = 0;

echo "Mini ERP Smoke Test\n";
echo "Modo write flow: " . ($runWriteFlow ? 'ON' : 'OFF') . "\n";
echo str_repeat('-', 70) . "\n";

foreach ($results as $row) {
    if ($row['ok']) {
        $passed++;
        echo "[PASS] " . $row['name'];
    } else {
        $failed++;
        echo "[FAIL] " . $row['name'];
    }

    if ($row['details'] !== '') {
        echo " -> " . $row['details'];
    }

    echo "\n";
}

echo str_repeat('-', 70) . "\n";
echo "Total: " . count($results) . " | PASS: {$passed} | FAIL: {$failed}\n";

exit($failed > 0 ? 1 : 0);
