<?php

class CupomController {
    private $couponService;
    private $couponRepository;

    public function __construct() {
        $this->couponService = new CouponService();
        $this->couponRepository = new CouponRepository();
    }

    public function form() {
        require '../app/views/cupons/form.php';
    }

    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            Response::redirect('index.php?rota=cupons_listar', 'Requisição inválida.', 'error');
        }

        $request = new Request();
        $codigo = strtoupper(trim($request->getString('codigo', '', true)));
        $desconto = $request->getFloat('valor_desconto', 0, true);
        $minSubtotal = $request->getFloat('minimo_subtotal', 0, true);
        $validade = $request->getString('validade', '', true);

        $errors = CouponValidator::validate($codigo, $desconto, $minSubtotal, $validade);
        if (!empty($errors)) {
            Response::redirect('index.php?rota=cupons', implode(' ', array_values($errors)), 'error');
        }

        try {
            $existing = $this->couponRepository->findByCode($codigo);
            if ($existing) {
                $this->couponService->update($codigo, $desconto, $minSubtotal, $validade);
            } else {
                $this->couponService->create($codigo, $desconto, $minSubtotal, $validade);
            }

            Response::redirect('index.php?rota=cupons_listar', 'Cupom salvo com sucesso!');
        } catch (Exception $e) {
            Logger::error('Coupon save failed: ' . $e->getMessage());
            Response::redirect('index.php?rota=cupons', 'Falha ao salvar cupom.', 'error');
        }
    }

    public function validar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            Response::json(['valido' => false, 'mensagem' => 'Token CSRF inválido.'], 403);
        }

        $request = new Request();
        $cupom = strtoupper(trim($request->getString('cupom', '')));
        $subtotal = $request->getFloat('subtotal', 0);

        $result = $this->couponService->validate($cupom, $subtotal);
        if ($result['valid']) {
            Response::json([
                'valido' => true,
                'desconto' => (float) $result['discount']
            ]);
        }

        Response::json([
            'valido' => false,
            'mensagem' => $result['message'] ?? 'Cupom inválido'
        ], 422);
    }

    public function listar() {
        $cupons = $this->couponService->getFiltered(
            trim((string) ($_GET['busca'] ?? '')),
            $this->resolveOrderBy((string) ($_GET['ordenar'] ?? ''))
        );
        require '../app/views/cupons/lista.php';
    }

    public function editar() {
        $codigo = strtoupper(trim((string) ($_GET['codigo'] ?? '')));
        if ($codigo === '') {
            Response::redirect('index.php?rota=cupons_listar', 'Cupom inválido.', 'error');
        }

        $cupom = $this->couponRepository->findByCode($codigo);
        if (!$cupom) {
            Response::redirect('index.php?rota=cupons_listar', 'Cupom não encontrado.', 'error');
        }

        require '../app/views/cupons/form.php';
    }

    public function excluir() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            Response::redirect('index.php?rota=cupons_listar', 'Requisição inválida.', 'error');
        }

        $codigo = strtoupper(trim((string) ($_POST['codigo'] ?? '')));
        if ($codigo === '') {
            Response::redirect('index.php?rota=cupons_listar', 'Cupom inválido.', 'error');
        }

        try {
            $this->couponService->delete($codigo);
            Response::redirect('index.php?rota=cupons_listar', 'Cupom excluído com sucesso!');
        } catch (Exception $e) {
            Logger::error('Coupon delete failed: ' . $e->getMessage());
            Response::redirect('index.php?rota=cupons_listar', 'Falha ao excluir cupom.', 'error');
        }
    }

    private function resolveOrderBy($order) {
        $map = [
            'valor_maior' => 'valor_desconto DESC',
            'valor_menor' => 'valor_desconto ASC',
            'validade_menor' => 'validade ASC',
            'validade_maior' => 'validade DESC',
        ];

        return $map[$order] ?? 'validade DESC';
    }
}