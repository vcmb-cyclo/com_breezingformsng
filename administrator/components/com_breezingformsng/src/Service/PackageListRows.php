<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

/**
 * The rows of the checkbox lists of the package screens (manage and export): one row
 * per item, with what the shared layout `packages.item-lists` needs to draw it.
 *
 * @phpstan-type Row array{id: int, title: string, rules: int, package: string, saved: bool, checked: bool, url: string}
 */
final class PackageListRows
{
    /** The kinds of items a package holds. */
    public const SECTIONS = ['forms', 'scripts', 'pieces', 'menus'];

    /**
     * Manage screen: the items of the package, ticked, then the items without a package.
     * `saved` is the state stored in the database; `checked` is what the screen shows, which
     * is the input of a refused save when there is one.
     *
     * @param array<string, list<array<string, mixed>>> $choices
     * @param array<string, list<int>>                    $draftSelect Ticked items of a refused save, by kind.
     * @return array<string, list<Row>>
     */
    public static function forUpdate(array $choices, string $selectedPackage, array $draftSelect = []): array
    {
        $rows = [];

        foreach (self::SECTIONS as $section) {
            $members = [];
            $free = [];

            foreach ($choices[$section] ?? [] as $item) {
                $package = (string) $item['package'];

                if ($selectedPackage !== '' && $package === $selectedPackage) {
                    $members[] = self::row($section, $item, true, null, $draftSelect[$section] ?? null);
                } elseif ($package === '') {
                    $free[] = self::row($section, $item, false, null, $draftSelect[$section] ?? null);
                }
            }

            $rows[$section] = array_merge($members, $free);
        }

        return $rows;
    }

    /**
     * Export screen: every item, whatever its package, none ticked (the script ticks those of
     * the chosen package), unless a rejected export is being shown again.
     *
     * @param array<string, list<array<string, mixed>>> $choices
     * @param array<string, list<int>>                    $draftSelect Ticked items of a refused export, by kind.
     * @return array<string, list<Row>>
     */
    public static function forExport(array $choices, array $draftSelect = []): array
    {
        $rows = [];

        foreach (self::SECTIONS as $section) {
            $rows[$section] = array_map(
                static fn (array $item): array => self::row($section, $item, false, null, $draftSelect[$section] ?? null),
                $choices[$section] ?? []
            );
        }

        return $rows;
    }

    /** Editor of an item, in the administrator: a form opens in QuickMode, in its package. */
    public static function editUrl(string $section, int $id, string $package): string
    {
        return match ($section) {
            'forms' => 'index.php?option=com_breezingformsng&task=quickmode.display&form=' . $id . ($package !== '' ? '&pkg=' . rawurlencode($package) : ''),
            'scripts' => 'index.php?option=com_breezingformsng&task=script.edit&id=' . $id,
            'pieces' => 'index.php?option=com_breezingformsng&task=piece.edit&id=' . $id,
            'menus' => 'index.php?option=com_breezingformsng&task=menu.edit&id=' . $id,
            default => throw new \InvalidArgumentException('Unknown package item section: ' . $section),
        };
    }

    /**
     * @param array<string, mixed> $item
     * @param list<int>|null       $draft Ticked items of a refused save, null when there is none.
     * @return Row
     */
    private static function row(string $section, array $item, bool $saved, ?bool $checked, ?array $draft): array
    {
        $id = (int) $item['id'];
        $package = (string) $item['package'];

        return [
            'id' => $id,
            'title' => (string) ($item['title'] ?: $item['name']),
            'rules' => (int) ($item['rules'] ?? 0),
            'package' => $package,
            'saved' => $saved,
            'checked' => $checked ?? ($draft !== null ? in_array($id, $draft, true) : $saved),
            'url' => self::editUrl($section, $id, $package),
        ];
    }
}
