<?php
/**
 * OrderService.php
 * Serviço de orquestração de pedidos
 */

class OrderService {
    private $orderRepository;
    private $orderItemRepository;
    private $stockService;
    private $couponService;

    public function __construct() {
        $this->orderRepository = new OrderRepository();
        $this->orderItemRepository = new OrderItemRepository();
        $this->stockService = new StockService();
        $this->couponService = new CouponService();
    }

    /**
     * Calcula total do pedido (subtotal + frete - desconto)
     */
    public function calculateTotal($cartItems, $couponCode = null) {
        $calculation = $this->calculateBreakdown($cartItems, $couponCode);

        if (!$calculation['success']) {
            return $calculation;
        }

        return [
            'success' => true,
            'subtotal' => $calculation['subtotal'],
            'shipping' => $calculation['shipping'],
            'discount' => $calculation['discount'],
            'total' => $calculation['total'],
            'coupon' => $calculation['coupon'] ?? null
        ];
    }

    /**
     * Calcula detalhamento completo do pedido
     */
    public function calculateBreakdown($cartItems, $couponCode = null) {
        try {
            if (empty($cartItems) || !is_array($cartItems)) {
                return ['success' => false, 'message' => 'Carrinho vazio'];
            }

            $subtotal = 0;
            $productsText = '';
            $items = [];
            $productRepo = new ProductRepository();
            $variationRepo = new VariationRepository();

            foreach ($cartItems as $key => $quantity) {
                $parts = explode(':', (string) $key);
                $productId = (int) ($parts[0] ?? 0);
                $variationId = isset($parts[1]) ? (int) $parts[1] : null;

                if ($productId <= 0) {
                    return ['success' => false, 'message' => 'Produto inválido no carrinho'];
                }

                if ($quantity <= 0) {
                    return ['success' => false, 'message' => 'Quantidade deve ser maior que zero'];
                }

                $product = $productRepo->findById($productId);
                if (!$product) {
                    return ['success' => false, 'message' => "Produto #$productId não encontrado"];
                }

                if (!$this->stockService->isAvailable($productId, (int) $quantity, $variationId)) {
                    return ['success' => false, 'message' => "Quantidade insuficiente para {$product['nome']}"];
                }

                $unitPrice = (float) $product['preco'];
                $lineTotal = $unitPrice * (int) $quantity;
                $subtotal += $lineTotal;

                $itemName = $product['nome'];
                if ($variationId !== null) {
                    $variation = $variationRepo->findById($variationId);
                    if ($variation) {
                        $itemName .= " ({$variation['nome']})";
                    }
                }

                $productsText .= sprintf(
                    "%dx %s - R$ %s\n",
                    (int) $quantity,
                    $itemName,
                    number_format($lineTotal, 2, ',', '.')
                );

                $items[] = [
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'name' => $itemName,
                    'unit_price' => $unitPrice,
                    'quantity' => (int) $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $shipping = ShippingService::calculateShipping($subtotal);

            $discount = 0;
            $couponData = null;
            if (!empty($couponCode)) {
                $validation = $this->couponService->validate($couponCode, $subtotal);
                if (!$validation['valid']) {
                    return ['success' => false, 'message' => $validation['message']];
                }

                $discount = (float) $validation['discount'];
                $couponData = $validation['coupon'];
            }

            $total = $subtotal + $shipping - $discount;

            return [
                'success' => true,
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'discount' => $discount,
                'total' => $total,
                'productsText' => $productsText,
                'items' => $items,
                'coupon' => $couponData
            ];
        } catch (Exception $e) {
            Logger::error('Order calculation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao calcular pedido'];
        }
    }

    /**
     * Cria novo pedido
     */
    public function create($cartItems, $cep, $address, $status = 'pendente', $couponCode = null, $customerEmail = null) {
        try {
            $calculation = $this->calculateBreakdown($cartItems, $couponCode);
            if (!$calculation['success']) {
                return $calculation;
            }

            if (!in_array($status, VALID_ORDER_STATUSES, true)) {
                return ['success' => false, 'message' => 'Status de pedido inválido'];
            }

            $db = Database::getInstance();
            $db->beginTransaction();

            try {
                $orderId = $this->orderRepository->create(
                    $calculation['subtotal'],
                    $calculation['shipping'],
                    $calculation['discount'],
                    $calculation['total'],
                    $status,
                    $cep,
                    $address,
                    $calculation['productsText'],
                    $couponCode,
                    $customerEmail
                );

                $this->orderItemRepository->createMany($orderId, $calculation['items']);

                foreach ($calculation['items'] as $item) {
                    if (!$this->stockService->reserve($item['product_id'], $item['quantity'], $item['variation_id'])) {
                        throw new Exception('Falha ao reservar estoque');
                    }
                }

                $db->commit();

                Logger::info("Order created: #$orderId");

                return [
                    'success' => true,
                    'orderId' => $orderId,
                    'subtotal' => $calculation['subtotal'],
                    'shipping' => $calculation['shipping'],
                    'discount' => $calculation['discount'],
                    'total' => $calculation['total'],
                    'productsText' => $calculation['productsText']
                ];
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            Logger::error('Order creation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao criar pedido'];
        }
    }

    /**
     * Atualiza status do pedido
     */
    public function updateStatus($orderId, $newStatus) {
        $newStatus = strtolower(trim((string) $newStatus));

        if (!in_array($newStatus, VALID_ORDER_STATUSES, true)) {
            return ['success' => false, 'message' => 'Status inválido'];
        }

        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Pedido não encontrado'];
        }

        if ($newStatus === 'cancelado') {
            return $this->cancel($orderId);
        }

        $this->orderRepository->updateStatus($orderId, $newStatus);
        Logger::info("Order #$orderId status changed to: $newStatus");

        return ['success' => true, 'message' => 'Status atualizado'];
    }

    /**
     * Cancela pedido e reverte estoque
     */
    public function cancel($orderId) {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Pedido não encontrado'];
        }

        if (strtolower((string) $order['status']) === 'cancelado') {
            return ['success' => true, 'message' => 'Pedido já está cancelado'];
        }

        $items = $this->orderItemRepository->findByOrderId($orderId);
        if (empty($items)) {
            Logger::warning("Order #$orderId has no items for stock reversal");
            return ['success' => false, 'message' => 'Itens do pedido não encontrados para reversão de estoque'];
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            foreach ($items as $item) {
                $this->stockService->release(
                    (int) $item['produto_id'],
                    (int) $item['quantidade'],
                    isset($item['variacao_id']) ? (int) $item['variacao_id'] : null
                );
            }

            $this->orderRepository->markAsCancelled($orderId);
            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            Logger::error("Order #$orderId cancellation rollback: " . $e->getMessage());
            return ['success' => false, 'message' => 'Falha ao cancelar pedido'];
        }

        Logger::info("Order #$orderId cancelled");
        return ['success' => true, 'message' => 'Pedido cancelado e estoque revertido'];
    }

    /**
     * Deleta pedido
     */
    public function delete($orderId) {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Pedido não encontrado'];
        }

        $this->orderRepository->delete($orderId);
        Logger::info("Order #$orderId deleted");

        return ['success' => true, 'message' => 'Pedido deletado'];
    }

    /**
     * Busca pedido por ID
     */
    public function getById($orderId) {
        return $this->orderRepository->findById($orderId);
    }

    /**
     * Busca itens do pedido
     */
    public function getItemsByOrderId($orderId) {
        return $this->orderItemRepository->findByOrderId($orderId);
    }

    /**
     * Lista pedidos com filtros
     */
    public function getFiltered($search = '', $status = '', $orderBy = 'criado_em DESC', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        $orders = $this->orderRepository->findFiltered($search, $status, $orderBy, $limit, $offset);
        $total = $this->orderRepository->countFiltered($search, $status);
        $totalPages = (int) ceil($total / max(1, $limit));

        return [
            'orders' => $orders,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'perPage' => $limit
        ];
    }
}
