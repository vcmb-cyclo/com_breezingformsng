<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\QuickMode;

\defined('_JEXEC') or die;

final class ElementFinder
{
    /**
     * @param array<string, mixed> $node
     *
     * @return array<string, mixed>|null
     */
    public function find(array $node, string $name): ?array
    {
        $properties = $node['properties'] ?? [];

        if (
            ($properties['type'] ?? null) === 'element'
            && ($properties['bfName'] ?? null) === $name
        ) {
            return $node;
        }

        foreach ($node['children'] ?? [] as $child) {
            if (!\is_array($child)) {
                continue;
            }

            $match = $this->find($child, $name);

            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }
}
