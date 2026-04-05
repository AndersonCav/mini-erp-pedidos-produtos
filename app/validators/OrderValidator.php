<?php
/**
 * OrderValidator.php
 * Validações para pedidos
 */

class OrderValidator {
    /**
     * Valida dados de finalização de pedido
     */
    public static function validateCheckout($cep, $address, $email = null) {
        $errors = [];

        // Valida CEP
        if (empty($cep)) {
            $errors['cep'] = 'CEP é obrigatório';
        } elseif (!self::isValidCep($cep)) {
            $errors['cep'] = 'CEP inválido';
        }

        // Valida endereço
        if (empty($address)) {
            $errors['endereco'] = 'Endereço é obrigatório';
        } elseif (strlen($address) < 10) {
            $errors['endereco'] = 'Endereço deve ter no mínimo 10 caracteres';
        } elseif (strlen($address) > 500) {
            $errors['endereco'] = 'Endereço muito longo';
        }

        // Valida email se fornecido
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        return $errors;
    }

    /**
     * Valida CEP brasileiro
     */
    private static function isValidCep($cep) {
        $cep = preg_replace('/\D/', '', $cep);
        return strlen($cep) === 8;
    }

    /**
     * Valida item do carrinho
     */
    public static function validateCartItem($productId, $quantity, $variationId = null) {
        $errors = [];

        if ($quantity <= 0) {
            $errors['quantidade'] = 'Quantidade deve ser maior que zero';
        }

        if ($quantity > 9999) {
            $errors['quantidade'] = 'Quantidade muito alta';
        }

        return $errors;
    }

    /**
     * Valida alteração de status
     */
    public static function validateStatusChange($oldStatus, $newStatus) {
        // Estados válidos
        if (!in_array($newStatus, VALID_ORDER_STATUSES)) {
            return ['status' => 'Status inválido'];
        }

        // Transições proibidas (opcional - adicionar lógica conforme necessário)
        // var $invalidTransitions = [
        //     'entregue' => ['pendente', 'processando'],
        //     'cancelado' => ['entregue']
        // ];

        return [];
    }
}
