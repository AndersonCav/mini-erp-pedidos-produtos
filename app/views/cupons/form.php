<?php require '../app/views/shared/header.php'; ?>
<div class="container mt-4">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body">
            <h3 class="card-title text-primary mb-4 d-flex align-items-center">
                <?= isset($cupom) ? '✏️ Editar Cupom' : '🎟️ Cadastrar Novo Cupom' ?>
            </h3>
            <?php if (!empty($_SESSION['mensagem'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['mensagem'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
                <?php unset($_SESSION['mensagem']); ?>
            <?php endif; ?>
            <form method="POST" action="index.php?rota=cupom_salvar">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(CsrfValidator::getToken()) ?>">
                <div class="mb-3">
                    <label class="form-label">🆔 Código do Cupom</label>
                    <input type="text" name="codigo" class="form-control shadow-sm" required
                        value="<?= htmlspecialchars($cupom['codigo'] ?? '') ?>"
                        <?= isset($cupom) ? 'readonly' : '' ?>>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">💰 Valor de Desconto (R$)</label>
                        <input type="number" step="0.01" name="valor_desconto" class="form-control shadow-sm" required
                            value="<?= htmlspecialchars($cupom['valor_desconto'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">📉 Subtotal Mínimo (R$)</label>
                        <input type="number" step="0.01" name="minimo_subtotal" class="form-control shadow-sm" required
                            value="<?= htmlspecialchars($cupom['minimo_subtotal'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">📅 Validade</label>
                    <input type="date" name="validade" class="form-control shadow-sm" required
                        value="<?= htmlspecialchars($cupom['validade'] ?? '') ?>">
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" class="btn btn-success">💾 Salvar Cupom</button>
                    <a href="index.php?rota=cupons_listar" class="btn btn-outline-secondary">↩️ Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require '../app/views/shared/footer.php'; ?>