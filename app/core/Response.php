<?php
/**
 * Response.php
 * Classe para respostas padronizadas (JSON e redirects)
 */

class Response {
    /**
     * Envia resposta JSON de sucesso
     */
    public static function success($data = [], $message = 'Sucesso', $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Envia resposta JSON de erro
     */
    public static function error($message = 'Erro', $errors = [], $httpCode = 400) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Envia resposta não autorizado (401)
     */
    public static function unauthorized($message = 'Não autorizado') {
        self::error($message, [], 401);
    }

    /**
     * Envia resposta não encontrado (404)
     */
    public static function notFound($message = 'Recurso não encontrado') {
        self::error($message, [], 404);
    }

    /**
     * Envia resposta com erro de validação (422)
     */
    public static function unprocessable($message = 'Dados inválidos', $errors = []) {
        self::error($message, $errors, 422);
    }

    /**
     * Retorna JSON simples
     */
    public static function json($data, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Faz redirect
     */
    public static function redirect($url, $message = null, $type = 'success') {
        if ($message) {
            $_SESSION['mensagem'] = $message;
            $_SESSION['mensagem_tipo'] = $type;
        }
        header("Location: $url");
        exit;
    }

    /**
     * Faz redirect com query string
     */
    public static function redirectTo($route, $params = [], $message = null, $type = 'success') {
        $url = "index.php?rota=$route";
        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }
        self::redirect($url, $message, $type);
    }

    /**
     * HTML simples com status code
     */
    public static function html($html, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}
