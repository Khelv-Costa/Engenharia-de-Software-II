<?php
/**
 * Config.php — Carregador de configuração centralizado
 * Lê o ficheiro .env e disponibiliza as variáveis de forma segura.
 */

declare(strict_types=1);

class Config
{
    private static array $data = [];
    private static bool  $loaded = false;

    public static function load(string $envFile = __DIR__ . '/.env'): void
    {
        if (self::$loaded) return;

        if (!file_exists($envFile)) {
            throw new RuntimeException("Ficheiro .env não encontrado em: {$envFile}");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Ignora comentários
            if (str_starts_with($line, '#') || !str_contains($line, '=')) continue;

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Remove aspas
            $value = trim($value, '"\'');

            self::$data[$key] = $value;
            putenv("{$key}={$value}");
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$data[$key] ?? getenv($key) ?: $default;
    }

    public static function requireKey(string $key): string
    {
        $val = self::get($key);
        if ($val === null || $val === '') {
            throw new RuntimeException("Variável de ambiente obrigatória não definida: {$key}");
        }
        return (string) $val;
    }
}
