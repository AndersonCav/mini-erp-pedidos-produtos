<?php
/**
 * OrderService.php
 * Serviço de orquestração de pedidos
 * Centraliza lógica de cálculo e criação de pedidos
 */

class OrderService {
    private $orderRepository;
    private $stockService;
    private $couponService;
    private $shippingService;

    public function __construct() {
        $this->orderRepository = new OrderRepository();
        $this->stockService = new StockService();
        $this->couponService = new CouponService();
        $this->shippingService = 'ShippingService'; // Static class
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
            // Validação básica de carrinho
            if (empty($cartItems) || !is_array($cartItems)) {
                return ['success' => false, 'message' => 'Carrinho vazio'];
            }

            $subtotal = 0;
            $productsText = '';
            $productRepo = new ProductRepository();

            // Calcula subtotal e monta texto de produtos
            foreach ($cartItems as $key => $quantity) {
                $parts = explode(':', $key);
                $productId = (int)$parts[0];
                $variationId = isset($parts[1]) ? (int)$parts[1] : null;

                // Validações
                if ($quantity <= 0) {
                    return ['success' => false, 'message' => 'Quantidade deve ser maior que zero'];
                }

                // Verifica se produto existe
                $product = $productRepo->findById($productId);
                if (!$product) {
                    return ['success' => false, 'message' => "Produto #$productId não encontrado"];
                }

                // Valida disponibilidade
                if (!$this->stockService->isAvailable($productId, $quantity, $variationId)) {
                    return ['success' => false, 'message' => "Quantidade insuficiente para o produto #{$product['nome']}"];
                }

                // Acumula subtotal
                $subtotal += $product['preco'] * $quantity;

                // Monta descrição
                $productName = $product['nome'];
                if ($variationId) {
                    $variationRepo = new VariationRepository();
                    $variation = $variationRepo->findById($variationId);
                    if ($variation) {
                        $productName .= " ({$variation['nome']})";
                    }
                }

                $productsText .= "{$quantity}x {$productName} - R$ " . 
                               number_format($product['preco'] * $quantity, 2, ',', '.') . "\n";
            }

            // Calcula frete
            $shipping = ShippingService::calculateShipping($subtotal);

            // Valida e aplica cupom
            $discount = 0;
            $couponData = null;

            if ($couponCode) {
                $validation = $this->couponService->validate($couponCode, $subtotal);
                if (!$validation['valid']) {
                    return ['success' => false, 'message' => $validation['message']];
                }
                $discount = $validation['discount'];
                $couponData = $validation['coupon'];
            }

            // Calcula total final
            $total = $subtotal + $shipping - $discount;

            return [
                'success' => true,
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'discount' => $discount,
                'total' => $total,
                'productsText' => $productsText,
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
    public function create($cartItems, $cep, $address, $status = 'pendente', $couponCode = null) {
        try {
            // Calcula tudo
            $calculation = $this->calculateBreakdown($cartItems, $couponCode);

            if (!$calculation['success']) {
                return $calculation;
            }

            // Valida status
            if (!in_array($status, VALID_ORDER_STATUSES)) {
                return ['success' => false, 'message' => 'Status de pedido inválido'];
            }

            // Inicia transação
            $db = Database::getInstance();
            $db->beginTransaction();

            try {
                // Cria pedido
                $orderId = $this->orderRepository->create(
                    $calculation['subtotal'],
                    $calculation['shipping'],
                    $calculation['discount'],
                    $calculation['total'],
                    $status,
                    $cep,
                    $address,
                    $calculation['productsText'],
                    $couponCode
                );

                // Reduz estoques
                foreach ($cartItems as $key => $quantity) {
                    $parts = explode(':', $key);
                    $productId = (int)$parts[0];
                    $variationId = isset($parts[1]) ? (int)$parts[1] : null;

                    if (!$this->stockService->reserve($productId, $quantity, $variationId)) {
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
                    'total' => $calculation['total']
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
        if (!in_array($newStatus, VALID_ORDER_STATUSES)) {
            return ['success' => false, 'message' => 'Status inválido'];
        }

        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Pedido não encontrado'];
        }

        $this->orderRepository->updateStatus($orderId, $newStatus);
        Logger::info("Order #$orderId status changed to: $newStatus");

        return ['success' => true, 'message' => 'Status atualizado'];
    }

    /**
     * Cancela pedido (reverte estoque)
     */
    public function cancel($orderId) {
        $order = $this->orderRepository->findById($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Pedido não encontrado'];
        }

        // Atualiza status
        $this->orderRepository->updateStatus($orderId, 'cancelado');

        // TODO: Reverter estoque (seria necessário rastrear itens)
        // Por enquanto apenas marca como cancelado

        Logger::info("Order #$orderId cancelled");
        return ['success' => true, 'message' => 'Pedido cancelado'];
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
     * Lista pedidos com filtros
     */
    public function getFiltered($search = '', $status = '', $orderBy = 'criado_em DESC', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        $orders = $this->orderRepository->findFiltered($search, $status, $orderBy, $limit, $offset);
        $total = $this->orderRepository->countFiltered($search, $status);
        $totalPages = ceil($total / $limit);

        return [
            'orders' => $orders,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'perPage' => $limit
        ];
    }
}
