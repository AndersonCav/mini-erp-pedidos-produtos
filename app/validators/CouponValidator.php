<?php
/**
 * CouponValidator.php
 * Validações para cupons
 */

class CouponValidator {
    /**
     * Valida dados de criação/atualização de cupom
     */
    public static function validate($code, $discount, $minSubtotal, $validUntil) {
        $errors = [];

        // Valida código
        if (empty($code)) {
            $errors['codigo'] = 'Código do cupom é obrigatório';
        } elseif (strlen($code) < 3 || strlen($code) > 50) {
            $errors['codigo'] = 'Código deve ter entre 3 e 50 caracteres';
        } elseif (!preg_match('/^[A-Z0-9\-]+$/i', $code)) {
            $errors['codigo'] = 'Código deve conter apenas letras, números e hífen';
        }

        // Valida desconto
        if ($discount === null || $discount === '') {
            $errors['valor_desconto'] = 'Valor do desconto é obrigatório';
        } elseif (!is_numeric($discount) || $discount <= 0) {
            $errors['valor_desconto'] = 'Desconto deve ser um valor maior que zero';
        } elseif ($discount > 99999.99) {
            $errors['valor_desconto'] = 'Desconto muito alto';
        }

        // Valida subtotal mínimo
        if ($minSubtotal === null || $minSubtotal === '') {
            $errors['minimo_subtotal'] = 'Subtotal mínimo é obrigatório';
        } elseif (!is_numeric($minSubtotal) || $minSubtotal < 0) {
            $errors['minimo_subtotal'] = 'Subtotal mínimo não pode ser negativo';
        }

        // Valida validade
        if (empty($validUntil)) {
            $errors['validade'] = 'Data de validade é obrigatória';
        } else {
            $validDate = strtotime($validUntil);
            if ($validDate === false) {
                $errors['validade'] = 'Data de validade inválida';
            } elseif ($validDate < time()) {
                $errors['validade'] = 'Data de validade deve ser no futuro';
            }
        }

        return $errors;
    }
}
