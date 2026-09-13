<?php
/**
 * Project: LMOnext
 * Filename: src/Liga/LigaService.php
 * Fileversion: 1.1.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */
declare(strict_types=1);

namespace LMOnext\Liga;

/**
 * Facade for all functionality formerly implemented in frontend/data_liga.php.
 *
 * The implementation is split into focused PSR-4 loadable traits while this
 * class keeps one stable static API. The legacy global function names are
 * provided by frontend/data_liga.php as compatibility wrappers.
 */
final class LigaService
{
    use LigaRepositoryTrait;
    use SpieltagRepositoryTrait;
    use TeamRepositoryTrait;
    use TeamFormattingTrait;
    // use HeadToHeadTrait; ENTFERNT (Beitrag: Auslagerung als eigenständiges
    // Addon "teamvergleich", siehe CHANGELOG.md) - die Logik lebt jetzt unter
    // addon/teamvergleich/HeadToHead.php, nicht mehr als Trait in dieser
    // Klasse. Aufrufer (RenderViewsTrait, PdfExporter, frontend/data_liga.php)
    // gehen über Hooks statt direkter self::-Methodenaufrufe.
    use TournamentTrait;
    use StandingsTrait;
    use StatisticsTrait;
    use RenderViewsTrait;
}
