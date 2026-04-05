<?php
/**
 * ShippingService.php
 * Centraliza lógica de cálculo de frete
 */

class ShippingService {
    /**
     * Calcula frete com base no subtotal
     * Regras:
     * - Acima de R$200: Grátis
     * - Entre R$52 e R$166,59: R$15
     * - Outros: R$20
     */
    public static function calculateShipping($subtotal) {
        $rules = SHIPPING_RULES;

        if ($subtotal > $rules['subtotal_min_free']) {
            return 0.00;
        }

        if ($subtotal >= $rules['subtotal_min_reduced'] && $subtotal <= $rules['subtotal_max_reduced']) {
            return $rules['reduced_shipping'];
        }

        return $rules['standard_shipping'];
    }

    /**
     * Retorna descrição do frete
     */
    public static function getDescription($shipping) {
        if ($shipping == 0) {
            return 'Frete Grátis';
        }
        if ($shipping == SHIPPING_RULES['reduced_shipping']) {
            return 'Frete Reduzido';
        }
        return 'Frete Padrão';
    }
}
