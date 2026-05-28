<?php
/**
 * AuthController.php — Registo e login de utilizadores
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthController
{
    // ------------------------------------------------------------------
    // POST /api/auth/register
    // ------------------------------------------------------------------
    public function register(): void
    {
        $body = $this->body();

        $name     = trim($body['name']     ?? '');
        $email    = strtolower(trim($body['email']    ?? ''));
        $password = $body['password'] ?? '';

        // Validação básica
        $errors = [];
        if (strlen($name) < 2)          $errors['name']     = 'Nome deve ter pelo menos 2 caracteres.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'E-mail inválido.';
        if (strlen($password) < 6)      $errors['password'] = 'Senha deve ter pelo menos 6 caracteres.';

        if ($errors) Response::error('Dados inválidos.', 422, $errors);

        // E-mail único
        $exists = Database::query('SELECT id FROM users WHERE email = ?', [$email])->fetch();
        if ($exists) Response::error('Este e-mail já está registado.', 409);

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::query(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)',
            [$name, $email, $hash, 'customer']
        );

        $userId = (int) Database::lastInsertId();
        $token  = JWT::generate(['sub' => $userId, 'name' => $name, 'email' => $email, 'role' => 'customer']);

        Response::success([
            'token' => $token,
            'user'  => ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => 'customer'],
        ], 'Registo efetuado com sucesso.', 201);
    }

    // ------------------------------------------------------------------
    // POST /api/auth/login
    // ------------------------------------------------------------------
    public function login(): void
    {
        $body  = $this->body();
        $email = strtolower(trim($body['email']    ?? ''));
        $pass  = $body['password'] ?? '';

        if (!$email || !$pass) Response::error('E-mail e senha são obrigatórios.');

        $user = Database::query(
            'SELECT id, name, email, password, role, active FROM users WHERE email = ?',
            [$email]
        )->fetch();

        if (!$user || !password_verify($pass, $user['password'])) {
            Response::error('Credenciais inválidas.', 401);
        }

        if (!$user['active']) {
            Response::error('Conta desativada. Contacte o administrador.', 403);
        }

        $token = JWT::generate([
            'sub'   => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ]);

        Response::success([
            'token' => $token,
            'user'  => [
                'id'    => (int) $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ], 'Login efetuado com sucesso.');
    }

    // ------------------------------------------------------------------
    // GET /api/auth/me  (protegido)
    // ------------------------------------------------------------------
    public function me(): void
    {
        $payload = \AuthMiddleware::handle();
        $user = Database::query(
            'SELECT id, name, email, role, created_at FROM users WHERE id = ?',
            [$payload['sub']]
        )->fetch();

        if (!$user) Response::notFound('Utilizador não encontrado.');
        Response::success($user);
    }

    // ------------------------------------------------------------------
    private function body(): array
    {
        return (array) json_decode(file_get_contents('php://input'), true);
    }
}
