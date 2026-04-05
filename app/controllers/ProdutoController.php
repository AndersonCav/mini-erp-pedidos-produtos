<?php

class ProdutoController {
    private $productRepository;
    private $variationRepository;
    private $stockRepository;

    public function __construct() {
        $this->productRepository = new ProductRepository();
        $this->variationRepository = new VariationRepository();
        $this->stockRepository = new StockRepository();
    }

    public function index() {
        require_once '../app/models/Produto.php';

        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $busca = trim((string) ($_GET['busca'] ?? ''));
        $ordenar = trim((string) ($_GET['ordenar'] ?? 'nome_asc'));
        $limite = DEFAULT_PRODUCTS_PER_PAGE;
        $offset = ($pagina - 1) * $limite;

        $produtos = Produto::paginar($limite, $offset, $ordenar, $busca);
        $variacoesPorProduto = [];
        foreach ($produtos as $produto) {
            $variacoesPorProduto[$produto['id']] = $this->variationRepository->findByProductId((int) $produto['id']);
        }
        $total_registros = Produto::contarTodos($busca);
        $total_paginas = (int) ceil($total_registros / $limite);
        $pagina_atual = $pagina;

        require '../app/views/produtos/lista.php';
    }

    public function form() {
        require '../app/views/produtos/form.php';
    }

    public function salvar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            Response::redirect('index.php?rota=produtos', 'Requisição inválida.', 'error');
        }

        require_once '../app/models/Produto.php';
        $dados = $_POST;
        $dados['imagem_url'] = $this->handleImageUpload($_POST['imagem_url'] ?? '');

        $errors = ProductValidator::validate(
            $dados['nome'] ?? '',
            $dados['preco'] ?? null,
            $dados['imagem_url'] ?? '',
            $dados['variacoes'] ?? []
        );

        $fileErrors = ProductValidator::validateImageFile($_FILES['imagem_arquivo'] ?? []);
        $errors = array_merge($errors, $fileErrors);

        if (!empty($errors)) {
            Response::redirect('index.php?rota=produto_form', implode(' ', array_values($errors)), 'error');
        }

        Produto::salvar($dados);
        Response::redirect('index.php?rota=produtos', 'Produto salvo com sucesso!');
    }

    public function editar() {
        $id = max(0, (int) ($_GET['id'] ?? 0));
        if ($id <= 0) {
            Response::redirect('index.php?rota=produtos', 'Produto inválido.', 'error');
        }

        $produto = $this->productRepository->findById($id);
        if (!$produto) {
            Response::redirect('index.php?rota=produtos', 'Produto não encontrado.', 'error');
        }

        $variacoes = $this->variationRepository->findByProductId($id);
        $estoques = [];
        foreach ($variacoes as $v) {
            $estoques[$v['id']] = $this->stockRepository->findQuantity($id, (int) $v['id']);
        }

        $estoque_simples = $this->stockRepository->findQuantity($id, null);

        require '../app/views/produtos/form.php';
    }

    public function atualizar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            Response::redirect('index.php?rota=produtos', 'Requisição inválida.', 'error');
        }

        require_once '../app/models/Produto.php';
        $dados = $_POST;
        $dados['imagem_url'] = $this->handleImageUpload($_POST['imagem_url'] ?? '');

        $errors = ProductValidator::validate(
            $dados['nome'] ?? '',
            $dados['preco'] ?? null,
            $dados['imagem_url'] ?? '',
            $dados['variacoes'] ?? []
        );

        if (!empty($errors)) {
            $id = (int) ($dados['id'] ?? 0);
            Response::redirect('index.php?rota=produto_editar&id=' . $id, implode(' ', array_values($errors)), 'error');
        }

        Produto::atualizar($dados);
        Response::redirect('index.php?rota=produtos', 'Produto atualizado com sucesso!');
    }

    public function excluir() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            Response::redirect('index.php?rota=produtos', 'Requisição inválida.', 'error');
        }

        require_once '../app/models/Produto.php';
        $id = max(0, (int) ($_POST['id'] ?? 0));
        if ($id <= 0) {
            Response::redirect('index.php?rota=produtos', 'Produto inválido.', 'error');
        }

        Produto::excluir($id);
        Response::redirect('index.php?rota=produtos', 'Produto excluído com sucesso!');
    }

    public function excluirVariacao() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            Response::redirect('index.php?rota=produtos', 'Requisição inválida.', 'error');
        }

        $id = max(0, (int) ($_POST['id'] ?? 0));
        if ($id <= 0) {
            Response::redirect('index.php?rota=produtos', 'Variação inválida.', 'error');
        }

        $this->variationRepository->delete($id);
        Response::redirect('index.php?rota=produtos', 'Variação excluída com sucesso!');
    }

    private function handleImageUpload($fallbackUrl = '') {
        $imagemUrl = trim((string) $fallbackUrl);

        if (empty($_FILES['imagem_arquivo']['tmp_name'])) {
            return $imagemUrl;
        }

        $nomeTmp = $_FILES['imagem_arquivo']['tmp_name'];
        $ext = strtolower((string) pathinfo($_FILES['imagem_arquivo']['name'] ?? '', PATHINFO_EXTENSION));
        $nomeFinal = uniqid('img_', true) . '.' . $ext;
        $destinoDir = 'public/uploads';
        $destino = $destinoDir . '/' . $nomeFinal;

        if (!is_dir($destinoDir)) {
            mkdir($destinoDir, 0755, true);
        }

        if (move_uploaded_file($nomeTmp, $destino)) {
            return $destino;
        }

        return $imagemUrl;
    }
}