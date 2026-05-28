<?php
/**
 * JWT.php — Geração e validação de tokens JWT (HS256)
 * Implementação manual sem dependências externas.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/Config.php';

class JWT
{
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }

    /** Gera um token JWT com os dados do utilizador */
    public static function generate(array $payload): string
    {
        Config::load();
        $secret  = Config::requireKey('JWT_SECRET');
        $expiry  = (int) Config::get('JWT_EXPIRY', '86400');

        $header  = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = array_merge($payload, [
            'iat' => time(),
            'exp' => time() + $expiry,
        ]);
        $payload  = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        return "{$header}.{$payload}.{$signature}";
    }

    /** Valida e decodifica um token. Retorna payload ou lança exceção */
    public static function verify(string $token): array
    {
        Config::load();
        $secret = Config::requireKey('JWT_SECRET');

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('Token inválido.');
        }

        [$header, $payload, $signature] = $parts;
        $expectedSig = self::base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        if (!hash_equals($expectedSig, $signature)) {
            throw new RuntimeException('Assinatura inválida.');
        }

        $data = json_decode(self::base64UrlDecode($payload), true);

        if (!isset($data['exp']) || $data['exp'] < time()) {
            throw new RuntimeException('Token expirado.');
        }

        return $data;
    }

    /** Extrai o token do cabeçalho Authorization: Bearer <token> */
    public static function fromHeader(): ?string
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? apache_request_headers()['Authorization']
            ?? '';

        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }
}
