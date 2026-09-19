<?php
declare(strict_types=1);

namespace LMOnext\Sport;

/**
 * Projekt: LMOnext
 * Filename: src/Sport/SportRegistry.php
 * Fileversion: 1.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Registry: mapt sport_type-Strings auf SportProfile-Instanzen.
 * Ueberall im Code reicht ein Aufruf:
 *
 *   $profile = SportRegistry::get($liga['sport_type'] ?? 'football');
 *
 * Fallback ist immer FootballProfile (volle Abwaertskompatibilitaet).
 */
final class SportRegistry
{
    /** @var array<string, SportProfile>|null */
    private static ?array $instances = null;

    /** Alle verfuegbaren Sportarten registrieren (lazy, einmalig). */
    private static function ensureLoaded(): void
    {
        if (self::$instances !== null) {
            return;
        }
        self::$instances = [
            'football'   => new FootballProfile(),
            'volleyball' => new VolleyballProfile(),
            'icehockey'  => new IceHockeyProfile(),
            'basketball' => new BasketballProfile(),
            'handball'   => new HandballProfile(),
            'badminton'  => new BadmintonProfile(),
        ];
    }

    /** Liefert das Profil fuer $sportType, mit Fallback auf Football. */
    public static function get(string $sportType): SportProfile
    {
        self::ensureLoaded();
        return self::$instances[$sportType] ?? self::$instances['football'];
    }

    /** Alle verfuegbaren Profile (fuer Admin-Dropdown). */
    public static function all(): array
    {
        self::ensureLoaded();
        return self::$instances;
    }
}
