<?php
/**
 * Estoque.php
 * Adaptador de modelo para operações de estoque
 */

require_once __DIR__ . '/../services/StockService.php';

class Estoque {
    private static $service;

    private static function getService() {
        if (!self::$service) {
            self::$service = new StockService();
        }
        return self::$service;
    }

    /**
     * Reduz quantidade em estoque
     * Nota: Agora recebe variacao_id corretamente
     */
    public static function reduzir($produto_id, $quantidade, $variacao_id = null) {
        try {
            self::getService()->reserve($produto_id, $quantidade, $variacao_id);
        } catch (Exception $e) {
            Logger::error('Stock reduction error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifica se tem estoque
     */
    public static function temEstoque($produto_id, $quantidade, $variacao_id = null) {
        return self::getService()->isAvailable($produto_id, $quantidade, $variacao_id);
    }
}