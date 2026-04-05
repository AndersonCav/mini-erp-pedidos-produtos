<?php
/**
 * bootstrap.php
 * Inicialização centralizada da aplicação
 */

// Carrega .env se existir (parser simples e tolerante a comentários)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || strpos($line, '#') === 0 || strpos($line, ';') === 0) {
                continue;
            }

            $delimiterPos = strpos($line, '=');
            if ($delimiterPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $delimiterPos));
            $value = trim(substr($line, $delimiterPos + 1));

            if ($key === '') {
                continue;
            }

            if (((substr($value, 0, 1) === '"') && (substr($value, -1) === '"')) ||
                ((substr($value, 0, 1) === "'") && (substr($value, -1) === "'"))) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
        }
    }
}

// Defaults se .env não definiu
if (!isset($_ENV['DB_HOST'])) {
    $_ENV['DB_HOST'] = 'localhost';
    $_ENV['DB_USER'] = 'root';
    $_ENV['DB_PASSWORD'] = '';
    $_ENV['DB_NAME'] = 'mini_erp';
    $_ENV['APP_DEBUG'] = false;
}

// Configuração de sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuração de timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'America/Sao_Paulo');

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Carrega constantes
require_once __DIR__ . '/constants.php';

// Carrega classes core
require_once __DIR__ . '/../app/core/Logger.php';

Logger::initialize();

// Inicializa logger
Logger::info('Aplicação inicializada');

// Carrega Database e outras classes core
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Request.php';
require_once __DIR__ . '/../app/core/Response.php';

// Inicializa banco de dados globalmente para compatibilidade
try {
    $GLOBALS['db'] = Database::getInstance();
    $GLOBALS['conn'] = $GLOBALS['db']->getConnection();
} catch (Exception $e) {
    Logger::error('Falha ao inicializar banco: ' . $e->getMessage());
    die('Erro ao conectar com o banco de dados. Por favor, tente mais tarde.');
}
