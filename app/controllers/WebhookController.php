<?php

class WebhookController {
    private $webhookService;

    public function __construct() {
        $this->webhookService = new WebhookService();
    }

    public function receber() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Logger::warning('Webhook invalid method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
            WebhookService::respond([
                'success' => false,
                'message' => 'Método não permitido. Use POST.',
                'code' => 405
            ]);
        }

        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (strpos($contentType, 'application/json') === false) {
            Logger::warning('Webhook invalid content-type: ' . $contentType);
            WebhookService::respond([
                'success' => false,
                'message' => 'Content-Type inválido. Use application/json.',
                'code' => 415
            ]);
        }

        $token = $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? null;
        if (!$this->webhookService->validateToken($token)) {
            WebhookService::respond([
                'success' => false,
                'message' => 'Token de autenticação inválido ou ausente.',
                'code' => 401
            ]);
        }

        $rawPayload = file_get_contents('php://input');
        $payload = json_decode((string) $rawPayload, true);

        if (!is_array($payload)) {
            Logger::warning('Webhook payload is not valid JSON');
            WebhookService::respond([
                'success' => false,
                'message' => 'Payload JSON inválido.',
                'code' => 400
            ]);
        }

        $result = $this->webhookService->processPayload($payload);
        WebhookService::respond($result);
    }
}