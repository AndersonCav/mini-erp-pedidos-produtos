<?php
/**
 * Pedido.php (REFATORADO)
 * Model mantido por compatibilidade, delega para OrderService + OrderRepository
 */

require_once __DIR__ . '/../services/OrderService.php';
require_once __DIR__ . '/../services/EmailService.php';

class Pedido {
    private static $service;

    private static function getService() {
        if (!self::$service) {
            self::$service = new OrderService();
        }
        return self::$service;
    }

    /**
     * Cria novo pedido (usa nova lógica centralizada em OrderService)
     */
    public static function criar($carrinho, $cep, $endereco, $cupom = null) {
        try {
            $result = self::getService()->create($carrinho, $cep, $endereco, 'pendente', $cupom);
            if ($result['success']) {
                return $result['orderId'];
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            Logger::error('Order creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Remove/deleta pedido
     */
    public static function remover($id) {
        try {
            self::getService()->delete($id);
        } catch (Exception $e) {
            Logger::error('Order deletion error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualiza status do pedido
     */
    public static function atualizarStatus($id, $status) {
        try {
            self::getService()->updateStatus($id, $status);
        } catch (Exception $e) {
            Logger::error('Order status update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envia email de confirmação
     */
    public static function enviarEmail($pedido_id, $email) {
        try {
            $repo = new OrderRepository();
            $pedido = $repo->findById($pedido_id);

            if (!$pedido) {
                throw new Exception('Pedido não encontrado');
            }

            return EmailService::sendOrderConfirmation(
                $email,
                $pedido_id,
                $pedido['produtos_texto'],
                $pedido['subtotal'],
                $pedido['frete'],
                $pedido['desconto'] ?? 0,
                $pedido['total'],
                $pedido['endereco'],
                $pedido['cep']
            );
        } catch (Exception $e) {
            Logger::error('Email sending error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca todos os pedidos
     */
    public static function todos($filtros = []) {
        try {
            $busca = $filtros['busca'] ?? '';
            $status = $filtros['status'] ?? '';
            $orderBy = 'criado_em DESC';

            if (!empty($filtros['ordem'])) {
                switch ($filtros['ordem']) {
                    case 'mais_novo': $orderBy = 'criado_em DESC'; break;
                    case 'mais_antigo': $orderBy = 'criado_em ASC'; break;
                    case 'maior_valor': $orderBy = 'total DESC'; break;
                    case 'menor_valor': $orderBy = 'total ASC'; break;
                }
            }

            $result = self::getService()->getFiltered($busca, $status, $orderBy, 1, 9999);
            return $result['orders'];
        } catch (Exception $e) {
            Logger::error('Orders list error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca pedidos com filtros e paginação
     */
    public static function todosFiltrado($busca = '', $status = '', $ordenar = '', $pagina = 1, $por_pagina = 10, &$total_paginas = 1) {
        try {
            $orderBy = 'criado_em DESC';
            if ($ordenar === 'maior_valor') {
                $orderBy = 'total DESC';
            } elseif ($ordenar === 'menor_valor') {
                $orderBy = 'total ASC';
            } elseif ($ordenar === 'mais_antigo') {
                $orderBy = 'criado_em ASC';
            } elseif ($ordenar === 'mais_novo') {
                $orderBy = 'criado_em DESC';
            }

            $result = self::getService()->getFiltered($busca, $status, $orderBy, $pagina, $por_pagina);

            $total_paginas = $result['totalPages'];

            return [
                'dados' => $result['orders'],
                'total_paginas' => $result['totalPages']
            ];
        } catch (Exception $e) {
            Logger::error('Orders pagination error: ' . $e->getMessage());
            return ['dados' => [], 'total_paginas' => 1];
        }
    }

    /**
     * Alter status (alias para atualizarStatus)
     */
    public static function alterarStatus($id, $novo_status) {
        self::atualizarStatus($id, $novo_status);
    }

    /**
     * Exclui pedido (alias para remover)
     */
    public static function excluir($id) {
        self::remover($id);
    }
}