<?php
/**
 * Project: LMOnext
 * Filename: src/Home/HomeRepository.php
 * Fileversion: 1.2.0
 *
 * PHP version 8.2
 *
 * @author    Dietmar Kersting <webmaster@liga-manager-online.org>
 * @author    Torsten Hofmann <entwickler@bastel-code.de>
 * @copyright 2026 Dietmar Kersting, Torsten Hofmann
 * @license   GPL-3.0-only
 */
declare(strict_types=1);

namespace LMOnext\Home;

final class HomeRepository
{
    private array $errors = [];

    /** @return array<int, array<string, mixed>> */
    public function getActiveLigenList(): array
    {
        try {
            return \getDB()->query(
                'SELECT l.id, l.name, l.datum, COALESCE(lo.option_value,\'0\') AS type
                   FROM ' . \tbl('liga') . ' l
                   LEFT JOIN ' . \tbl('liga_options') . ' lo ON lo.liga_id=l.id AND lo.option_key="Type"
                  WHERE l.archiv_folder_id IS NULL
                  ORDER BY l.name ASC'
            )->fetchAll();
        } catch (\Throwable $e) {
            $this->errors[] = 'getActiveLigenList: ' . $e->getMessage();
            \error_log('LMOnext HomeRepository::getActiveLigenList(): ' . $e->getMessage());
            return [];
        }
    }

    /** @return array<int, array<int, array<string, mixed>>> */
    public function getArchivedLigenByFolder(): array
    {
        try {
            $rows = \getDB()->query(
                'SELECT l.id, l.name, l.datum, l.archiv_folder_id, COALESCE(lo.option_value,\'0\') AS type
                   FROM ' . \tbl('liga') . ' l
                   LEFT JOIN ' . \tbl('liga_options') . ' lo ON lo.liga_id=l.id AND lo.option_key="Type"
                  WHERE l.archiv_folder_id IS NOT NULL
                  ORDER BY l.name DESC'
            )->fetchAll();
        } catch (\Throwable $e) {
            $this->errors[] = 'getArchivedLigenByFolder: ' . $e->getMessage();
            \error_log('LMOnext HomeRepository::getArchivedLigenByFolder(): ' . $e->getMessage());
            return [];
        }

        $byFolder = [];
        foreach ($rows as $row) {
            $byFolder[(int)$row['archiv_folder_id']][] = $row;
        }

        return $byFolder;
    }

    /** @return array<int, array<int, array<string, mixed>>> */
    public function getArchivFolderTree(): array
    {
        try {
            $folders = \getDB()->query(
                'SELECT id, parent_id, name, beschreibung, sort
                   FROM ' . \tbl('liga_archiv_folders') . '
                  ORDER BY sort, name'
            )->fetchAll();
        } catch (\Throwable $e) {
            $this->errors[] = 'getArchivFolderTree: ' . $e->getMessage();
            \error_log('LMOnext HomeRepository::getArchivFolderTree(): ' . $e->getMessage());
            return [];
        }

        $byParent = [];
        foreach ($folders as $folder) {
            $byParent[(int)($folder['parent_id'] ?? 0)][] = $folder;
        }

        return $byParent;
    }

    public function getLastError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /** @return list<string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
