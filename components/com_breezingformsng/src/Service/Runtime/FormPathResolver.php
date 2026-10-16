<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Runtime;

\defined('_JEXEC') or die;

final class FormPathResolver
{
    public function resolve(
        int $page,
        int $lastPage,
        string $formName,
        string $formTitle,
        string $homepage,
        string $sitePath,
        string $siteUrl,
        string $imagesPattern,
        string $uploadsPattern
    ): FormPathContext {
        $tokens = [
            '{ff_currentpage}',
            '{ff_lastpage}',
            '{ff_name}',
            '{ff_title}',
            '{ff_homepage}',
            '{mospath}',
            '{mossite}',
        ];
        $values = [(string) $page, (string) $lastPage, $formName, $formTitle, $homepage, $sitePath, $siteUrl];
        $images = str_replace($tokens, $values, $imagesPattern);
        $tokens[] = '{ff_images}';
        $values[] = $images;
        $uploads = str_replace($tokens, $values, $uploadsPattern);
        $tokens[] = '{ff_uploads}';
        $values[] = $uploads;
        $tokens[] = '{CBSite}';
        $values[] = JPATH_SITE;
        $tokens[] = '{cbsite}';
        $values[] = JPATH_SITE;

        return new FormPathContext($tokens, $values, $images, $uploads);
    }
}
