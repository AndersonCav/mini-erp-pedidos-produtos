<?php

class PedidoController {
    private $orderService;
    private $productRepository;
    private $stockService;

    public function __construct() {
        $this->orderService = new OrderService();
        $this->productRepository = new ProductRepository();
        $this->stockService = new StockService();
    }

    public function carrinho() {
        $cart = $_SESSION[CART_SESSION_KEY] ?? [];
        $cartDetails = [];
        $subtotal = 0;
        $variationRepository = new VariationRepository();

        foreach ($cart as $key => $qtd) {
            $parts = explode(':', (string) $key);
            $produtoId = (int) ($parts[0] ?? 0);
            $variacaoId = isset($parts[1]) ? (int) $parts[1] : null;

            $produto = $this->productRepository->findById($produtoId);
            if (!$produto) {
                continue;
            }

            $nomeProduto = $produto['nome'];
            if ($variacaoId !== null) {
                $variacao = $variationRepository->findById($variacaoId);
                if ($variacao) {
                    $nomeProduto .= ' - ' . $variacao['nome'];
                }
            }

            $preco = (float) $produto['preco'];
            $estoque = $this->stockService->getAvailability($produtoId, $variacaoId);
            $totalLinha = $preco * (int) $qtd;
            $subtotal += $totalLinha;

            $cartDetails[] = [
                'key' => $key,
                'produto_id' => $produtoId,
                'variacao_id' => $variacaoId,
                'nome' => $nomeProduto,
                'preco' => $preco,
                'quantidade' => (int) $qtd,
                'estoque' => (int) $estoque,
                'total' => $totalLinha,
            ];
        }

        require '../app/views/pedidos/carrinho.php';
    }

    public function adicionar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            $_SESSION['mensagem'] = 'Requisição inválida.';
            Response::redirect('index.php?rota=produtos');
        }

        $request = new Request();
        $produtoId = $request->getInt('produto_id', 0, true);
        $quantidade = $request->getInt('quantidade', 1, true);
        $variacaoRaw = $request->getString('variacao_id', '');
        $variacaoId = $variacaoRaw === '' ? null : max(0, (int) $variacaoRaw);

        if ($produtoId <= 0 || $quantidade <= 0) {
            $_SESSION['mensagem'] = 'Dados de item inválidos.';
            Response::redirect('index.php?rota=produtos');
        }

        $produto = $this->productRepository->findById($produtoId);
        if (!$produto) {
            $_SESSION['mensagem'] = 'Produto não encontrado.';
            Response::redirect('index.php?rota=produtos');
        }

        $chave = $variacaoId ? "{$produtoId}:{$variacaoId}" : (string) $produtoId;
        $qtdAtual = (int) ($_SESSION[CART_SESSION_KEY][$chave] ?? 0);
        $qtdNova = $qtdAtual + $quantidade;

        if (!$this->stockService->isAvailable($produtoId, $qtdNova, $variacaoId ?: null)) {
            $_SESSION['mensagem'] = 'Estoque insuficiente para esta quantidade.';
            Response::redirect('index.php?rota=produtos');
        }

        $_SESSION[CART_SESSION_KEY][$chave] = $qtdNova;
        Response::redirect('index.php?rota=carrinho');
    }

    public function atualizarQuantidade() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            Response::json(['success' => false, 'message' => 'Requisição inválida'], 403);
        }

        $request = new Request();
        $itemKey = $request->getString('item', '', true);
        $quantidade = max(1, $request->getInt('quantidade', 1, true));

        if (empty($itemKey) || !isset($_SESSION[CART_SESSION_KEY][$itemKey])) {
            Response::json(['success' => false, 'message' => 'Item não encontrado no carrinho'], 404);
        }

        $parts = explode(':', $itemKey);
        $produtoId = (int) ($parts[0] ?? 0);
        $variacaoId = isset($parts[1]) ? (int) $parts[1] : null;

        if (!$this->stockService->isAvailable($produtoId, $quantidade, $variacaoId)) {
            Response::json(['success' => false, 'message' => 'Quantidade acima do estoque'], 422);
        }

        $_SESSION[CART_SESSION_KEY][$itemKey] = $quantidade;
        Response::json(['success' => true, 'message' => 'Quantidade atualizada']);
    }

    public function finalizar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            $_SESSION['mensagem'] = 'Requisição inválida.';
            Response::redirect('index.php?rota=carrinho');
        }

        $cart = $_SESSION[CART_SESSION_KEY] ?? [];
        if (empty($cart)) {
            $_SESSION['mensagem'] = 'Seu carrinho está vazio.';
            Response::redirect('index.php?rota=carrinho');
        }

        $request = new Request();
        $email = $request->getString('email', '');
        $cep = $request->getString('cep', '');
        $endereco = $request->getString('endereco', '');
        $cupom = $request->getString('cupom', '');

        $errors = OrderValidator::validateCheckout($cep, $endereco, $email);
        if (!empty($errors)) {
            $_SESSION['mensagem'] = implode(' ', array_values($errors));
            Response::redirect('index.php?rota=carrinho');
        }

        $result = $this->orderService->create(
            $cart,
            $cep,
            $endereco,
            'pendente',
            $cupom !== '' ? $cupom : null,
            $email !== '' ? $email : null
        );

        if (!$result['success']) {
            $_SESSION['mensagem'] = $result['message'] ?? 'Não foi possível finalizar o pedido.';
            Response::redirect('index.php?rota=carrinho');
        }

        if (!empty($email)) {
            EmailService::sendOrderConfirmation(
                $email,
                (int) $result['orderId'],
                $result['productsText'] ?? '',
                (float) $result['subtotal'],
                (float) $result['shipping'],
                (float) $result['discount'],
                (float) $result['total'],
                $endereco,
                $cep
            );
        }

        unset($_SESSION[CART_SESSION_KEY]);
        require '../app/views/pedidos/sucesso.php';
    }

    public function remover() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            $_SESSION['mensagem'] = 'Requisição inválida.';
            Response::redirect('index.php?rota=carrinho');
        }

        $chave = $_POST['item'] ?? null;
        if ($chave && isset($_SESSION[CART_SESSION_KEY][$chave])) {
            unset($_SESSION[CART_SESSION_KEY][$chave]);
            $_SESSION[MESSAGE_SESSION_KEY] = 'Item removido com sucesso!';
        }
        Response::redirect('index.php?rota=carrinho');
    }

    public function limpar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            $_SESSION['mensagem'] = 'Requisição inválida.';
            Response::redirect('index.php?rota=carrinho');
        }
        unset($_SESSION[CART_SESSION_KEY]);
        $_SESSION[MESSAGE_SESSION_KEY] = 'Carrinho esvaziado com sucesso!';
        Response::redirect('index.php?rota=carrinho');
    }

    public function lista() {
        $busca = trim((string) ($_GET['busca'] ?? ''));
        $status = strtolower(trim((string) ($_GET['status'] ?? '')));
        $ordem = trim((string) ($_GET['ordem'] ?? 'mais_novo'));
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $limite = DEFAULT_PAGE_LIMIT;

        $orderByMap = [
            'mais_novo' => 'criado_em DESC',
            'mais_antigo' => 'criado_em ASC',
            'maior_valor' => 'total DESC',
            'menor_valor' => 'total ASC',
        ];

        $orderBy = $orderByMap[$ordem] ?? 'criado_em DESC';

        $result = $this->orderService->getFiltered($busca, $status, $orderBy, $pagina, $limite);
        $pedidos = $result['orders'];
        $total_paginas = $result['totalPages'];
        $pagina_atual = $pagina;

        require '../app/views/pedidos/lista.php';
    }

    public function alterarStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfValidator::validate()) {
            $_SESSION['mensagem'] = 'Requisição inválida.';
            Response::redirect('index.php?rota=pedidos');
        }

        $request = new Request();
        $id = $request->getInt('pedido_id', 0, true);
        $novoStatus = strtolower($request->getString('status', '', true));

        if ($id <= 0) {
            $_SESSION['mensagem'] = 'Pedido inválido.';
            Response::redirect('index.php?rota=pedidos');
        }

        $statusErrors = OrderValidator::validateStatusChange('', $novoStatus);
        if (!empty($statusErrors)) {
            $_SESSION['mensagem'] = implode(' ', array_values($statusErrors));
            Response::redirect('index.php?rota=pedidos');
        }

        $result = $this->orderService->updateStatus($id, $novoStatus);
        $_SESSION['mensagem'] = $result['message'] ?? 'Status atualizado.';
        Response::redirect('index.php?rota=pedidos');
    }
}