<?php
/**
 * CouponRepository.php
 * Camada de acesso a dados para cupons
 */

class CouponRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Busca cupom por código
     */
    public function findByCode($code) {
        $query = "SELECT * FROM cupons WHERE codigo = ?";
        $result = $this->db->execute($query, [$code], 's');
        return $result ? $result->fetch_assoc() : null;
    }

    /**
     * Busca todos os cupons
     */
    public function findAll() {
        $query = "SELECT * FROM cupons ORDER BY validade DESC";
        $result = $this->db->execute($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Busca cupons com filtro e ordenação
     */
    public function findFiltered($search = '', $orderBy = 'validade DESC') {
        $orderBy = $this->sanitizeOrderBy($orderBy);

        if (!empty($search)) {
            $query = "SELECT * FROM cupons WHERE codigo LIKE ? ORDER BY $orderBy";
            $result = $this->db->execute($query, ["%$search%"], 's');
        } else {
            $query = "SELECT * FROM cupons ORDER BY $orderBy";
            $result = $this->db->execute($query);
        }

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    private function sanitizeOrderBy($orderBy) {
        $allowed = [
            'validade DESC',
            'validade ASC',
            'valor_desconto DESC',
            'valor_desconto ASC',
        ];

        return in_array($orderBy, $allowed, true) ? $orderBy : 'validade DESC';
    }

    /**
     * Cria novo cupom
     */
    public function create($code, $discount, $minSubtotal, $validUntil) {
        $query = "INSERT INTO cupons (codigo, valor_desconto, minimo_subtotal, validade) 
                  VALUES (?, ?, ?, ?)";
        $this->db->executeUpdate($query, [$code, $discount, $minSubtotal, $validUntil], 'sdds');
        return $this->db->getLastInsertId();
    }

    /**
     * Atualiza cupom
     */
    public function update($code, $discount, $minSubtotal, $validUntil) {
        $query = "UPDATE cupons SET valor_desconto = ?, minimo_subtotal = ?, validade = ? 
                  WHERE codigo = ?";
        $this->db->executeUpdate($query, [$discount, $minSubtotal, $validUntil, $code], 'ddss');
    }

    /**
     * Deleta cupom
     */
    public function delete($code) {
        $query = "DELETE FROM cupons WHERE codigo = ?";
        return $this->db->executeUpdate($query, [$code], 's');
    }
}
