<?php
declare(strict_types=1);

namespace LMOnext\Core;

use Dotenv\Dotenv;
use RuntimeException;

/**
 * Zentrale Environment-Konfiguration für LMOnext.
 *
 * Die .env liegt im Projekt-Root. Bereits vom Server/Container gesetzte
 * Environment-Variablen werden durch vlucas/phpdotenv nicht überschrieben.
 */
final class Env
{
    private static bool $loaded = false;

    public static function load(?string $root = null): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;
        $root ??= dirname(__DIR__, 2);

        if (!is_file($root . '/.env')) {
            return;
        }

        try {
            Dotenv::createImmutable($root)->safeLoad();
        } catch (\Throwable $e) {
            self::$loaded = false;
            throw new RuntimeException('LMOnext .env konnte nicht geladen werden: ' . $e->getMessage(), 0, $e);
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        self::load();
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return ($value === false || $value === null) ? $default : (string)$value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off', '' => false,
            default => $default,
        };
    }
}
