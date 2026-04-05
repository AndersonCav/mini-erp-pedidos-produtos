<?php
/**
 * OrderRepository.php
 * Camada de acesso a dados para pedidos
 */

class OrderRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Busca pedido por ID
     */
    public function findById($id) {
        $query = "SELECT * FROM pedidos WHERE id = ?";
        $result = $this->db->execute($query, [$id], 'i');
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Busca todos os pedidos
     */
    public function findAll() {
        $query = "SELECT * FROM pedidos ORDER BY criado_em DESC";
        $result = $this->db->execute($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Busca pedidos com filtros e paginação
     */
    public function findFiltered($search = '', $status = '', $orderBy = 'criado_em DESC', $limit = 10, $offset = 0) {
        $whereConditions = [];
        $params = [];
        $types = '';

        if (!empty($search)) {
            $whereConditions[] = "produtos_texto LIKE ?";
            $params[] = "%$search%";
            $types .= 's';
        }

        if (!empty($status)) {
            $whereConditions[] = "status = ?";
            $params[] = $status;
            $types .= 's';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $query = "SELECT * FROM pedidos $whereClause ORDER BY $orderBy LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $result = $this->db->execute($query, $params, $types);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Conta total de pedidos com filtros
     */
    public function countFiltered($search = '', $status = '') {
        $whereConditions = [];
        $params = [];
        $types = '';

        if (!empty($search)) {
            $whereConditions[] = "produtos_texto LIKE ?";
            $params[] = "%$search%";
            $types .= 's';
        }

        if (!empty($status)) {
            $whereConditions[] = "status = ?";
            $params[] = $status;
            $types .= 's';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        $query = "SELECT COUNT(*) as total FROM pedidos $whereClause";

        $result = $types ? $this->db->execute($query, $params, $types) : $this->db->execute($query);
        $row = $result ? $result->fetch_assoc() : null;
        return $row['total'] ?? 0;
    }

    /**
     * Cria novo pedido
     */
    public function create($subtotal, $shipping, $discount, $total, $status, $cep, $address, $productsText, $coupon = null) {
        $query = "INSERT INTO pedidos (subtotal, frete, desconto, total, status, cep, endereco, produtos_texto, cupom_usado, criado_em) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $this->db->executeUpdate(
            $query,
            [$subtotal, $shipping, $discount, $total, $status, $cep, $address, $productsText, $coupon],
            'ddddsssss'
        );
        return $this->db->getLastInsertId();
    }

    /**
     * Atualiza status do pedido
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE pedidos SET status = ? WHERE id = ?";
        return $this->db->executeUpdate($query, [$status, $id], 'si');
    }

    /**
     * Deleta pedido
     */
    public function delete($id) {
        $query = "DELETE FROM pedidos WHERE id = ?";
        return $this->db->executeUpdate($query, [$id], 'i');
    }

    /**
     * Verifica se pedido existe
     */
    public function exists($id) {
        $query = "SELECT id FROM pedidos WHERE id = ? LIMIT 1";
        $result = $this->db->execute($query, [$id], 'i');
        return ($result && $result->num_rows > 0);
    }
}
