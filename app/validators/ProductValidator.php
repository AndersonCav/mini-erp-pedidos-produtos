<?php
/**
 * ProductValidator.php
 * Validações para produtos
 */

class ProductValidator {
    /**
     * Valida dados de criação/atualização de produto
     */
    public static function validate($name, $price, $imageUrl = '', $variations = []) {
        $errors = [];

        // Valida nome
        if (empty($name)) {
            $errors['nome'] = 'Nome do produto é obrigatório';
        } elseif (strlen($name) < 3) {
            $errors['nome'] = 'Nome deve ter no mínimo 3 caracteres';
        } elseif (strlen($name) > 255) {
            $errors['nome'] = 'Nome não pode exceder 255 caracteres';
        }

        // Valida preço
        if ($price === null || $price === '') {
            $errors['preco'] = 'Preço é obrigatório';
        } elseif (!is_numeric($price) || $price <= 0) {
            $errors['preco'] = 'Preço deve ser um valor maior que zero';
        }

        // Valida URL de imagem se fornecida
        if (!empty($imageUrl)) {
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL) && !file_exists($imageUrl)) {
                $errors['imagem_url'] = 'URL de imagem inválida ou arquivo não existe';
            }
        }

        // Valida variações
        if (!empty($variations) && is_array($variations)) {
            foreach ($variations as $index => $variation) {
                if (empty($variation)) {
                    $errors["variacao_$index"] = 'Variação não pode estar vazia';
                }
            }
        }

        return $errors;
    }

    /**
     * Valida arquivo de imagem
     */
    public static function validateImageFile($file) {
        $errors = [];

        if (!isset($file['tmp_name']) || !$file['tmp_name']) {
            return $errors; // Arquivo não fornecido é OK
        }

        // Valida tamanho
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors['imagem'] = 'Arquivo muito grande. Máximo: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB';
        }

        // Valida tipo
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_IMAGE_TYPES)) {
            $errors['imagem'] = 'Tipo de arquivo não permitido. Use: ' . implode(', ', ALLOWED_IMAGE_TYPES);
        }

        return $errors;
    }
}
