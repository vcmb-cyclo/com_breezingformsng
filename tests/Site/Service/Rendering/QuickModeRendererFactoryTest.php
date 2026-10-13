<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering;

use HTML_facileFormsProcessor;
use Joomla\CMS\Application\CMSApplication;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\BootstrapRenderer;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\ClassicRenderer;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\OnePageRenderer;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickModeRendererFactory;

if (!defined('JPATH_ADMINISTRATOR')) {
    define('JPATH_ADMINISTRATOR', __DIR__ . '/../../../../administrator');
}

if (!defined('JPATH_SITE')) {
    define('JPATH_SITE', __DIR__ . '/../../../../');
}

require_once __DIR__ . '/QuickMode/joomla-cmsapplication-stub.php';
require_once __DIR__ . '/QuickMode/joomla-uri-stub.php';

if (!class_exists('Joomla\\CMS\\Component\\ComponentHelper')) {
    eval('namespace Joomla\\CMS\\Component; final class ComponentHelper {
        public static function getParams(string $name): object {
            return new class {
                public function get(string $key, mixed $default = null): mixed { return $default; }
            };
        }
    }');
}

if (!function_exists('Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Rendering\\QuickMode\\bf_b64dec')) {
    eval(
        'namespace Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Rendering\\QuickMode; '
        . 'function bf_b64dec(string $value): string { return (string) base64_decode($value, true); }'
    );
}

if (!class_exists(HTML_facileFormsProcessor::class)) {
    require_once __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php';
}

final class QuickModeRendererFactoryTest extends TestCase
{
    public function testCreatesClassicRendererByDefault(): void
    {
        self::assertInstanceOf(
            ClassicRenderer::class,
            $this->factory()->create($this->processor(), [])
        );
    }

    public function testCreatesBootstrapRendererForBootstrapMetadata(): void
    {
        self::assertInstanceOf(
            BootstrapRenderer::class,
            $this->factory()->create($this->processor(), ['themebootstrapThemeEngine' => 'bootstrap'])
        );
    }

    public function testCreatesOnePageRendererForBootstrapMode(): void
    {
        self::assertInstanceOf(
            OnePageRenderer::class,
            $this->factory()->create(
                $this->processor(),
                ['themebootstrapThemeEngine' => 'bootstrap', 'themebootstrapMode' => true]
            )
        );
    }

    private function processor(): HTML_facileFormsProcessor
    {
        $processor = (new ReflectionClass(HTML_facileFormsProcessor::class))->newInstanceWithoutConstructor();
        $processor->app = new CMSApplication();
        $processor->formrow = (object) [
            'template_code' => base64_encode(json_encode([
                'properties' => [
                    'fadeIn' => false,
                    'useErrorAlerts' => false,
                    'rollover' => false,
                    'rolloverColor' => '',
                    'theme' => '',
                ],
            ], JSON_THROW_ON_ERROR)),
        ];

        return $processor;
    }

    private function factory(): QuickModeRendererFactory
    {
        return new QuickModeRendererFactory();
    }
}
