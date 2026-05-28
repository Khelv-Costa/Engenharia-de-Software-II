<?php
/**
 * AuthMiddleware.php — Proteção de rotas via JWT
 */

declare(strict_types=1);

require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware
{
    /**
     * Valida o token e retorna o payload.
     * Interrompe a execução com 401 se inválido.
     */
    public static function handle(): array
    {
        $token = JWT::fromHeader();

        if (!$token) {
            Response::unauthorized('Token não fornecido.');
        }

        try {
            return JWT::verify($token);
        } catch (Exception $e) {
            Response::unauthorized($e->getMessage());
        }
    }

    /**
     * Garante que o utilizador tem o papel exigido.
     * Ex: AuthMiddleware::requireRole($payload, 'admin')
     */
    public static function requireRole(array $payload, string $role): void
    {
        if (($payload['role'] ?? '') !== $role) {
            Response::forbidden("Esta ação requer o papel: {$role}");
        }
    }
}
