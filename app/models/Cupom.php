<?php
/**
 * Cupom.php (REFATORADO)
 * Model mantido por compatibilidade, delega para Repository + Service
 */

require_once __DIR__ . '/../repositories/CouponRepository.php';
require_once __DIR__ . '/../services/CouponService.php';

class Cupom {
    private static $service;

    private static function getService() {
        if (!self::$service) {
            self::$service = new CouponService();
        }
        return self::$service;
    }

    /**
     * Salva/atualiza cupom
     */
    public static function salvar($dados) {
        try {
            $repo = new CouponRepository();
            $codigo = $dados['codigo'];

            // Verifica se já existe
            $existing = $repo->findByCode($codigo);

            if ($existing) {
                $repo->update(
                    $codigo,
                    floatval($dados['valor_desconto']),
                    floatval($dados['minimo_subtotal']),
                    $dados['validade']
                );
            } else {
                $repo->create(
                    $codigo,
                    floatval($dados['valor_desconto']),
                    floatval($dados['minimo_subtotal']),
                    $dados['validade']
                );
            }

            Logger::info("Coupon saved: $codigo");
        } catch (Exception $e) {
            Logger::error('Coupon save error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Valida cupom com desconto
     */
    public static function validar($codigo, $subtotal) {
        try {
            $result = self::getService()->validate($codigo, $subtotal);
            if ($result['valid']) {
                return $result['coupon'];
            }
            return null;
        } catch (Exception $e) {
            Logger::error('Coupon validation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca todos os cupons com filtros
     */
    public static function todos() {
        try {
            $search = $_GET['busca'] ?? '';
            $orderBy = 'validade DESC';

            if (!empty($_GET['ordenar'])) {
                switch ($_GET['ordenar']) {
                    case 'valor_maior':
                        $orderBy = 'valor_desconto DESC';
                        break;
                    case 'valor_menor':
                        $orderBy = 'valor_desconto ASC';
                        break;
                    case 'validade_maior':
                        $orderBy = 'validade DESC';
                        break;
                    case 'validade_menor':
                        $orderBy = 'validade ASC';
                        break;
                }
            }

            return (new CouponRepository())->findFiltered($search, $orderBy);
        } catch (Exception $e) {
            Logger::error('Coupon list error: ' . $e->getMessage());
            return [];
        }
    }
}