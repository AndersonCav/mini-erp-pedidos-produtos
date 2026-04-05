<?php
/**
 * CsrfValidator.php
 * Proteção contra CSRF (Cross-Site Request Forgery)
 */

class CsrfValidator {
    const TOKEN_LENGTH = 32;
    const TOKEN_SESSION_KEY = '_csrf_token';

    /**
     * Gera novo token CSRF
     */
    public static function generateToken() {
        if (!isset($_SESSION[self::TOKEN_SESSION_KEY])) {
            $_SESSION[self::TOKEN_SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }
        return $_SESSION[self::TOKEN_SESSION_KEY];
    }

    /**
     * Retorna token atual ou gera novo
     */
    public static function getToken() {
        return self::generateToken();
    }

    /**
     * Valida token CSRF
     */
    public static function validate($token = null) {
        if ($token === null) {
            $token = $_POST['_csrf_token'] ?? $_GET['_csrf_token'] ?? null;
        }

        if (!$token) {
            Logger::warning('CSRF token missing in request');
            return false;
        }

        $sessionToken = $_SESSION[self::TOKEN_SESSION_KEY] ?? null;

        if (!$sessionToken) {
            Logger::warning('CSRF token not found in session');
            return false;
        }

        // Usa hash_equals para prevenir timing attacks
        $isValid = hash_equals($sessionToken, $token);

        if (!$isValid) {
            Logger::warning('CSRF token mismatch');
        }

        return $isValid;
    }

    /**
     * Regenera token (para máxima segurança após operações críticas)
     */
    public static function regenerateToken() {
        unset($_SESSION[self::TOKEN_SESSION_KEY]);
        return self::generateToken();
    }
}
