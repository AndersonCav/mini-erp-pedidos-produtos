<?php
/**
 * database.php (LEGADO - Mantido para compatibilidade)
 * Use config/bootstrap.php para nova inicialização
 */

// Compatibilidade com código antigo
if (!isset($GLOBALS['db'])) {
    require_once __DIR__ . '/bootstrap.php';
}

// Mantém global $conn para compatibilidade com código existente
$conn = $GLOBALS['conn'] ?? null;
if (!$conn) {
    die("Erro ao conectar com o banco de dados");
}