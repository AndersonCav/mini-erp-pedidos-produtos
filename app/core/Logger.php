<?php
/**
 * Logger.php
 * Sistema básico de logging para erros e eventos
 */

class Logger {
    private static $logFile = __DIR__ . '/../../logs/app.log';

    public static function initialize() {
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log de erro
     */
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }

    /**
     * Log de aviso
     */
    public static function warning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }

    /**
     * Log de informação
     */
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }

    /**
     * Log de debug
     */
    public static function debug($message, $context = []) {
        if ($_ENV['APP_DEBUG'] ?? false) {
            self::log('DEBUG', $message, $context);
        }
    }

    /**
     * Registra log no arquivo
     */
    private static function log($level, $message, $context = []) {
        self::initialize();

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[$timestamp] $level: $message$contextStr" . PHP_EOL;

        error_log($logMessage, 3, self::$logFile);
    }
}
