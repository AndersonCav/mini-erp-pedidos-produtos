<?php require '../app/views/shared/header.php'; ?>
<div class="container mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h3 class="card-title text-primary mb-4">📦 Gerenciar Estoque</h3>
            <form method="GET" class="card card-body shadow-sm mb-4">
                <input type="hidden" name="rota" value="estoque">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">🔍 Buscar por produto ou variação</label>
                        <input type="text" name="busca" class="form-control" placeholder="Ex: Xbox Series X" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">📊 Ordenar por</label>
                        <select name="ordem" class="form-select">
                            <option value="">Selecionar...</option>
                            <option value="nome_asc" <?= ($_GET['ordem'] ?? '') === 'nome_asc' ? 'selected' : '' ?>>Nome (A-Z)</option>
                            <option value="nome_desc" <?= ($_GET['ordem'] ?? '') === 'nome_desc' ? 'selected' : '' ?>>Nome (Z-A)</option>
                            <option value="qtd_asc" <?= ($_GET['ordem'] ?? '') === 'qtd_asc' ? 'selected' : '' ?>>Estoque (menor)</option>
                            <option value="qtd_desc" <?= ($_GET['ordem'] ?? '') === 'qtd_desc' ? 'selected' : '' ?>>Estoque (maior)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100">🔎 Filtrar</button>
                    </div>
                </div>
            </form>
            <div id="mensagem-status"></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle shadow-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Produto</th>
                            <th>Variação</th>
                            <th>Estoque Atual</th>
                            <th>Ajustar Estoque</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $item): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($item['nome']) ?></td>
                                <td><?= $item['variacao'] ?? '<span class="text-muted">Sem variação</span>' ?></td>
                                <td><span class="badge bg-secondary"><?= $item['quantidade'] ?></span></td>
                                <td>
                                    <form method="POST" action="index.php?rota=estoque_atualizar"onsubmit="return confirmarAtualizacao(this, '<?= htmlspecialchars($item['nome']) ?>', '<?= $item['variacao'] ?? 'Sem variação' ?>')"class="d-flex align-items-center">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(CsrfValidator::getToken()) ?>">
                                        <input type="hidden" name="produto_id" value="<?= $item['produto_id'] ?>">
                                        <input type="hidden" name="variacao_id" value="<?= $item['variacao_id'] ?? 0 ?>">
                                        <input type="number" name="quantidade" value="<?= $item['quantidade'] ?>" class="form-control form-control-sm me-2 shadow-sm rounded" style="width: 100px;">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_paginas > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagina_atual > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?rota=estoque&pagina=<?= $pagina_atual - 1 ?>&busca=<?= urlencode($_GET['busca'] ?? '') ?>&ordem=<?= urlencode($_GET['ordem'] ?? '') ?>">Anterior</a>
                            </li>
                        <?php endif ?>
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?= ($pagina_atual == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?rota=estoque&pagina=<?= $i ?>&busca=<?= urlencode($_GET['busca'] ?? '') ?>&ordem=<?= urlencode($_GET['ordem'] ?? '') ?>"><?= $i ?></a>
                            </li>
                        <?php endfor ?>
                        <?php if ($pagina_atual < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?rota=estoque&pagina=<?= $pagina_atual + 1 ?>&busca=<?= urlencode($_GET['busca'] ?? '') ?>&ordem=<?= urlencode($_GET['ordem'] ?? '') ?>">Próxima</a>
                            </li>
                        <?php endif ?>
                    </ul>
                </nav>
            <?php endif ?>
        </div>
    </div>
</div>
<script>
    function confirmarAtualizacao(form, produto, variacao) {
        const qtd = form.querySelector('[name="quantidade"]').value;
        const confirmado = confirm(`Tem certeza que deseja atualizar o estoque de "${produto} (${variacao})" para ${qtd} unidade(s)?`);
        const divMensagem = document.getElementById('mensagem-status');
        divMensagem.innerHTML = '';
        if (!confirmado) {
            divMensagem.innerHTML = `<div class="alert alert-warning mt-3">Alteração cancelada por você.</div>`;
            return false;
        }
        localStorage.setItem("estoqueAtualizado", `${produto} (${variacao}) atualizado para ${qtd} unidade(s).`);
        return true;
    }
    document.addEventListener("DOMContentLoaded", function () {
        const mensagem = localStorage.getItem("estoqueAtualizado");
        if (mensagem) {
            const div = document.getElementById('mensagem-status');
            div.innerHTML = `<div class="alert alert-success mt-3">${mensagem}</div>`;
            localStorage.removeItem("estoqueAtualizado");
        }
    });
</script>
<?php require '../app/views/shared/footer.php'; ?>