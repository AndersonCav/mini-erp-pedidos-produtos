<?php
/**
 * EmailService.php
 * Serviço de envio de emails com tratamento de erros
 */

class EmailService {
    /**
     * Envia email de confirmação de pedido
     */
    public static function sendOrderConfirmation($email, $orderId, $products, $subtotal, $shipping, $discount, $total, $address, $cep) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Logger::warning("Invalid email address: $email");
            return false;
        }

        $subject = "Confirmação do Pedido #$orderId";
        $message = self::buildOrderConfirmationEmail($orderId, $products, $subtotal, $shipping, $discount, $total, $address, $cep);

        return self::send($email, $subject, $message);
    }

    /**
     * Constrói corpo do email de confirmação
     */
    private static function buildOrderConfirmationEmail($orderId, $products, $subtotal, $shipping, $discount, $total, $address, $cep) {
        $message = "Olá!\n\n";
        $message .= "Seu pedido foi recebido e está sendo processado.\n\n";
        $message .= "======================================\n";
        $message .= "PEDIDO #$orderId\n";
        $message .= "======================================\n\n";

        $message .= "Itens do Pedido:\n";
        $message .= str_repeat("-", 40) . "\n";
        $message .= $products;
        $message .= "\n";

        $message .= "Subtotal: R$ " . number_format($subtotal, 2, ',', '.') . "\n";
        $message .= "Frete: R$ " . number_format($shipping, 2, ',', '.') . "\n";

        if ($discount > 0) {
            $message .= "Desconto: -R$ " . number_format($discount, 2, ',', '.') . "\n";
        }

        $message .= str_repeat("-", 40) . "\n";
        $message .= "TOTAL: R$ " . number_format($total, 2, ',', '.') . "\n";
        $message .= str_repeat("-", 40) . "\n\n";

        $message .= "📍 Endereço de Entrega:\n";
        $message .= "$address\n";
        $message .= "CEP: $cep\n\n";

        $message .= "Você pode acompanhar seu pedido através do nossa plataforma.\n\n";
        $message .= "Obrigado por comprar conosco!\n\n";
        $message .= "---\n";
        $message .= "Mini ERP\n";

        return $message;
    }

    /**
     * Envia email genérico
     */
    public static function send($to, $subject, $message, $replyTo = null) {
        try {
            // Validação básica
            if (empty($to) || empty($subject) || empty($message)) {
                throw new Exception('Email, subject e message são obrigatórios');
            }

            // Headers
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "From: pedidos@mini-erp.com.br\r\n";

            if ($replyTo) {
                $headers .= "Reply-To: $replyTo\r\n";
            }

            // Envia email
            $result = mail($to, $subject, $message, $headers);

            if (!$result) {
                Logger::warning("Failed to send email to $to");
                return false;
            }

            Logger::info("Email sent successfully to $to");
            return true;
        } catch (Exception $e) {
            Logger::error("Email sending error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia email para admin sobre novo pedido
     */
    public static function notifyAdmin($orderId, $total, $email) {
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? null;
        if (!$adminEmail) {
            return false;
        }

        $subject = "Novo Pedido #$orderId Recebido";
        $message = "Um novo pedido foi recebido no sistema.\n\n";
        $message .= "ID do Pedido: #$orderId\n";
        $message .= "Total: R$ " . number_format($total, 2, ',', '.') . "\n";
        $message .= "Email do Cliente: $email\n\n";
        $message .= "Acesse o painel de administração para mais detalhes.";

        return self::send($adminEmail, $subject, $message);
    }
}
