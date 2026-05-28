<?php
/**
 * TicketsController.php — CRUD de tickets do cliente
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

class TicketsController
{
    // ------------------------------------------------------------------
    // POST /api/tickets — Cria um novo ticket
    // ------------------------------------------------------------------
    public function create(): void
    {
        $payload   = AuthMiddleware::handle();
        $userId    = (int) $payload['sub'];
        $body      = (array) json_decode(file_get_contents('php://input'), true);
        $serviceId = (int) ($body['service_id'] ?? 0);

        if (!$serviceId) Response::error('service_id é obrigatório.');

        // Verifica se serviço existe
        $service = Database::query(
            'SELECT id, name, prefix FROM services WHERE id = ? AND active = 1',
            [$serviceId]
        )->fetch();
        if (!$service) Response::notFound('Serviço não encontrado.');

        // Verifica se cliente já tem ticket ativo no mesmo serviço
        $existing = Database::query(
            'SELECT id FROM tickets WHERE user_id = ? AND service_id = ? AND status IN ("waiting","called","serving")',
            [$userId, $serviceId]
        )->fetch();
        if ($existing) Response::error('Já possui um ticket ativo para este serviço.', 409);

        // Gera número de ticket: PREFIX + sequência diária
        $count = Database::query(
            'SELECT COUNT(*) AS c FROM tickets WHERE service_id = ? AND DATE(created_at) = CURDATE()',
            [$serviceId]
        )->fetch()['c'];
        $ticketNumber = $service['prefix'] . str_pad((string)((int)$count + 1), 3, '0', STR_PAD_LEFT);

        Database::beginTransaction();
        try {
            Database::query(
                'INSERT INTO tickets (user_id, service_id, ticket_number, status) VALUES (?, ?, ?, "waiting")',
                [$userId, $serviceId, $ticketNumber]
            );
            $ticketId = (int) Database::lastInsertId();

            Database::query(
                'INSERT INTO queue_history (ticket_id, service_id, action) VALUES (?, ?, "created")',
                [$ticketId, $serviceId]
            );

            Database::commit();
        } catch (\Exception $e) {
            Database::rollback();
            Response::error('Erro ao criar ticket.', 500);
        }

        // Posição na fila
        $position = $this->getPosition($ticketId, $serviceId);

        Response::success([
            'id'            => $ticketId,
            'ticket_number' => $ticketNumber,
            'service'       => $service['name'],
            'status'        => 'waiting',
            'position'      => $position,
            'estimated_wait'=> $position * (int) \Config::get('ESTIMATED_TIME_PER_TICKET', '3'),
        ], 'Ticket criado com sucesso.', 201);
    }

    // ------------------------------------------------------------------
    // GET /api/tickets/my — Ticket ativo do cliente
    // ------------------------------------------------------------------
    public function myTicket(): void
    {
        $payload = AuthMiddleware::handle();
        $userId  = (int) $payload['sub'];

        $ticket = Database::query(
            'SELECT t.id, t.ticket_number, t.status, t.created_at, t.called_at,
                    s.id AS service_id, s.name AS service_name, s.icon AS service_icon
             FROM tickets t
             JOIN services s ON s.id = t.service_id
             WHERE t.user_id = ? AND t.status IN ("waiting","called","serving")
             ORDER BY t.created_at DESC LIMIT 1',
            [$userId]
        )->fetch();

        if (!$ticket) {
            Response::success(null, 'Nenhum ticket ativo.');
        }

        $position = $this->getPosition((int)$ticket['id'], (int)$ticket['service_id']);
        $ticket['position']       = $position;
        $ticket['estimated_wait'] = $position * (int) \Config::get('ESTIMATED_TIME_PER_TICKET', '3');

        Response::success($ticket);
    }

    // ------------------------------------------------------------------
    // DELETE /api/tickets/{id} — Cancela ticket
    // ------------------------------------------------------------------
    public function cancel(int $ticketId): void
    {
        $payload = AuthMiddleware::handle();
        $userId  = (int) $payload['sub'];

        $ticket = Database::query(
            'SELECT id, service_id, status, user_id FROM tickets WHERE id = ?',
            [$ticketId]
        )->fetch();

        if (!$ticket) Response::notFound('Ticket não encontrado.');

        // Cliente só pode cancelar o próprio ticket; admin pode cancelar qualquer um
        if ($payload['role'] !== 'admin' && (int)$ticket['user_id'] !== $userId) {
            Response::forbidden();
        }

        if (!in_array($ticket['status'], ['waiting', 'called'])) {
            Response::error('Só é possível cancelar tickets em espera ou chamados.');
        }

        Database::beginTransaction();
        try {
            Database::query(
                'UPDATE tickets SET status = "cancelled" WHERE id = ?',
                [$ticketId]
            );
            Database::query(
                'INSERT INTO queue_history (ticket_id, service_id, admin_id, action) VALUES (?, ?, ?, "cancelled")',
                [$ticketId, $ticket['service_id'], $payload['role'] === 'admin' ? $userId : null]
            );
            Database::commit();
        } catch (\Exception $e) {
            Database::rollback();
            Response::error('Erro ao cancelar ticket.', 500);
        }

        Response::success(null, 'Ticket cancelado.');
    }

    // ------------------------------------------------------------------
    private function getPosition(int $ticketId, int $serviceId): int
    {
        $rows = Database::query(
            'SELECT id FROM tickets WHERE service_id = ? AND status = "waiting" ORDER BY created_at ASC',
            [$serviceId]
        )->fetchAll();

        foreach ($rows as $i => $row) {
            if ((int)$row['id'] === $ticketId) return $i + 1;
        }
        return 0;
    }
}
