<?php
/**
 * VariationRepository.php
 * Camada de acesso a dados para variações
 */

class VariationRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Busca todas as variações de um produto
     */
    public function findByProductId($productId) {
        $query = "SELECT * FROM variacoes WHERE produto_id = ? ORDER BY nome ASC";
        $result = $this->db->execute($query, [$productId], 'i');
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Busca variação por ID
     */
    public function findById($id) {
        $query = "SELECT * FROM variacoes WHERE id = ?";
        $result = $this->db->execute($query, [$id], 'i');
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Cria nova variação
     */
    public function create($productId, $name) {
        $query = "INSERT INTO variacoes (produto_id, nome) VALUES (?, ?)";
        $this->db->executeUpdate($query, [$productId, $name], 'is');
        return $this->db->getLastInsertId();
    }

    /**
     * Deleta variação (cascata do DB remove estoques)
     */
    public function delete($id) {
        $query = "DELETE FROM variacoes WHERE id = ?";
        return $this->db->executeUpdate($query, [$id], 'i');
    }
}
