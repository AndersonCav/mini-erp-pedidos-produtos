<?php

class EstoqueController {
    private $stockService;

    public function __construct() {
        $this->stockService = new StockService();
    }

    public function index() {
        $busca = trim((string) ($_GET['busca'] ?? ''));
        $ordem = trim((string) ($_GET['ordem'] ?? ''));
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $por_pagina = DEFAULT_PAGE_LIMIT;
        $offset = ($pagina - 1) * $por_pagina;

        $orderMap = [
            'nome_asc' => 'p.nome ASC',
            'nome_desc' => 'p.nome DESC',
            'qtd_asc' => 'e.quantidade ASC',
            'qtd_desc' => 'e.quantidade DESC',
        ];

        $orderBy = $orderMap[$ordem] ?? 'p.nome ASC';

        $produtos = $this->stockService->findPaginated($por_pagina, $offset, $orderBy, $busca);
        $total_resultado = $this->stockService->count($busca);
        $total_paginas = (int) ceil($total_resultado / $por_pagina);
        $pagina_atual = $pagina;

        require '../app/views/estoque/index.php';
    }

    public function atualizar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            Response::redirect('index.php?rota=estoque', 'Requisição inválida.', 'error');
        }

        $request = new Request();
        $produtoId = $request->getInt('produto_id', 0, true);
        $variacaoRaw = $request->getString('variacao_id', '');
        $variacaoId = ($variacaoRaw === '' || $variacaoRaw === '0') ? null : (int) $variacaoRaw;
        $quantidade = $request->getInt('quantidade', -1, true);

        if ($produtoId <= 0 || $quantidade < 0) {
            Response::redirect('index.php?rota=estoque', 'Dados de estoque inválidos.', 'error');
        }

        try {
            $this->stockService->setQuantity($produtoId, $quantidade, $variacaoId);
            Response::redirect('index.php?rota=estoque', 'Estoque atualizado com sucesso!');
        } catch (Exception $e) {
            Logger::error('Stock update failed: ' . $e->getMessage());
            Response::redirect('index.php?rota=estoque', 'Falha ao atualizar estoque.', 'error');
        }
    }
}