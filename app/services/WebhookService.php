<?php
/**
 * WebhookService.php
 * Serviço de processamento de webhooks com autenticação
 */

class WebhookService {
    private $orderService;
    private $webhookToken;

    public function __construct() {
        $this->orderService = new OrderService();
        $this->webhookToken = $_ENV['WEBHOOK_TOKEN'] ?? null;
    }

    /**
     * Valida token do webhook
     */
    public function validateToken($token) {
        if (!$this->webhookToken) {
            Logger::warning('Webhook token not configured in .env');
            return false;
        }

        if (empty($token)) {
            Logger::warning('Webhook token not provided');
            return false;
        }

        $isValid = hash_equals($this->webhookToken, $token);

        if (!$isValid) {
            Logger::warning('Invalid webhook token attempt: ' . substr($token, 0, 5) . '...');
        }

        return $isValid;
    }

    /**
     * Processa payload do webhook
     */
    public function processPayload($payload) {
        try {
            if (!is_array($payload)) {
                return [
                    'success' => false,
                    'message' => 'Payload inválido: JSON esperado',
                    'code' => 400
                ];
            }

            // Validação básica de estrutura
            if (!isset($payload['id']) || !isset($payload['status'])) {
                return [
                    'success' => false,
                    'message' => 'Payload inválido: id e status são obrigatórios',
                    'code' => 400
                ];
            }

            $orderId = intval($payload['id']);
            $rawStatus = strtolower(trim((string) $payload['status']));

            $statusAliases = [
                'cancelled' => 'cancelado',
                'completed' => 'entregue',
                'shipped' => 'enviado',
                'processing' => 'processando',
                'pending' => 'pendente',
            ];

            $newStatus = $statusAliases[$rawStatus] ?? $rawStatus;

            // Validação de ID
            if ($orderId <= 0) {
                return [
                    'success' => false,
                    'message' => 'ID do pedido inválido',
                    'code' => 400
                ];
            }

            // Verifica se pedido existe
            $order = $this->orderService->getById($orderId);
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                    'code' => 404
                ];
            }

            if (!in_array($newStatus, VALID_ORDER_STATUSES, true)) {
                Logger::warning('Webhook invalid status: ' . $rawStatus);
                return [
                    'success' => false,
                    'message' => 'Status inválido para atualização de pedido',
                    'code' => 422
                ];
            }

            // Casos especiais de status
            if ($newStatus === 'cancelado') {
                $result = $this->orderService->cancel($orderId);
                if ($result['success']) {
                    return [
                        'success' => true,
                        'message' => 'Pedido cancelado com sucesso',
                        'code' => 200,
                        'data' => ['orderId' => $orderId, 'status' => 'cancelado']
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => $result['message'] ?? 'Erro ao cancelar pedido',
                        'code' => 500
                    ];
                }
            }

            // Atualiza status
            $result = $this->orderService->updateStatus($orderId, $newStatus);
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Status do pedido atualizado',
                    'code' => 200,
                    'data' => ['orderId' => $orderId, 'status' => $newStatus]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro ao atualizar status',
                    'code' => 400
                ];
            }
        } catch (Exception $e) {
            Logger::error('Webhook processing error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno ao processar webhook',
                'code' => 500
            ];
        }
    }

    /**
     * Retorna resposta JSON padronizada para webhook
     */
    public static function respond($result) {
        http_response_code($result['code']);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null
        ], JSON_UNESCAPED_UNICODE);
    }
}
