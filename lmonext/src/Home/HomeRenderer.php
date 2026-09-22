<?php
/**
 * Project: LMOnext
 * Filename: src/Home/HomeRenderer.php
 * Fileversion: 1.0.0
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

final class HomeRenderer
{
    /**
     * Baut den Archiv-Baum als HTML auf.
     *
     * @param array<int, array<int, array<string, mixed>>> $byParent
     * @param array<int, array<int, array<string, mixed>>> $ligenByFolder
     */
    public function renderArchivFolderTree(
        array $byParent,
        array $ligenByFolder,
        int $parentId = 0
    ): string {
        if (empty($byParent[$parentId])) {
            return '';
        }

        $html = '';
        foreach ($byParent[$parentId] as $folder) {
            $fid = (int)$folder['id'];
            $ligen = $ligenByFolder[$fid] ?? [];
            $childHtml = $this->renderArchivFolderTree($byParent, $ligenByFolder, $fid);
            $hasContent = !empty($ligen) || $childHtml !== '';

            $ligenHtml = '';
            foreach ($ligen as $liga) {
                $ligenHtml .= $this->renderLigaLink($liga);
            }

            $folderContent = $hasContent
                ? '<div class="folder-content">' . $ligenHtml . $childHtml . '</div>'
                : '<div class="folder-content folder-empty">' . \h(\tf('home_folder_empty')) . '</div>';

            $folderDesc = !empty($folder['beschreibung'])
                ? '<span class="folder-desc"> – ' . \h($folder['beschreibung']) . '</span>'
                : '';

            $html .= \renderPartial('archiv_folder', [
                'FolderName'    => \h($folder['name']),
                'FolderDesc'    => $folderDesc,
                'FolderContent' => $folderContent,
            ]);
        }

        return $html;
    }

    /** @param array<string, mixed> $liga */
    public function renderLigaLink(array $liga): string
    {
        $isKO = ($liga['type'] ?? '0') === '1';

        return \renderPartial('liga_list_item', [
            'LigaId'     => (int)$liga['id'],
            'ChipClass'  => $isKO ? 'chip-yellow' : 'chip-blue',
            'TypeLabel'  => $isKO ? \h(\tf('home_type_ko')) : \h(\tf('home_type_liga')),
            'LigaName'   => \h($liga['name']),
        ]);
    }
}
