<?php
/**
 * ProductRepository.php
 * Camada de acesso a dados para produtos com prepared statements
 */

class ProductRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Busca todos os produtos
     */
    public function findAll() {
        $query = "SELECT * FROM produtos ORDER BY nome ASC";
        $result = $this->db->execute($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Busca produto por ID
     */
    public function findById($id) {
        $query = "SELECT * FROM produtos WHERE id = ?";
        $result = $this->db->execute($query, [$id], 'i');
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Busca produtos com paginação e filtro
     */
    public function findPaginated($limit, $offset, $orderBy = 'nome ASC', $search = '') {
        $whereClause = '';
        $params = [];
        $types = '';

        if (!empty($search)) {
            $whereClause = "WHERE nome LIKE ?";
            $params[] = "%$search%";
            $types = 's';
        }

        $query = "SELECT * FROM produtos $whereClause ORDER BY $orderBy LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $result = $this->db->execute($query, $params, $types);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Conta total de produtos com filtro opcional
     */
    public function countAll($search = '') {
        if (!empty($search)) {
            $query = "SELECT COUNT(*) as total FROM produtos WHERE nome LIKE ?";
            $result = $this->db->execute($query, ["%$search%"], 's');
        } else {
            $query = "SELECT COUNT(*) as total FROM produtos";
            $result = $this->db->execute($query);
        }

        $row = $result ? $result->fetch_assoc() : null;
        return $row['total'] ?? 0;
    }

    /**
     * Cria novo produto
     */
    public function create($name, $price, $imageUrl = '') {
        $query = "INSERT INTO produtos (nome, preco, imagem_url) VALUES (?, ?, ?)";
        $this->db->executeUpdate($query, [$name, $price, $imageUrl], 'sds');
        return $this->db->getLastInsertId();
    }

    /**
     * Atualiza produto
     */
    public function update($id, $name, $price, $imageUrl = null) {
        if ($imageUrl === null) {
            $query = "UPDATE produtos SET nome = ?, preco = ? WHERE id = ?";
            $this->db->executeUpdate($query, [$name, $price, $id], 'sdi');
        } else {
            $query = "UPDATE produtos SET nome = ?, preco = ?, imagem_url = ? WHERE id = ?";
            $this->db->executeUpdate($query, [$name, $price, $imageUrl, $id], 'sdsi');
        }
    }

    /**
     * Deleta produto (cascata referencial do DB)
     */
    public function delete($id) {
        // Primeiro, remove arquivo de imagem se existir
        $product = $this->findById($id);
        if ($product && $product['imagem_url'] && file_exists($product['imagem_url'])) {
            unlink($product['imagem_url']);
        }

        $query = "DELETE FROM produtos WHERE id = ?";
        return $this->db->executeUpdate($query, [$id], 'i');
    }

    /**
     * Busca produtos com suas variações e estoques
     */
    public function findWithVariations($productId) {
        $query = "SELECT 
                    p.id, p.nome, p.preco, p.imagem_url,
                    v.id as variacao_id, v.nome as variacao_nome,
                    e.quantidade
                  FROM produtos p
                  LEFT JOIN variacoes v ON v.produto_id = p.id
                  LEFT JOIN estoques e ON e.produto_id = p.id 
                     AND (e.variacao_id = v.id OR (v.id IS NULL AND e.variacao_id IS NULL))
                  WHERE p.id = ?
                  ORDER BY v.nome ASC";
        
        $result = $this->db->execute($query, [$productId], 'i');
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
