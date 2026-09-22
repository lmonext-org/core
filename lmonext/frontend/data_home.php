<?php
/**
 * Project: LMOnext
 * Filename: frontend/data_home.php
 * Fileversion: 3.0.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 *
 * Kompatibilitätsschicht für die frühere frontend/data_home.php. Die
 * eigentliche Implementierung liegt jetzt unter src/Home/.
 */
declare(strict_types = 1);

require_once __DIR__ . '/../src/Home/HomeRepository.php';
require_once __DIR__ . '/../src/Home/HomeRenderer.php';
require_once __DIR__ . '/../src/Home/HomeService.php';

function getHomeService() : \LMOnext\Home\HomeService
{
    static $service = null;
    return $service ??= new \LMOnext\Home\HomeService();
}

function getActiveLigenList() : array
{
    return getHomeService()->getActiveLigenList();
}

function getArchivedLigenByFolder() : array
{
    return getHomeService()->getArchivedLigenByFolder();
}

function getArchivFolderTree() : array
{
    return getHomeService()->getArchivFolderTree();
}

function renderArchivFolderTree(array $byParent, array $ligenByFolder, int $parentId = 0) : string
{
    return getHomeService()->renderArchivFolderTree($byParent, $ligenByFolder, $parentId);
}

function renderLigaLink(array $liga) : string
{
    return getHomeService()->renderLigaLink($liga);
}
