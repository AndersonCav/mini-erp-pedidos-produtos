<?php
/**
 * Produto.php
 * Adaptador de modelo para operações de produto
 */

require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../repositories/VariationRepository.php';
require_once __DIR__ . '/../services/StockService.php';

class Produto {
    private static $repository;
    private static $stockService;

    private static function getRepository() {
        if (!self::$repository) {
            self::$repository = new ProductRepository();
        }
        return self::$repository;
    }

    private static function getStockService() {
        if (!self::$stockService) {
            self::$stockService = new StockService();
        }
        return self::$stockService;
    }

    /**
     * Busca todos os produtos
     */
    public static function todos() {
        return self::getRepository()->findAll();
    }

    /**
     * Salva novo produto com variações e estoques
     */
    public static function salvar($dados) {
        try {
            $repo = self::getRepository();
            $variacaoRepo = new VariationRepository();
            $stockService = self::getStockService();

            // Cria produto
            $produtoId = $repo->create(
                $dados['nome'],
                floatval($dados['preco']),
                $dados['imagem_url'] ?? ''
            );

            // Adiciona variações
            if (!empty($dados['variacoes']) && is_array($dados['variacoes'])) {
                foreach ($dados['variacoes'] as $index => $variacaoNome) {
                    $variacaoId = $variacaoRepo->create($produtoId, $variacaoNome);
                    $estoque = intval($dados['estoques'][$index] ?? 0);
                    $stockService->create($produtoId, $estoque, $variacaoId);
                }
            } else {
                // Produto simples, sem variações
                $estoque = intval($dados['estoque'] ?? 0);
                $stockService->create($produtoId, $estoque);
            }

            Logger::info("Product saved: #$produtoId");
            return $produtoId;
        } catch (Exception $e) {
            Logger::error('Product save error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Atualiza produto com variações e estoques
     */
    public static function atualizar($dados) {
        try {
            $id = intval($dados['id']);
            $repo = self::getRepository();
            $variacaoRepo = new VariationRepository();
            $stockService = self::getStockService();

            // Atualiza produto
            $repo->update(
                $id,
                $dados['nome'],
                floatval($dados['preco']),
                $dados['imagem_url'] ?? null
            );

            // Remove variações e estoques antigos
            $variacoes = $variacaoRepo->findByProductId($id);
            foreach ($variacoes as $v) {
                $variacaoRepo->delete($v['id']);
            }

            // Adiciona novas variações
            if (!empty($dados['variacoes']) && is_array($dados['variacoes'])) {
                foreach ($dados['variacoes'] as $index => $variacaoNome) {
                    $variacaoId = $variacaoRepo->create($id, $variacaoNome);
                    $estoque = intval($dados['estoques'][$index] ?? 0);
                    $stockService->create($id, $estoque, $variacaoId);
                }
            } else {
                // Produto simples
                $estoque = intval($dados['estoque'] ?? 0);
                $stockService->create($id, $estoque);
            }

            Logger::info("Product updated: #$id");
        } catch (Exception $e) {
            Logger::error('Product update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca produto com todas as variações e estoques
     */
    public static function todosComEstoque() {
        return self::getStockService()->findPaginated(1000, 0, 'p.nome ASC', '');
    }

    /**
     * Deleta produto (cascata de variações e estoques)
     */
    public static function excluir($id) {
        try {
            self::getRepository()->delete($id);
            Logger::info("Product deleted: #$id");
        } catch (Exception $e) {
            Logger::error('Product delete error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Busca produtos com paginação
     */
    public static function paginar($limite, $offset, $ordenar = 'nome_asc', $busca = '') {
        try {
            $orderMap = [
                'nome_asc' => 'nome ASC',
                'nome_desc' => 'nome DESC',
                'preco_asc' => 'preco ASC',
                'preco_desc' => 'preco DESC',
            ];

            $orderBy = $orderMap[$ordenar] ?? 'nome ASC';

            return self::getRepository()->findPaginated($limite, $offset, $orderBy, $busca);
        } catch (Exception $e) {
            Logger::error('Product pagination error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Conta total de produtos
     */
    public static function contarTodos($busca = '') {
        return self::getRepository()->countAll($busca);
    }
}