<?php require '../app/views/shared/header.php'; ?>
<div class="container mt-4">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body">
            <h3 class="card-title mb-4 text-primary d-flex align-items-center">
                🛒 <?= isset($produto['id']) ? 'Editar Produto' : 'Cadastro de Produto' ?>
            </h3>
            <form method="POST" action="index.php?rota=<?= isset($produto['id']) ? 'produto_atualizar' : 'produto_salvar' ?>" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(CsrfValidator::getToken()) ?>">
                <?php if (!empty($produto['id'])): ?>
                    <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Nome:</label>
                    <input type="text" name="nome" class="form-control shadow-sm" required value="<?= htmlspecialchars($produto['nome'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Preço:</label>
                    <input type="number" step="0.01" name="preco" class="form-control shadow-sm" required value="<?= htmlspecialchars($produto['preco'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label">Variações (opcional):</label>
                    <?php if (!empty($variacoes)): ?>
                        <?php foreach ($variacoes as $index => $v): ?>
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-md-6">
                                    <input type="text" name="variacoes[]" class="form-control shadow-sm" value="<?= htmlspecialchars($v['nome']) ?>" placeholder="Ex: Tamanho M">
                                </div>
                                <div class="col-md-6">
                                    <input type="number" name="estoques[]" class="form-control shadow-sm" placeholder="Estoque" value="<?= htmlspecialchars($estoques[$v['id']] ?? '') ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="row g-2 align-items-center mb-2">
                            <div class="col-md-6">
                                <input type="text" name="variacoes[]" class="form-control shadow-sm" placeholder="Ex: Tamanho M">
                            </div>
                            <div class="col-md-6">
                                <input type="number" name="estoques[]" class="form-control shadow-sm" placeholder="Estoque">
                            </div>
                        </div>
                    <?php endif; ?>
                    <button type="button" id="addVariacao" class="btn btn-outline-primary btn-sm">➕ Adicionar Variação</button>
                </div>
                <div class="mb-3">
                    <label class="form-label">Estoque (caso não use variação):</label>
                    <input type="number" name="estoque" class="form-control shadow-sm" value="<?= htmlspecialchars($estoque_simples ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Imagem do Produto:</label>
                    <input type="url" name="imagem_url" class="form-control shadow-sm mb-2" placeholder="URL da imagem (https://...)" value="<?= htmlspecialchars($produto['imagem_url'] ?? '') ?>">
                    <input type="file" name="imagem_arquivo" class="form-control shadow-sm">
                    <small class="form-text text-muted">Se você selecionar um arquivo, ele será usado no lugar do link acima.</small>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" class="btn btn-success">💾 Salvar Produto</button>
                    <a href="index.php?rota=produtos" class="btn btn-secondary">↩️ Voltar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.getElementById('addVariacao').addEventListener('click', function () {
        const container = document.createElement('div');
        container.classList.add('row', 'g-2', 'align-items-center', 'mb-2');
        container.innerHTML = `
            <div class="col-md-6">
                <input type="text" name="variacoes[]" class="form-control shadow-sm" placeholder="Ex: Tamanho M">
            </div>
            <div class="col-md-6">
                <input type="number" name="estoques[]" class="form-control shadow-sm" placeholder="Estoque">
            </div>
        `;
        this.before(container);
    });
</script>
<?php require '../app/views/shared/footer.php'; ?>