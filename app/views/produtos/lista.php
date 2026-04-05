<?php require '../app/views/shared/header.php'; ?>
<div class="container mt-4">
    <?php
        $totalProdutos = count($produtos ?? []);
        $somaPrecos = 0;
        if (!empty($produtos)) {
            foreach ($produtos as $item) {
                $somaPrecos += (float) ($item['preco'] ?? 0);
            }
        }
        $ticketMedio = $totalProdutos > 0 ? $somaPrecos / $totalProdutos : 0;
        $catalogoAltoValor = 0;
        if (!empty($produtos)) {
            foreach ($produtos as $item) {
                if ((float) ($item['preco'] ?? 0) >= 1000) {
                    $catalogoAltoValor++;
                }
            }
        }
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <span class="text-muted">Produtos na listagem</span>
                    <h4 class="mb-0"><?= $totalProdutos ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <span class="text-muted">Preço médio do catálogo</span>
                    <h4 class="mb-0">R$ <?= number_format($ticketMedio, 2, ',', '.') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <span class="text-muted">Itens premium (>= R$ 1.000)</span>
                    <h4 class="mb-0"><?= $catalogoAltoValor ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="text-primary">Produtos</h2>
        <a href="index.php?rota=produto_form" class="btn btn-success">➕ Novo Produto</a>
    </div>
    <form class="card card-body shadow-sm mb-4" method="GET" action="index.php">
        <input type="hidden" name="rota" value="produtos">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">🔍 Buscar por nome</label>
                <input type="text" name="busca" class="form-control" placeholder="Ex: Halo" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">📊 Ordenar por</label>
                <select name="ordenar" class="form-select">
                    <option value="nome_asc" <?= ($_GET['ordenar'] ?? '') === 'nome_asc' ? 'selected' : '' ?>>Nome A-Z</option>
                    <option value="nome_desc" <?= ($_GET['ordenar'] ?? '') === 'nome_desc' ? 'selected' : '' ?>>Nome Z-A</option>
                    <option value="preco_asc" <?= ($_GET['ordenar'] ?? '') === 'preco_asc' ? 'selected' : '' ?>>Preço crescente</option>
                    <option value="preco_desc" <?= ($_GET['ordenar'] ?? '') === 'preco_desc' ? 'selected' : '' ?>>Preço decrescente</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary">🔎 Filtrar</button>
            </div>
            <div class="col-md-1 d-grid">
                <a href="index.php?rota=produtos" class="btn btn-outline-secondary">↺</a>
            </div>
        </div>
    </form>
    <?php if (!empty($produtos)) : ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($produtos as $produto): ?>
                <?php $variacoes = $variacoesPorProduto[$produto['id']] ?? []; ?>
                <div class="col">
                    <div class="card shadow-sm border-0 h-100">
                        <?php if (!empty($produto['imagem_url'])): ?>
                            <img src="<?= htmlspecialchars($produto['imagem_url']) ?>" class="card-img-top" alt="Imagem do produto" style="object-fit: cover; height: 180px;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 180px;">
                                <span class="text-muted">Sem imagem</span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary"><?= htmlspecialchars($produto['nome']) ?></h5>
                            <p class="card-text fw-semibold mb-2">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                            <form action="index.php?rota=adicionar_carrinho" method="POST" class="mb-2">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(CsrfValidator::getToken()) ?>">
                                <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                                <?php if (!empty($variacoes)): ?>
                                    <select name="variacao_id" class="form-select form-select-sm mb-2">
                                        <?php foreach ($variacoes as $v): ?>
                                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nome']) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                <?php endif ?>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="number" name="quantidade" value="1" min="1" class="form-control" style="max-width: 70px;">
                                    <button class="btn btn-success" type="submit">Comprar</button>
                                </div>
                            </form>
                            <div class="d-flex flex-wrap gap-2 mt-auto">
                                <a href="index.php?rota=produto_editar&id=<?= $produto['id'] ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                                <?php if (!empty($variacoes)): ?>
                                    <form method="POST" action="index.php?rota=variacao_excluir" onsubmit="return confirm('Deseja mesmo excluir esta variação?')">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(CsrfValidator::getToken()) ?>">
                                        <input type="hidden" name="rota" value="variacao_excluir">
                                        <div class="input-group input-group-sm">
                                            <select name="id" class="form-select">
                                                <?php foreach ($variacoes as $v): ?>
                                                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nome']) ?></option>
                                                <?php endforeach ?>
                                            </select>
                                            <button class="btn btn-outline-danger">Excluir</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="index.php?rota=produto_excluir" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este produto?')">
                                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(CsrfValidator::getToken()) ?>">
                                        <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑️ Excluir</button>
                                    </form>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
        <?php if (isset($total_paginas) && $total_paginas > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($pagina_atual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?rota=produtos&pagina=<?= $pagina_atual - 1 ?>&ordenar=<?= urlencode($_GET['ordenar'] ?? '') ?>&busca=<?= urlencode($_GET['busca'] ?? '') ?>">Anterior</a>
                        </li>
                    <?php endif ?>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= ($i == $pagina_atual) ? 'active' : '' ?>">
                            <a class="page-link" href="?rota=produtos&pagina=<?= $i ?>&ordenar=<?= urlencode($_GET['ordenar'] ?? '') ?>&busca=<?= urlencode($_GET['busca'] ?? '') ?>"><?= $i ?></a>
                        </li>
                    <?php endfor ?>
                    <?php if ($pagina_atual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="?rota=produtos&pagina=<?= $pagina_atual + 1 ?>&ordenar=<?= urlencode($_GET['ordenar'] ?? '') ?>&busca=<?= urlencode($_GET['busca'] ?? '') ?>">Próxima</a>
                        </li>
                    <?php endif ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-muted">Nenhum produto cadastrado.</p>
    <?php endif; ?>
</div>
<?php require '../app/views/shared/footer.php'; ?>