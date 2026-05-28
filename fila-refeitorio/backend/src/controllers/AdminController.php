<?php
/**
 * AdminController.php — Painel do administrador
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

class AdminController
{
    // ------------------------------------------------------------------
    // GET /api/admin/queue/{serviceId}
    // ------------------------------------------------------------------
    public function queue(int $serviceId): void
    {
        $payload = AuthMiddleware::handle();
        AuthMiddleware::requireRole($payload, 'admin');

        $tickets = Database::query(
            'SELECT t.id, t.ticket_number, t.status, t.created_at, t.called_at,
                    u.name AS cliente_nome
             FROM tickets t
             JOIN users u ON u.id = t.user_id
             WHERE t.service_id = ? AND t.status IN ("waiting","called","serving")
             ORDER BY t.created_at ASC',
            [$serviceId]
        )->fetchAll();

        Response::success($tickets);
    }

    // ------------------------------------------------------------------
    // POST /api/admin/call/{serviceId} — Chama próximo
    // ------------------------------------------------------------------
    public function callNext(int $serviceId): void
    {
        $payload = AuthMiddleware::handle();
        AuthMiddleware::requireRole($payload, 'admin');
        $adminId = (int) $payload['sub'];

        // Pega o próximo em espera
        $next = Database::query(
            'SELECT id, ticket_number FROM tickets
             WHERE service_id = ? AND status = "waiting"
             ORDER BY created_at ASC LIMIT 1',
            [$serviceId]
        )->fetch();

        if (!$next) Response::error('Fila vazia. Nenhum ticket em espera.', 404);

        Database::beginTransaction();
        try {
            Database::query(
                'UPDATE tickets SET status = "called", called_at = NOW() WHERE id = ?',
                [$next['id']]
            );
            Database::query(
                'INSERT INTO queue_history (ticket_id, service_id, admin_id, action) VALUES (?, ?, ?, "called")',
                [$next['id'], $serviceId, $adminId]
            );
            Database::commit();
        } catch (\Exception $e) {
            Database::rollback();
            Response::error('Erro ao chamar ticket.', 500);
        }

        Response::success([
            'ticket_id'     => (int) $next['id'],
            'ticket_number' => $next['ticket_number'],
        ], "Ticket {$next['ticket_number']} chamado.");
    }

    // ------------------------------------------------------------------
    // POST /api/admin/complete/{ticketId} — Conclui atendimento
    // ------------------------------------------------------------------
    public function complete(int $ticketId): void
    {
        $payload = AuthMiddleware::handle();
        AuthMiddleware::requireRole($payload, 'admin');
        $adminId = (int) $payload['sub'];

        $ticket = Database::query(
            'SELECT id, service_id, status, ticket_number FROM tickets WHERE id = ?',
            [$ticketId]
        )->fetch();

        if (!$ticket) Response::notFound('Ticket não encontrado.');
        if (!in_array($ticket['status'], ['called', 'serving'])) {
            Response::error('Apenas tickets "chamados" ou "em atendimento" podem ser concluídos.');
        }

        Database::beginTransaction();
        try {
            Database::query(
                'UPDATE tickets SET status = "completed", completed_at = NOW() WHERE id = ?',
                [$ticketId]
            );
            Database::query(
                'INSERT INTO queue_history (ticket_id, service_id, admin_id, action) VALUES (?, ?, ?, "completed")',
                [$ticketId, $ticket['service_id'], $adminId]
            );
            Database::commit();
        } catch (\Exception $e) {
            Database::rollback();
            Response::error('Erro ao completar atendimento.', 500);
        }

        Response::success(null, "Atendimento do ticket {$ticket['ticket_number']} concluído.");
    }

    // ------------------------------------------------------------------
    // GET /api/admin/stats/{serviceId} — Estatísticas do dia
    // ------------------------------------------------------------------
    public function stats(int $serviceId): void
    {
        $payload = AuthMiddleware::handle();
        AuthMiddleware::requireRole($payload, 'admin');

        $today = Database::query(
            'SELECT
                COUNT(*) AS total,
                SUM(status = "completed")  AS completed,
                SUM(status = "waiting")    AS waiting,
                SUM(status = "called")     AS called,
                SUM(status = "cancelled")  AS cancelled,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)), 1) AS avg_wait_minutes
             FROM tickets
             WHERE service_id = ? AND DATE(created_at) = CURDATE()',
            [$serviceId]
        )->fetch();

        Response::success([
            'date'             => date('Y-m-d'),
            'service_id'       => $serviceId,
            'total'            => (int)  $today['total'],
            'completed'        => (int)  $today['completed'],
            'waiting'          => (int)  $today['waiting'],
            'called'           => (int)  $today['called'],
            'cancelled'        => (int)  $today['cancelled'],
            'avg_wait_minutes' => (float) ($today['avg_wait_minutes'] ?? 0),
        ]);
    }

    // ------------------------------------------------------------------
    // GET /api/admin/services — Lista todos os serviços (para abas)
    // ------------------------------------------------------------------
    public function services(): void
    {
        $payload = AuthMiddleware::handle();
        AuthMiddleware::requireRole($payload, 'admin');

        $services = Database::query(
            'SELECT id, name, icon, prefix,
                (SELECT COUNT(*) FROM tickets t WHERE t.service_id = services.id AND t.status = "waiting") AS waiting
             FROM services WHERE active = 1 ORDER BY id'
        )->fetchAll();

        Response::success($services);
    }
}
