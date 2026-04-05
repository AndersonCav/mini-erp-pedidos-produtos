<?php
/**
 * Script para criar banco mini_erp, executar schema.sql e inserir dados de teste
 */

// Conexão inicial SEM BANCO
$conn = new mysqli('127.0.0.1', 'root', '');

if ($conn->connect_error) {
    die('❌ Erro na conexão: ' . $conn->connect_error . "\n");
}

echo "✅ Conectado ao MySQL\n";

// Cria o banco se não existir
echo "📦 Criando banco de dados mini_erp...\n";
if (!$conn->query("CREATE DATABASE IF NOT EXISTS mini_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    die('❌ Erro ao criar banco: ' . $conn->error . "\n");
}

// Seleciona o banco
$conn->select_db('mini_erp');
echo "✅ Banco selecionado: mini_erp\n\n";

// Lê o arquivo schema.sql
$schema = file_get_contents(__DIR__ . '/schema.sql');

// Executa o schema
echo "📋 Executando schema.sql...\n";
if ($conn->multi_query($schema)) {
    echo "✅ Schema executado com sucesso!\n\n";
    
    // Consume all results
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
} else {
    die('❌ Erro ao executar schema: ' . $conn->error . "\n");
}

// Inserir dados de teste
echo "📊 Inserindo dados de teste...\n";

// Produtos
$produtos = [
    ['nome' => 'Notebook Dell', 'preco' => 3500.00, 'imagem' => 'https://via.placeholder.com/300?text=Notebook'],
    ['nome' => 'Mouse Logitech', 'preco' => 150.00, 'imagem' => 'https://via.placeholder.com/300?text=Mouse'],
    ['nome' => 'Teclado Mecânico', 'preco' => 450.00, 'imagem' => 'https://via.placeholder.com/300?text=Teclado'],
    ['nome' => 'Monitor LG 27"', 'preco' => 1200.00, 'imagem' => 'https://via.placeholder.com/300?text=Monitor'],
    ['nome' => 'Headset Corsair', 'preco' => 800.00, 'imagem' => 'https://via.placeholder.com/300?text=Headset'],
];

foreach ($produtos as $prod) {
    $stmt = $conn->prepare("INSERT INTO produtos (nome, preco, imagem_url) VALUES (?, ?, ?)");
    $stmt->bind_param('sds', $prod['nome'], $prod['preco'], $prod['imagem']);
    $stmt->execute();
    echo "  ✓ Produto: {$prod['nome']}\n";
}

// Cupons
$cupons = [
    ['codigo' => 'DESCONTO10', 'desconto' => 100.00, 'minimo' => 500.00],
    ['codigo' => 'FRETEGRATIS', 'desconto' => 50.00, 'minimo' => 200.00],
    ['codigo' => 'PRIMEIRACOMPRA', 'desconto' => 150.00, 'minimo' => 300.00],
];

foreach ($cupons as $cupom) {
    $validade = date('Y-m-d', strtotime('+30 days'));
    $stmt = $conn->prepare("INSERT INTO cupons (codigo, valor_desconto, minimo_subtotal, validade) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sdds', $cupom['codigo'], $cupom['desconto'], $cupom['minimo'], $validade);
    $stmt->execute();
    echo "  ✓ Cupom: {$cupom['codigo']}\n";
}

// Estoques
$stmt = $conn->prepare("SELECT id FROM produtos ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $quantidade = random_int(10, 100);
    $stmt2 = $conn->prepare("INSERT INTO estoques (produto_id, quantidade) VALUES (?, ?)");
    $stmt2->bind_param('ii', $row['id'], $quantidade);
    $stmt2->execute();
}

echo "  ✓ Estoques criados\n";

$conn->close();

echo "\n" . str_repeat("=", 60) . "\n";
echo "✨ BANCO DE DADOS CRIADO COM SUCESSO!\n";
echo str_repeat("=", 60) . "\n\n";

echo "🔐 DADOS DE CONEXÃO:\n";
echo "  • Host: 127.0.0.1 (ou localhost)\n";
echo "  • Database: mini_erp\n";
echo "  • User: root\n";
echo "  • Password: (vazio)\n";
echo "  • Port: 3306\n\n";

echo "📝 CONFIGURE O .env:\n";
echo "  DB_HOST=127.0.0.1\n";
echo "  DB_USER=root\n";
echo "  DB_PASSWORD=\n";
echo "  DB_NAME=mini_erp\n\n";

echo "🛍️  PRODUTOS INSERIDOS (5):\n";
echo "  1. Notebook Dell............ R$ 3.500,00\n";
echo "  2. Mouse Logitech.......... R$ 150,00\n";
echo "  3. Teclado Mecânico....... R$ 450,00\n";
echo "  4. Monitor LG 27\"......... R$ 1.200,00\n";
echo "  5. Headset Corsair........ R$ 800,00\n\n";

echo "🎁 CUPONS PARA TESTE (3):\n";
echo "  • DESCONTO10............ -R$ 100 (min R$ 500)\n";
echo "  • FRETEGRATIS........... -R$ 50 (min R$ 200)\n";
echo "  • PRIMEIRACOMPRA........ -R$ 150 (min R$ 300)\n";
echo "  Validade: +30 dias\n\n";

echo "🚀 PRÓXIMOS PASSOS:\n";
echo "  1. Copiar .env.example para .env\n";
echo "  2. Executar: php -S localhost:8000 -t public/\n";
echo "  3. Acessar: http://localhost:8000\n";
echo "  4. Testar fluxo de compra com cupons\n\n";

echo "✅ Tudo pronto!\n\n";
?>
