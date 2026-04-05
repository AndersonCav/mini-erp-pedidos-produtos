<?php
/**
 * CouponService.php
 * Serviço de validação e gerenciamento de cupons
 */

class CouponService {
    private $repository;

    public function __construct() {
        $this->repository = new CouponRepository();
    }

    /**
     * Valida cupom com regras de negócio
     */
    public function validate($code, $subtotal) {
        if (empty($code)) {
            return ['valid' => false, 'message' => 'Cupom não informado'];
        }

        $coupon = $this->repository->findByCode($code);

        if (!$coupon) {
            return ['valid' => false, 'message' => 'Cupom não encontrado'];
        }

        // Verifica validade
        if (strtotime($coupon['validade']) < time()) {
            return ['valid' => false, 'message' => 'Cupom expirado'];
        }

        // Verifica subtotal mínimo
        if ($subtotal < $coupon['minimo_subtotal']) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Subtotal mínimo para este cupom é R$ %.2f',
                    $coupon['minimo_subtotal']
                )
            ];
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => floatval($coupon['valor_desconto'])
        ];
    }

    /**
     * Cria novo cupom
     */
    public function create($code, $discount, $minSubtotal, $validUntil) {
        // Validações básicas
        if (empty($code)) {
            throw new Exception('Código do cupom é obrigatório');
        }

        if ($discount <= 0) {
            throw new Exception('Desconto deve ser maior que zero');
        }

        if ($minSubtotal < 0) {
            throw new Exception('Subtotal mínimo não pode ser negativo');
        }

        if (strtotime($validUntil) < time()) {
            throw new Exception('Data de validade deve ser no futuro');
        }

        // Verifica se cupom já existe
        if ($this->repository->findByCode($code)) {
            throw new Exception('Cupom já existe. Use update() para modificar.');
        }

        return $this->repository->create($code, $discount, $minSubtotal, $validUntil);
    }

    /**
     * Atualiza cupom
     */
    public function update($code, $discount, $minSubtotal, $validUntil) {
        $coupon = $this->repository->findByCode($code);
        if (!$coupon) {
            throw new Exception('Cupom não encontrado');
        }

        return $this->repository->update($code, $discount, $minSubtotal, $validUntil);
    }

    /**
     * Deleta cupom
     */
    public function delete($code) {
        return $this->repository->delete($code);
    }

    /**
     * Lista todos os cupons
     */
    public function getAll() {
        return $this->repository->findAll();
    }

    /**
     * Lista cupons com filtros
     */
    public function getFiltered($search = '', $orderBy = 'validade DESC') {
        return $this->repository->findFiltered($search, $orderBy);
    }
}
