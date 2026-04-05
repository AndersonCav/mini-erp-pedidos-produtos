<?php
    require_once '../app/controllers/ProdutoController.php';
    require_once '../app/controllers/PedidoController.php';
    require_once '../app/controllers/CupomController.php';
    require_once '../app/controllers/WebhookController.php';
    require_once '../app/controllers/EstoqueController.php';
    $rota = $_GET['rota'] ?? 'produtos';
    switch ($rota) {
        case 'produtos':
            (new ProdutoController())->index();
            break;
        case 'produto_form':
            (new ProdutoController())->form();
            break;
        case 'produto_salvar':
            (new ProdutoController())->salvar();
            break;
        case 'produto_editar':
            (new ProdutoController())->editar();
            break;
        case 'produto_atualizar':
            (new ProdutoController())->atualizar();
            break;
        case 'produto_excluir':
            (new ProdutoController())->excluir();
            break;
        case 'variacao_excluir':
            (new ProdutoController())->excluirVariacao();
            break;
        case 'carrinho':
            (new PedidoController())->carrinho();
            break;
        case 'adicionar_carrinho':
            (new PedidoController())->adicionar();
            break;
        case 'finalizar_pedido':
            (new PedidoController())->finalizar();
            break;
        case 'limpar_carrinho':
            (new PedidoController())->limpar();
            break;
        case 'remover_item':
            (new PedidoController())->remover();
            break;
        case 'validar_cupom':
            (new CupomController())->validar();
            break;
        case 'cupons':
            (new CupomController())->form();
            break;
        case 'cupom_salvar':
            (new CupomController())->salvar();
            break;
        case 'estoque':
            (new EstoqueController())->index();
            break;
        case 'estoque_atualizar':
            (new EstoqueController())->atualizar();
            break;
        case 'webhook':
            (new WebhookController())->receber();
            break;
        case 'pedidos':
            (new PedidoController())->lista();
            break;
        case 'pedido_alterar_status':
            (new PedidoController())->alterarStatus();
            break;
        case 'cupons_listar':
            (new CupomController())->listar();
            break;
        case 'cupom_editar':
            (new CupomController())->editar();
            break;
        case 'cupom_excluir':
            (new CupomController())->excluir();
            break;
        case 'atualizar_qtd':
            (new PedidoController())->atualizarQuantidade();
            break;
        default:
            http_response_code(404);
            echo "<h1>404 - Página não encontrada</h1>";
    }