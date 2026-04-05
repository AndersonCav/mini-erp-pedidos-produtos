<?php require '../app/views/shared/header.php'; ?>
<div class="container mt-4">
    <h2 class="text-primary mb-3">📦 Pedidos Realizados</h2>
    <form method="GET" class="card card-body shadow-sm mb-4">
        <input type="hidden" name="rota" value="pedidos">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">🔍 Buscar por Produto</label>
                <input type="text" name="busca" class="form-control" placeholder="Ex: FIFA 23" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">🎯 Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos os Status</option>
                    <?php foreach (["pendente", "finalizado", "cancelado"] as $s): ?>
                        <option value="<?= $s ?>" <?= (($_GET['status'] ?? '') === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">📅 Ordenar por</label>
                <select name="ordem" class="form-select">
                    <option value="">Selecionar</option>
                    <option value="mais_novo" <?= ($_GET['ordem'] ?? '') === 'mais_novo' ? 'selected' : '' ?>>Mais recentes</option>
                    <option value="mais_antigo" <?= ($_GET['ordem'] ?? '') === 'mais_antigo' ? 'selected' : '' ?>>Mais antigos</option>
                    <option value="maior_valor" <?= ($_GET['ordem'] ?? '') === 'maior_valor' ? 'selected' : '' ?>>Maior valor</option>
                    <option value="menor_valor" <?= ($_GET['ordem'] ?? '') === 'menor_valor' ? 'selected' : '' ?>>Menor valor</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">🔎 Aplicar</button>
            </div>
        </div>
    </form>
    <?php if (!empty($_SESSION['mensagem'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['mensagem'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        <?php unset($_SESSION['mensagem']); ?>
    <?php endif; ?>
    <?php if (!empty($pedidos)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover shadow-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Produtos</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p): ?>
                        <tr>
                            <td><strong>#<?= $p['id'] ?></strong></td>
                            <td style="white-space: pre-wrap"><?= nl2br(htmlspecialchars($p['produtos_texto'])) ?></td>
                            <td><span class="fw-bold text-success">R$ <?= number_format($p['total'], 2, ',', '.') ?></span></td>
                            <td>
                                <?php
                                    $statusBadge = match(strtolower($p['status'])) {
                                        'pendente' => 'warning',
                                        'finalizado' => 'success',
                                        'cancelado' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>
                                <span class="badge bg-<?= $statusBadge ?>"><?= ucfirst($p['status']) ?></span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
                            <td>
                                <form method="POST" action="index.php?rota=pedido_alterar_status" class="d-flex align-items-center">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(CsrfValidator::getToken()) ?>">
                                    <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                    <select name="status" class="form-select form-select-sm me-2">
                                        <?php
                                            $opcoes = ['Pendente', 'Finalizado', 'Cancelado'];
                                            foreach ($opcoes as $opcao):
                                                $selecionado = strtolower($p['status']) === strtolower($opcao) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $opcao ?>" <?= $selecionado ?>><?= $opcao ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Salvar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_paginas > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?= $pagina_atual == $i ? 'active' : '' ?>">
                        <a class="page-link" href="?rota=pedidos&pagina=<?= $i ?>&status=<?= urlencode($_GET['status'] ?? '') ?>&busca=<?= urlencode($_GET['busca'] ?? '') ?>&ordem=<?= urlencode($_GET['ordem'] ?? '') ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info mt-4">Nenhum pedido encontrado com os filtros selecionados.</div>
    <?php endif; ?>
</div>
<?php require '../app/views/shared/footer.php'; ?>