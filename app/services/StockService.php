<?php
/**
 * StockService.php
 * Serviço de gerenciamento de estoque
 */

class StockService {
    private $repository;

    public function __construct() {
        $this->repository = new StockRepository();
    }

    /**
     * Verifica disponibilidade
     */
    public function getAvailability($productId, $variationId = null) {
        return $this->repository->findQuantity($productId, $variationId);
    }

    /**
     * Verifica se há quantidade suficiente
     */
    public function isAvailable($productId, $quantity, $variationId = null) {
        return $this->repository->hasEnoughStock($productId, $quantity, $variationId);
    }

    /**
     * Reduz estoque (usar em checkout)
     */
    public function reserve($productId, $quantity, $variationId = null) {
        try {
            $this->repository->decreaseStock($productId, $quantity, $variationId);
            return true;
        } catch (Exception $e) {
            Logger::error('Stock reservation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Aumenta estoque (usar em devolução/cancelamento)
     */
    public function release($productId, $quantity, $variationId = null) {
        try {
            $this->repository->increaseStock($productId, $quantity, $variationId);
            return true;
        } catch (Exception $e) {
            Logger::error('Stock release failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Define quantidade exata (administrativo)
     */
    public function setQuantity($productId, $quantity, $variationId = null) {
        if ($quantity < 0) {
            throw new Exception('Quantidade não pode ser negativa');
        }
        $this->repository->setQuantity($productId, $quantity, $variationId);
    }

    /**
     * Cria novo registro de estoque
     */
    public function create($productId, $quantity, $variationId = null) {
        return $this->repository->create($productId, $quantity, $variationId);
    }

    /**
     * Busca estoques com filtros e paginação
     */
    public function findPaginated($limit = 10, $offset = 0, $orderBy = 'p.nome ASC', $search = '') {
        return $this->repository->findPaginated($limit, $offset, $orderBy, $search);
    }

    /**
     * Conta total de estoques
     */
    public function count($search = '') {
        return $this->repository->countAll($search);
    }
}
