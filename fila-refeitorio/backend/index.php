<?php
/**
 * index.php — Ponto de entrada do backend
 * Router simples baseado em path e método HTTP.
 */

declare(strict_types=1);

// ── Autoload manual ────────────────────────────────────────────────
$base = __DIR__ . '/src';
require_once "{$base}/config/Config.php";
Config::load("{$base}/config/.env");

require_once "{$base}/config/Database.php";
require_once "{$base}/utils/Response.php";
require_once "{$base}/utils/JWT.php";
require_once "{$base}/middleware/AuthMiddleware.php";
require_once "{$base}/controllers/AuthController.php";
require_once "{$base}/controllers/ServicesController.php";
require_once "{$base}/controllers/TicketsController.php";
require_once "{$base}/controllers/AdminController.php";

// ── CORS ────────────────────────────────────────────────────────────
$allowedOrigins = explode(',', Config::get('CORS_ALLOWED_ORIGINS', 'http://localhost:4202'));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Routing ─────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim($uri, '/');

// Remove prefixo /api se o servidor for configurado assim
$uri = preg_replace('#^/api#', '', $uri);

$segments = array_values(array_filter(explode('/', $uri)));

try {
    match (true) {

        // Auth
        $method === 'POST' && $uri === '/auth/register'
            => (new AuthController)->register(),
        $method === 'POST' && $uri === '/auth/login'
            => (new AuthController)->login(),
        $method === 'GET'  && $uri === '/auth/me'
            => (new AuthController)->me(),

        // Services (public)
        $method === 'GET' && $uri === '/services'
            => (new ServicesController)->index(),
        $method === 'GET' && preg_match('#^/queue/(\d+)$#', $uri, $m)
            => (new ServicesController)->queueInfo((int) $m[1]),

        // Tickets (customer)
        $method === 'POST'   && $uri === '/tickets'
            => (new TicketsController)->create(),
        $method === 'GET'    && $uri === '/tickets/my'
            => (new TicketsController)->myTicket(),
        $method === 'DELETE' && preg_match('#^/tickets/(\d+)$#', $uri, $m)
            => (new TicketsController)->cancel((int) $m[1]),

        // Admin
        $method === 'GET'  && $uri === '/admin/services'
            => (new AdminController)->services(),
        $method === 'GET'  && preg_match('#^/admin/queue/(\d+)$#', $uri, $m)
            => (new AdminController)->queue((int) $m[1]),
        $method === 'POST' && preg_match('#^/admin/call/(\d+)$#', $uri, $m)
            => (new AdminController)->callNext((int) $m[1]),
        $method === 'POST' && preg_match('#^/admin/complete/(\d+)$#', $uri, $m)
            => (new AdminController)->complete((int) $m[1]),
        $method === 'GET'  && preg_match('#^/admin/stats/(\d+)$#', $uri, $m)
            => (new AdminController)->stats((int) $m[1]),

        // 404
        default => Response::notFound("Rota não encontrada: {$method} {$uri}")
    };
} catch (\Throwable $e) {
    error_log('[ERRO] ' . $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine());
    $debug = Config::get('APP_DEBUG', 'false') === 'true';
    Response::error(
        $debug ? $e->getMessage() : 'Erro interno do servidor.',
        500,
        $debug ? ['file' => $e->getFile(), 'line' => $e->getLine()] : null
    );
}
