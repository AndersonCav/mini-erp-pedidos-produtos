<?php
/**
 * StockRepository.php
 * Camada de acesso a dados para estoques
 */

class StockRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Busca quantidade em estoque
     */
    public function findQuantity($productId, $variationId = null) {
        if ($variationId === null) {
            $query = "SELECT quantidade FROM estoques WHERE produto_id = ? AND variacao_id IS NULL";
            $result = $this->db->execute($query, [$productId], 'i');
        } else {
            $query = "SELECT quantidade FROM estoques WHERE produto_id = ? AND variacao_id = ?";
            $result = $this->db->execute($query, [$productId, $variationId], 'ii');
        }

        $row = $result ? $result->fetch_assoc() : null;
        return $row['quantidade'] ?? 0;
    }

    /**
     * Verifica se há quantidade suficiente em estoque
     */
    public function hasEnoughStock($productId, $quantity, $variationId = null) {
        $available = $this->findQuantity($productId, $variationId);
        return $available >= $quantity;
    }

    /**
     * Reduz quantidade em estoque
     */
    public function decreaseStock($productId, $quantity, $variationId = null) {
        if (!$this->hasEnoughStock($productId, $quantity, $variationId)) {
            throw new Exception('Quantidade insuficiente em estoque');
        }

        if ($variationId === null) {
            $query = "UPDATE estoques SET quantidade = quantidade - ? WHERE produto_id = ? AND variacao_id IS NULL";
            $this->db->executeUpdate($query, [$quantity, $productId], 'ii');
        } else {
            $query = "UPDATE estoques SET quantidade = quantidade - ? WHERE produto_id = ? AND variacao_id = ?";
            $this->db->executeUpdate($query, [$quantity, $productId, $variationId], 'iii');
        }
    }

    /**
     * Aumenta quantidade em estoque
     */
    public function increaseStock($productId, $quantity, $variationId = null) {
        if ($variationId === null) {
            $query = "UPDATE estoques SET quantidade = quantidade + ? WHERE produto_id = ? AND variacao_id IS NULL";
            $this->db->executeUpdate($query, [$quantity, $productId], 'ii');
        } else {
            $query = "UPDATE estoques SET quantidade = quantidade + ? WHERE produto_id = ? AND variacao_id = ?";
            $this->db->executeUpdate($query, [$quantity, $productId, $variationId], 'iii');
        }
    }

    /**
     * Define quantidade em estoque
     */
    public function setQuantity($productId, $quantity, $variationId = null) {
        if ($variationId === null) {
            $query = "UPDATE estoques SET quantidade = ? WHERE produto_id = ? AND variacao_id IS NULL";
            $this->db->executeUpdate($query, [$quantity, $productId], 'ii');
        } else {
            $query = "UPDATE estoques SET quantidade = ? WHERE produto_id = ? AND variacao_id = ?";
            $this->db->executeUpdate($query, [$quantity, $productId, $variationId], 'iii');
        }
    }

    /**
     * Cria novo registro de estoque
     */
    public function create($productId, $quantity, $variationId = null) {
        $query = "INSERT INTO estoques (produto_id, quantidade, variacao_id) VALUES (?, ?, ?)";
        $this->db->executeUpdate($query, [$productId, $quantity, $variationId], 'iii');
        return $this->db->getLastInsertId();
    }

    /**
     * Busca todos os estoques com filtros e paginação
     */
    public function findPaginated($limit, $offset, $orderBy = 'p.nome ASC', $search = '') {
        $whereClause = '';
        $params = [];
        $types = '';

        if (!empty($search)) {
            $whereClause = "WHERE p.nome LIKE ? OR v.nome LIKE ?";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types = 'ss';
        }

        $query = "SELECT 
                    p.id AS produto_id, p.nome, 
                    v.id AS variacao_id, v.nome AS variacao, 
                    e.quantidade
                  FROM produtos p
                  LEFT JOIN variacoes v ON v.produto_id = p.id
                  LEFT JOIN estoques e ON e.produto_id = p.id 
                     AND (e.variacao_id = v.id OR (v.id IS NULL AND e.variacao_id IS NULL))
                  $whereClause
                  ORDER BY $orderBy
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $result = $this->db->execute($query, $params, $types);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Conta total de estoques
     */
    public function countAll($search = '') {
        if (!empty($search)) {
            $query = "SELECT COUNT(DISTINCT p.id) as total FROM produtos p
                      LEFT JOIN variacoes v ON v.produto_id = p.id
                      WHERE p.nome LIKE ? OR v.nome LIKE ?";
            $result = $this->db->execute($query, ["%$search%", "%$search%"], 'ss');
        } else {
            $query = "SELECT COUNT(DISTINCT p.id) as total FROM produtos p";
            $result = $this->db->execute($query);
        }

        $row = $result ? $result->fetch_assoc() : null;
        return $row['total'] ?? 0;
    }
}
