<?php
/**
 * OrderItemRepository.php
 * Camada de acesso a dados para itens de pedido
 */

class OrderItemRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Cria um item de pedido
     */
    public function create($orderId, $productId, $variationId, $itemName, $unitPrice, $quantity, $lineTotal) {
        $query = "INSERT INTO pedido_itens (pedido_id, produto_id, variacao_id, nome_item, preco_unitario, quantidade, total_linha)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";

        return $this->db->executeUpdate(
            $query,
            [$orderId, $productId, $variationId, $itemName, $unitPrice, $quantity, $lineTotal],
            'iiisdid'
        );
    }

    /**
     * Cria múltiplos itens em lote
     */
    public function createMany($orderId, array $items) {
        foreach ($items as $item) {
            $this->create(
                $orderId,
                (int) $item['product_id'],
                isset($item['variation_id']) ? (int) $item['variation_id'] : null,
                $item['name'],
                (float) $item['unit_price'],
                (int) $item['quantity'],
                (float) $item['line_total']
            );
        }
    }

    /**
     * Busca itens por pedido
     */
    public function findByOrderId($orderId) {
        $query = "SELECT * FROM pedido_itens WHERE pedido_id = ? ORDER BY id ASC";
        $result = $this->db->execute($query, [$orderId], 'i');
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
