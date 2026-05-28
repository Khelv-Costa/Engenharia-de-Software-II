<?php
/**
 * ServicesController.php — Listagem de serviços e info de fila pública
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/Response.php';

class ServicesController
{
    // GET /api/services
    public function index(): void
    {
        $services = Database::query(
            'SELECT id, name, description, icon, prefix FROM services WHERE active = 1 ORDER BY id'
        )->fetchAll();

        // Para cada serviço, conta quantos tickets estão em espera
        foreach ($services as &$svc) {
            $row = Database::query(
                'SELECT COUNT(*) AS total FROM tickets WHERE service_id = ? AND status = "waiting"',
                [$svc['id']]
            )->fetch();
            $svc['waiting_count'] = (int) $row['total'];
        }

        Response::success($services);
    }

    // GET /api/queue/{serviceId}
    public function queueInfo(int $serviceId): void
    {
        $service = Database::query(
            'SELECT id, name, icon, prefix FROM services WHERE id = ? AND active = 1',
            [$serviceId]
        )->fetch();

        if (!$service) Response::notFound('Serviço não encontrado.');

        $waiting = Database::query(
            'SELECT COUNT(*) AS total FROM tickets WHERE service_id = ? AND status = "waiting"',
            [$serviceId]
        )->fetch()['total'];

        $called = Database::query(
            'SELECT t.ticket_number FROM tickets t
             WHERE t.service_id = ? AND t.status = "called"
             ORDER BY t.called_at DESC LIMIT 1',
            [$serviceId]
        )->fetch();

        $estimatedMinutes = (int) \Config::get('ESTIMATED_TIME_PER_TICKET', '3');

        Response::success([
            'service'           => $service,
            'waiting_count'     => (int) $waiting,
            'currently_serving' => $called['ticket_number'] ?? null,
            'estimated_wait'    => (int) $waiting * $estimatedMinutes,
        ]);
    }
}
