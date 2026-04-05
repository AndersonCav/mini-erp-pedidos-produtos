<?php
/**
 * Request.php
 * Classe para validação e sanitização de entradas
 */

class Request {
    private $data = [];
    private $errors = [];

    public function __construct() {
        // Coleta dados do GET, POST e JSON
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->data = array_merge($_GET, $_POST);
            
            // Se content-type é JSON
            if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                $json = json_decode(file_get_contents('php://input'), true);
                if ($json && is_array($json)) {
                    $this->data = array_merge($this->data, $json);
                }
            }
        } else {
            $this->data = $_GET;
        }
    }

    /**
     * Obtém valor com validação e sanitização
     */
    public function get($key, $default = null, $type = 'string', $required = false) {
        if (!isset($this->data[$key])) {
            if ($required) {
                $this->errors[$key] = "Campo '$key' é obrigatório";
            }
            return $default;
        }

        $value = $this->data[$key];

        // Sanitização por tipo
        switch ($type) {
            case 'int':
            case 'integer':
                return intval($value);

            case 'float':
            case 'double':
                return floatval($value);

            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) ?: null;

            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) ?: null;

            case 'string':
            default:
                return trim((string)$value);
        }
    }

    /**
     * Obtém inteiro com validação
     */
    public function getInt($key, $default = 0, $required = false) {
        return $this->get($key, $default, 'int', $required);
    }

    /**
     * Obtém float com validação
     */
    public function getFloat($key, $default = 0.0, $required = false) {
        return $this->get($key, $default, 'float', $required);
    }

    /**
     * Obtém string com validação
     */
    public function getString($key, $default = '', $required = false) {
        return $this->get($key, $default, 'string', $required);
    }

    /**
     * Obtém valor de arquivo
     */
    public function getFile($key) {
        return $_FILES[$key] ?? null;
    }

    /**
     * Valida status contra lista permitida
     */
    public function validateStatus($status, $validStatuses = []) {
        if (!in_array($status, $validStatuses)) {
            $this->errors['status'] = 'Status inválido';
            return false;
        }
        return true;
    }

    /**
     * Valida quantidade (deve ser > 0)
     */
    public function validateQuantity($quantity) {
        $qty = intval($quantity);
        if ($qty <= 0) {
            $this->errors['quantidade'] = 'Quantidade deve ser maior que zero';
            return false;
        }
        return true;
    }

    /**
     * Valida CEP brasileiro (optional)
     */
    public function validateCep($cep) {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) {
            $this->errors['cep'] = 'CEP deve ter 8 dígitos';
            return false;
        }
        return true;
    }

    /**
     * Valida email
     */
    public function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Email inválido';
            return false;
        }
        return true;
    }

    /**
     * Retorna todos os erros
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Retorna se há erros
     */
    public function hasErrors() {
        return !empty($this->errors);
    }

    /**
     * Adiciona erro customizado
     */
    public function addError($key, $message) {
        $this->errors[$key] = $message;
    }

    /**
     * Obtém dados brutos (use com cuidado)
     */
    public function all() {
        return $this->data;
    }

    /**
     * Verifica se é POST
     */
    public function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Verifica se é GET
     */
    public function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Verifica se é JSON
     */
    public function isJson() {
        return strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
    }
}
