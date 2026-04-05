<?php require '../app/views/shared/header.php'; ?>
<div class="container mt-4">
    <h2>Revisar Pedido</h2>
    <?php
        $carrinho = $_SESSION['carrinho'] ?? [];
        $subtotal = 0;
        $productRepo = new ProductRepository();
        if (empty($carrinho)) {
            echo "<p>Seu carrinho está vazio.</p>";
            require '../app/views/shared/footer.php';
            exit;
        }
        foreach ($carrinho as $produto_id => $qtd) {
            $produto = $productRepo->findById((int) $produto_id);
            if (!$produto) {
                continue;
            }
            $subtotal += $produto['preco'] * $qtd;
        }
        // Calcular frete
        if ($subtotal > 200) {
            $frete = 0;
        } elseif ($subtotal >= 52 && $subtotal <= 166.59) {
            $frete = 15;
        } else {
            $frete = 20;
        }
        // Aplicar cupom (se já enviado via sessão ou POST)
        require_once '../app/models/Cupom.php';
        $cupom = $_SESSION['cupom'] ?? ($_POST['cupom'] ?? '');
        $desconto = 0;
        if ($cupom) {
            $cupomValido = Cupom::validar($cupom, $subtotal);
            if ($cupomValido) {
                $desconto = $cupomValido['valor_desconto'];
            } else {
                echo "<div class='alert alert-warning'>Cupom inválido ou expirado.</div>";
            }
        }
        $total = $subtotal + $frete - $desconto;
    ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Qtd</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($carrinho as $produto_id => $qtd):
                $produto = $productRepo->findById((int) $produto_id);
                if (!$produto) {
                    continue;
                }
            ?>
                <tr>
                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                    <td><?= $qtd ?></td>
                    <td>R$ <?= number_format($produto['preco'] * $qtd, 2, ',', '.') ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <ul class="list-group mb-3">
        <li class="list-group-item">Subtotal: R$ <?= number_format($subtotal, 2, ',', '.') ?></li>
        <li class="list-group-item">Frete: R$ <?= number_format($frete, 2, ',', '.') ?></li>
        <li class="list-group-item">Desconto: R$ <?= number_format($desconto, 2, ',', '.') ?></li>
        <li class="list-group-item active">Total a pagar: R$ <?= number_format($total, 2, ',', '.') ?></li>
    </ul>
    <form method="POST" action="index.php?rota=finalizar_pedido">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(CsrfValidator::getToken()) ?>">
        <input type="hidden" name="cep" value="<?= htmlspecialchars($_POST['cep'] ?? '') ?>">
        <input type="hidden" name="endereco" value="<?= htmlspecialchars($_POST['endereco'] ?? '') ?>">
        <input type="hidden" name="cupom" value="<?= htmlspecialchars($cupom) ?>">
        <button type="submit" class="btn btn-success">Confirmar Pedido</button>
        <a href="index.php?rota=carrinho" class="btn btn-secondary">Voltar ao Carrinho</a>
    </form>
</div>
<?php require '../app/views/shared/footer.php'; ?>