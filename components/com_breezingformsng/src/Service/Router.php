<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterBase;

/**
 * SEF router. URLs carry free-form key/value pairs as alternating
 * segments (legacy BreezingForms URL scheme, kept for existing links).
 */
class Router extends RouterBase
{
    /**
     * @param  array<string, mixed>  $query
     *
     * @return  list<mixed>
     */
    public function build(&$query)
    {
        $segments = [];

        foreach ($query as $key => $value) {
            if (\in_array($key, ['option', 'Itemid', 'lang', 'view', 'form'], true)) {
                continue;
            }

            $segments[] = $key;
            $segments[] = $value;
            unset($query[$key]);
        }

        unset($query['form'], $query['view']);

        return $segments;
    }

    /**
     * @param  array<int, string>  $segments
     *
     * @return  array<string, string>
     */
    public function parse(&$segments)
    {
        $vars = [];
        $count = \count($segments);

        for ($i = 0; $i + 1 < $count; $i += 2) {
            $vars[$segments[$i]] = $segments[$i + 1];
        }

        if ($count % 2 === 1) {
            $vars[$segments[$count - 1]] = '';
        }

        $segments = [];

        return $vars;
    }
}
