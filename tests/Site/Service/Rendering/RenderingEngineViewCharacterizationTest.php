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
use Joomla\CMS\Uri\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\FileExtensionsCheckBuilder;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\RenderingEngine;
use Vcmb\Component\BreezingformsNG\Site\Service\Upload\TokenizedDirectoryResolver;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\BootstrapRenderer;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\ClassicRenderer;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\OnePageRenderer;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickModeRendererFactory;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\CallbackRegistrationService;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\SubmittedCallbackNameResolver;

if (!defined('JPATH_ADMINISTRATOR')) {
    define('JPATH_ADMINISTRATOR', __DIR__ . '/../../../../administrator');
}

if (!defined('JPATH_SITE')) {
    define('JPATH_SITE', __DIR__ . '/../../../../');
}

if (!defined('_FF_RUNMODE_FRONTEND')) {
    define('_FF_RUNMODE_FRONTEND', 0);
}

if (!defined('_FF_RUNMODE_BACKEND')) {
    define('_FF_RUNMODE_BACKEND', 1);
}

if (!class_exists(HTML_facileFormsProcessor::class)) {
    require_once __DIR__ . '/../../../../components/com_breezingformsng/src/Support/processor_facade.php';
}

require_once __DIR__ . '/QuickMode/joomla-text-stub.php';
require_once __DIR__ . '/QuickMode/joomla-uri-stub.php';
require_once __DIR__ . '/QuickMode/joomla-route-stub.php';
require_once __DIR__ . '/QuickMode/joomla-htmlhelper-stub.php';
require_once __DIR__ . '/QuickMode/joomla-cmsapplication-stub.php';

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

if (!function_exists('Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Rendering\\bf_b64dec')) {
    eval(
        'namespace Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Rendering; '
        . 'function bf_b64dec(string $value): string { return (string) base64_decode($value, true); }'
    );
}

if (!function_exists('Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Rendering\\nl')) {
    eval(
        'namespace Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Rendering; '
        . 'function nl(): string { return "\\n"; }'
    );
}

if (!function_exists('Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Rendering\\indentc')) {
    eval(
        'namespace Vcmb\\Component\\BreezingformsNG\\Site\\Service\\Rendering; '
        . 'function indentc(int $level): string { return str_repeat("\\t", $level); }'
    );
}

if (!class_exists('Joomla\\Database\\ParameterType')) {
    eval('namespace Joomla\\Database; final class ParameterType { public const INTEGER = 1; }');
}

/**
 * Initial characterization coverage for RenderingEngine::view().
 *
 * The non-QuickMode guard is deliberately covered first: it is a complete
 * branch that must not initialize the QuickMode runtime or touch the database.
 */
final class RenderingEngineViewCharacterizationTest extends TestCase
{
    public function testNonQuickModeRendersWarningAndStopsBeforeRuntimeSetup(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->formrow = (object) ['template_code_processed' => 'LegacyTemplate'];

        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);

        ob_start();
        try {
            $engine->view();
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertSame(
            '<div class="alert alert-warning">COM_BREEZINGFORMSNG_QUICKMODE_ONLY</div>',
            $html
        );
        self::assertSame(0, $processor->permissionChecks);
    }

    public function testQuickModeStopsBeforeRenderingWhenProcessorCannotRun(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->app = new class {
            public function getInput(): object
            {
                return new class {
                    public function getBool(string $name, bool $default = false): bool
                    {
                        return false;
                    }

                    public function getString(string $name, string $default = ''): string
                    {
                        return $default;
                    }

                    public function getInt(string $name, int $default = 0): int
                    {
                        return $default;
                    }
                };
            }

            public function getSession(): object
            {
                return new class {
                    public function clear(string $name): void
                    {
                    }

                    public function set(string $name, mixed $value): void
                    {
                    }
                };
            }
        };
        $processor->formrow = (object) [
            'template_code_processed' => 'QuickMode',
            'template_code' => base64_encode(json_encode([
                'properties' => [
                    'themebootstrapThemeEngine' => 'bootstrap',
                ],
            ], JSON_THROW_ON_ERROR)),
        ];
        $processor->okrun = false;

        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);

        ob_start();
        try {
            $engine->view();
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertSame('', $html);
        self::assertSame(1, $processor->permissionChecks);
    }

    public function testViewAbortsAfterBeforeFormPieceWhenPieceRequestsBury(): void
    {
        $processor = $this->makeProcessorReadyForCaptchaScript([]);
        $processor->formrow->piece1cond = 2;
        $processor->formrow->piece1code = 'before';
        $processor->buryOnCallNumber = 1;

        $html = $this->captureCaptchaScript($processor);

        self::assertStringContainsString('<piece>before</piece>', $html);
        self::assertStringNotContainsString('function ffCheckCaptcha()', $html);
        self::assertSame('before', $processor->executedPieces[0]['code']);
        self::assertSame('f', $processor->executedPieces[0]['type']);
    }

    public function testViewAbortsAfterFormCallbacksBeforeElementSections(): void
    {
        $processor = $this->makeProcessorReadyForCaptchaScript([]);
        $processor->formrow->name = 'contact';
        $processor->formrow->script1cond = 0;
        $processor->formrow->script1id = 0;
        $processor->formrow->script1code = 'init';
        $processor->formrow->script2cond = 0;
        $processor->formrow->script2id = 0;
        $processor->formrow->script2code = 'submitted';
        $processor->buryOnCallNumber = 4;

        $html = $this->captureCaptchaScript($processor);

        self::assertSame(['ff_contact_init', 'ff_contact_submitted'], $processor->callbackNames);
        self::assertStringNotContainsString('function ff_dispQueryPage', $html);
        self::assertStringNotContainsString('formValidationClose', $html);
    }

    public function testPermissionsReturnNeutralContextWhenContentBuilderIsUnavailable(): void
    {
        $processor = (new ReflectionClass(HTML_facileFormsProcessor::class))->newInstanceWithoutConstructor();
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);

        self::assertSame(
            [
                'form' => null,
                'record' => null,
                'frontend' => true,
                'data' => null,
                'full' => false,
            ],
            $engine->cbCheckPermissions()
        );
    }

    public function testFileExtensionValidationScriptCoversConfiguredUploads(): void
    {
        $processor = (new ReflectionClass(HTML_facileFormsProcessor::class))->newInstanceWithoutConstructor();
        $processor->formrow = (object) ['template_code' => 'encoded-template'];
        $processor->rowcount = 2;
        $processor->rows = [
            (object) ['type' => 'File Upload', 'data2' => 'PDF, jpg', 'id' => 21, 'page' => 2],
            (object) ['type' => 'File Upload', 'data2' => '', 'id' => 22, 'page' => 1],
        ];

        [$script, $count] = (new FileExtensionsCheckBuilder())->build(
            $processor->rows,
            $processor->rowcount,
            '"Extension not allowed"',
            true
        );

        self::assertSame(1, $count);
        self::assertStringContainsString('ff_elem21Exts', $script);
        self::assertStringContainsString('lastIndexOf(".pdf")', $script);
        self::assertStringContainsString('lastIndexOf(".jpg")', $script);
        self::assertStringContainsString('return true;', $script);
        self::assertStringNotContainsString('ff_elem22Exts', $script);

        $processor->formrow->template_code = '';
        [$emptyTemplateScript, $emptyTemplateCount] = (new FileExtensionsCheckBuilder())->build(
            $processor->rows,
            $processor->rowcount,
            '"Extension not allowed"',
            false
        );

        self::assertSame(0, $emptyTemplateCount);
        self::assertStringContainsString('return true;', $emptyTemplateScript);
        self::assertStringNotContainsString('ff_elem21Exts', $emptyTemplateScript);
    }

    public function testScriptLibraryStateLoadsBuiltinsAndScripts(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);

        $method = (new ReflectionClass($engine))->getMethod('createScriptLibraryState');
        [$library, $linked] = $method->invoke($engine);

        self::assertSame(['builtin' => 'loaded', 'script' => 'loaded'], $library);
        self::assertSame([], $linked);
    }

    public function testQuickModeMetadataLoadsPropertiesFromEncodedTemplate(): void
    {
        $processor = (new ReflectionClass(HTML_facileFormsProcessor::class))->newInstanceWithoutConstructor();
        $processor->formrow = (object) [
            'template_code' => base64_encode(json_encode([
                'properties' => [
                    'themebootstrapThemeEngine' => 'bootstrap',
                    'themebootstrapMode' => true,
                ],
                'elements' => [['type' => 'bfTextfield']],
            ], JSON_THROW_ON_ERROR)),
        ];
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);

        $method = (new ReflectionClass($engine))->getMethod('loadQuickModeMetadata');

        self::assertSame([
            'themebootstrapThemeEngine' => 'bootstrap',
            'themebootstrapMode' => true,
        ], $method->invoke($engine));
    }

    public function testFormScriptsStopBetweenInitAndSubmittedWhenProcessorIsBuried(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->form = 7;
        $processor->formrow = (object) [
            'name' => 'contact',
            'script1cond' => 0,
            'script1id' => 0,
            'script1code' => '',
            'script2cond' => 0,
            'script2id' => 0,
            'script2code' => '',
        ];
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $method = (new ReflectionClass($engine))->getMethod('addFormScripts');
        $library = [];
        $linked = [];

        self::assertFalse($method->invokeArgs($engine, [&$library, &$linked]));
        self::assertSame([
            'ff_contact_init',
            'ff_contact_submitted',
        ], $processor->callbackNames);

        $processor->callbackNames = [];
        $processor->buryAfterFirstCallback = true;
        self::assertTrue($method->invokeArgs($engine, [&$library, &$linked]));
        self::assertSame(['ff_contact_init'], $processor->callbackNames);
    }

    public function testCallbackRegistrationServiceRegistersFormCallbacksDirectly(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $service = new CallbackRegistrationService($processor);
        $library = [];
        $linked = [];

        self::assertFalse($service->registerForm(
            (object) [
                'name' => 'contact',
                'script1cond' => 0,
                'script1id' => 1,
                'script1code' => 'init',
                'script2cond' => 0,
                'script2id' => 2,
                'script2code' => 'submitted',
            ],
            $library,
            $linked,
            12
        ));
        self::assertSame(['ff_contact_init', 'ff_contact_submitted'], $processor->callbackNames);
    }

    public function testSubmittedCallbackNameResolverPreservesCustomAndEmptyModes(): void
    {
        $resolver = new SubmittedCallbackNameResolver(null);

        self::assertSame(
            'ff_contact_submitted',
            $resolver->resolve((object) ['script2cond' => 2, 'name' => 'contact'])
        );
        self::assertSame('', $resolver->resolve((object) ['script2cond' => 0, 'name' => 'contact']));
    }

    public function testInitialOnloadInitializesFormPageGridAndHeight(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->formrow = (object) ['heightmode' => 2, 'height' => 480];
        $processor->showgrid = true;
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $library = [];
        $linked = [];

        (new ReflectionClass($engine))->getMethod('linkInitialOnload')->invokeArgs($engine, [&$library, &$linked]);

        self::assertCount(1, $processor->linkedCallbacks);
        self::assertSame('onload', $processor->linkedCallbacks[0]['function']);
        self::assertStringContainsString("ff_initialize('formentry');", $processor->linkedCallbacks[0]['code']);
        self::assertStringContainsString("ff_initialize('pageentry');", $processor->linkedCallbacks[0]['code']);
        self::assertStringContainsString('ff_resizepage(2, 480);', $processor->linkedCallbacks[0]['code']);
        self::assertStringContainsString('ff_showgrid();', $processor->linkedCallbacks[0]['code']);
    }

    public function testSubmittedOnloadLinksCustomCallbackAndPresentationHooks(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->status = 5;
        $processor->message = '<saved>';
        $processor->showgrid = true;
        $processor->formrow = (object) [
            'name' => 'contact',
            'script2cond' => 2,
            'script2id' => 0,
            'heightmode' => 1,
            'height' => 360,
        ];
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $library = [];
        $linked = [];

        (new ReflectionClass($engine))->getMethod('linkSubmittedOnload')->invokeArgs($engine, [&$library, &$linked]);

        self::assertCount(1, $processor->linkedCallbacks);
        self::assertSame('onload', $processor->linkedCallbacks[0]['function']);
        self::assertStringContainsString('ff_resizepage(1, 360);', $processor->linkedCallbacks[0]['code']);
        self::assertStringContainsString('ff_showgrid();', $processor->linkedCallbacks[0]['code']);
        self::assertStringContainsString(
            'ff_contact_submitted(5,"\\u003Csaved\\u003E");',
            $processor->linkedCallbacks[0]['code']
        );
    }

    public function testSubmittedOnloadIsOmittedWithoutCallbackOrPresentationHooks(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->status = 1;
        $processor->message = '';
        $processor->showgrid = false;
        $processor->formrow = (object) [
            'name' => 'contact',
            'script2cond' => 0,
            'script2id' => 0,
            'heightmode' => 0,
            'height' => 0,
        ];
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $library = [];
        $linked = [];

        (new ReflectionClass($engine))->getMethod('linkSubmittedOnload')->invokeArgs($engine, [&$library, &$linked]);

        self::assertSame([], $processor->linkedCallbacks);
    }

    public function testSubmittedOnloadResolvesLibraryCallback(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->status = 2;
        $processor->message = 'saved';
        $processor->showgrid = false;
        $processor->formrow = (object) [
            'name' => 'contact',
            'script2cond' => 1,
            'script2id' => 18,
            'heightmode' => 0,
            'height' => 0,
        ];
        $processor->database = new class {
            public function createQuery(): object
            {
                return new class {
                    public function select(string $columns): self
                    {
                        return $this;
                    }

                    public function from(string $table): self
                    {
                        return $this;
                    }

                    public function where(string $condition): self
                    {
                        return $this;
                    }

                    public function bind(string $key, int $value, mixed $type): self
                    {
                        return $this;
                    }
                };
            }

            public function quoteName(string $name): string
            {
                return $name;
            }

            public function setQuery(object $query): void
            {
            }

            public function loadResult(): string
            {
                return 'ff_library_submitted';
            }
        };
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $library = [];
        $linked = [];

        (new ReflectionClass($engine))->getMethod('linkSubmittedOnload')->invokeArgs($engine, [&$library, &$linked]);

        self::assertCount(1, $processor->linkedCallbacks);
        self::assertStringContainsString('ff_library_submitted(2,"saved");', $processor->linkedCallbacks[0]['code']);
    }

    public function testSubmittedOnloadLinksPresentationHooksWithoutCallback(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->status = 1;
        $processor->message = '';
        $processor->showgrid = true;
        $processor->formrow = (object) [
            'name' => 'contact',
            'script2cond' => 0,
            'script2id' => 0,
            'heightmode' => 3,
            'height' => 420,
        ];
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $library = [];
        $linked = [];

        (new ReflectionClass($engine))->getMethod('linkSubmittedOnload')->invokeArgs($engine, [&$library, &$linked]);

        self::assertCount(1, $processor->linkedCallbacks);
        self::assertStringContainsString('ff_resizepage(3, 420);', $processor->linkedCallbacks[0]['code']);
        self::assertStringContainsString('ff_showgrid();', $processor->linkedCallbacks[0]['code']);
        self::assertStringNotContainsString('_submitted(', $processor->linkedCallbacks[0]['code']);
    }

    public function testFormRenderingInitializationBuildsQuickModeWrapperAndRequestState(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->form = 12;
        $processor->queryCols = ['stale'];
        $processor->queryRows = ['stale'];
        $processor->formrow = (object) [
            'template_code_processed' => 'QuickMode',
            'class1' => 'form-class',
        ];
        $processor->app = new class {
            public function getInput(): object
            {
                return new class {
                    public function getCmd(string $name, string $default = ''): string
                    {
                        return $name === 'ff_status' ? 'completed' : $default;
                    }

                    public function getString(string $name, string $default = ''): string
                    {
                        return $name === 'ff_message' ? 'Saved' : $default;
                    }
                };
            }
        };
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);

        ob_start();
        try {
            (new ReflectionClass($engine))->getMethod('initializeFormRendering')->invoke($engine);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertSame([], $processor->queryCols);
        self::assertSame([], $processor->queryRows);
        self::assertSame('completed', $processor->status);
        self::assertSame('Saved', $processor->message);
        self::assertStringContainsString('id="ff_formdiv12"', $html);
        self::assertStringContainsString('bfFormDiv resolved-form-class', $html);
    }

    public function testFormRenderingInitializationBuildsFormWrapperMarkup(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->form = 4;
        $processor->formrow = (object) [
            'template_code_processed' => 'QuickMode',
            'class1' => '',
        ];
        $processor->app = new class {
            public function getInput(): object
            {
                return new class {
                    public function getCmd(string $name, string $default = ''): string
                    {
                        return $default;
                    }

                    public function getString(string $name, string $default = ''): string
                    {
                        return $default;
                    }
                };
            }
        };
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);

        ob_start();
        try {
            (new ReflectionClass($engine))->getMethod('initializeFormRendering')->invoke($engine);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertSame('<div id="ff_formdiv4" class="bfFormDiv">', $html);
        self::assertSame('', $processor->status);
        self::assertSame('', $processor->message);
    }

    public function testBeforeFormCustomPieceRendersAndPropagatesBuryState(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->form = 9;
        $processor->formrow = (object) [
            'piece1cond' => 2,
            'piece1code' => 'echo "before";',
        ];
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $method = (new ReflectionClass($engine))->getMethod('executeBeforeFormPiece');

        ob_start();
        try {
            $buried = $method->invoke($engine);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertFalse($buried);
        self::assertSame('<piece>echo "before";</piece>', $html);
        self::assertSame('f', $processor->executedPieces[0]['type']);
        self::assertSame(9, $processor->executedPieces[0]['id']);
        self::assertSame(2, $processor->executedPieces[0]['pane']);

        $processor->buryImmediately = true;
        ob_start();
        try {
            self::assertTrue($method->invoke($engine));
        } finally {
            ob_end_clean();
        }
    }

    public function testBeforeFormLibraryPieceResolvesAndRenders(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->form = 9;
        $processor->formrow = (object) [
            'piece1cond' => 1,
            'piece1id' => 24,
        ];
        $processor->database = new class {
            public function createQuery(): object
            {
                return new class {
                    public function select(mixed $columns): self
                    {
                        return $this;
                    }

                    public function from(string $table): self
                    {
                        return $this;
                    }

                    public function where(string $condition): self
                    {
                        return $this;
                    }

                    public function bind(string $key, int $value, mixed $type): self
                    {
                        return $this;
                    }
                };
            }

            public function quoteName(string $name): string
            {
                return $name;
            }

            public function setQuery(object $query): void
            {
            }

            /** @return list<object> */
            public function loadObjectList(): array
            {
                return [(object) ['name' => 'Library piece', 'code' => 'echo "library";']];
            }
        };
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $method = (new ReflectionClass($engine))->getMethod('executeBeforeFormPiece');

        ob_start();
        try {
            self::assertFalse($method->invoke($engine));
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertSame('<piece>echo "library";</piece>', $html);
        self::assertSame('p', $processor->executedPieces[0]['type']);
        self::assertSame(24, $processor->executedPieces[0]['id']);
        self::assertNull($processor->executedPieces[0]['pane']);
        self::assertStringContainsString(
            'COM_BREEZINGFORMSNG_PROCESS_BFPIECE Library piece',
            $processor->executedPieces[0]['name']
        );
    }

    public function testAfterFormCustomPieceRendersAndPropagatesBuryState(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->form = 9;
        $processor->formrow = (object) [
            'piece2cond' => 2,
            'piece2code' => 'echo "after";',
        ];
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $method = (new ReflectionClass($engine))->getMethod('executeAfterFormPiece');

        ob_start();
        try {
            $buried = $method->invoke($engine);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertFalse($buried);
        self::assertSame('<piece>echo "after";</piece>', $html);
        self::assertSame('f', $processor->executedPieces[0]['type']);
        self::assertSame(9, $processor->executedPieces[0]['id']);
        self::assertSame(2, $processor->executedPieces[0]['pane']);

        $processor->buryImmediately = true;
        ob_start();
        try {
            self::assertTrue($method->invoke($engine));
        } finally {
            ob_end_clean();
        }
    }

    public function testAfterFormLibraryPieceResolvesAndRenders(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->form = 9;
        $processor->formrow = (object) [
            'piece2cond' => 1,
            'piece2id' => 25,
        ];
        $processor->database = new class {
            public function createQuery(): object
            {
                return new class {
                    public function select(mixed $columns): self
                    {
                        return $this;
                    }

                    public function from(string $table): self
                    {
                        return $this;
                    }

                    public function where(string $condition): self
                    {
                        return $this;
                    }

                    public function bind(string $key, int $value, mixed $type): self
                    {
                        return $this;
                    }
                };
            }

            public function quoteName(string $name): string
            {
                return $name;
            }

            public function setQuery(object $query): void
            {
            }

            /** @return list<object> */
            public function loadObjectList(): array
            {
                return [(object) ['name' => 'After library piece', 'code' => 'echo "after library";']];
            }
        };
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $method = (new ReflectionClass($engine))->getMethod('executeAfterFormPiece');

        ob_start();
        try {
            self::assertFalse($method->invoke($engine));
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertSame('<piece>echo "after library";</piece>', $html);
        self::assertSame('p', $processor->executedPieces[0]['type']);
        self::assertSame(25, $processor->executedPieces[0]['id']);
        self::assertNull($processor->executedPieces[0]['pane']);
        self::assertStringContainsString(
            'COM_BREEZINGFORMSNG_PROCESS_AFPIECE After library piece',
            $processor->executedPieces[0]['name']
        );
    }

    public function testFormRenderingClosureEmitsWrapperCloseTag(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $method = (new ReflectionClass($engine))->getMethod('closeFormRendering');

        ob_start();
        try {
            $method->invoke($engine);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertSame("</div><!-- form end -->\n", $html);
    }

    public function testViewFinalizationPreservesTraceOrdering(): void
    {
        $method = (new ReflectionClass(RenderingEngine::class))->getMethod('finishViewRendering');
        $initialBufferLevel = ob_get_level();

        foreach ([false, true] as $directTrace) {
            $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
            $processor->traceMode = $directTrace ? _FF_TRACEMODE_DIRECT : 0;
            $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
            (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
            set_error_handler(static function (): bool {
                return false;
            });
            ob_start();
            ob_start();

            try {
                $method->invoke($engine);
                self::assertSame($initialBufferLevel + 1, ob_get_level());
                self::assertSame($directTrace ? 'trace</pre>' : 'trace', ob_get_contents());
                self::assertSame(['dumpTrace'], $processor->traceEvents);
            } finally {
                while (ob_get_level() > $initialBufferLevel) {
                    ob_end_clean();
                }
            }
        }
    }

    public function testMakeSafeFolderDelegatesToTokenizedDirectoryResolver(): void
    {
        $processor = (new ReflectionClass(HTML_facileFormsProcessor::class))->newInstanceWithoutConstructor();
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        $engineReflection = new ReflectionClass($engine);
        $engineReflection->getProperty('processor')->setValue($engine, $processor);
        $resolver = (new ReflectionClass(TokenizedDirectoryResolver::class))->newInstanceWithoutConstructor();
        $engineReflection->getProperty('tokenizedDirectoryResolverService')->setValue($engine, $resolver);

        self::assertSame(
            'uploads/user_name/{field}/file_.txt',
            $engine->makeSafeFolder('uploads/user name/{field}/file?.txt')
        );
    }

    public function testHeaderRendersProcessorVariablesThroughSharedHeaderRenderer(): void
    {
        $processor = (new ReflectionClass(HTML_facileFormsProcessor::class))->newInstanceWithoutConstructor();
        $processor->app = new CMSApplication();
        $processor->okrun = true;
        $processor->ip = '127.0.0.1';
        $processor->agent = 'Test Agent';
        $processor->browser = 'Test Browser';
        $processor->opsys = 'Test OS';
        $processor->provider = 'Test Provider';
        $processor->submitted = 0;
        $processor->form = 12;
        $processor->form_id = 12;
        $processor->page = 1;
        $processor->target = '';
        $processor->runmode = 0;
        $processor->inframe = 0;
        $processor->inline = 0;
        $processor->template = 0;
        $processor->homepage = 'https://example.test';
        $processor->mossite = 'https://example.test';
        $processor->images = 0;
        $processor->border = 0;
        $processor->align = '';
        $processor->top = 0;
        $processor->suffix = '';
        $processor->status = '';
        $processor->message = '';
        $processor->record_id = 0;
        $processor->showgrid = false;
        $processor->traceBuffer = '';

        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);

        $GLOBALS['ff_config'] = (object) ['compress' => false];

        $script = $engine->header();

        self::assertStringContainsString('ff_processor = new Object();', $script);
        self::assertStringContainsString('ff_processor.form', $script);
        self::assertStringContainsString('= 12;', $script);
        self::assertStringContainsString("ff_processor.ip", $script);
        self::assertStringContainsString("'127.0.0.1';", $script);
    }

    /**
     * Drives view() end-to-end through the QuickMode preamble, header,
     * before-form piece and file-extension/CAPTCHA script emission, then
     * stops right after that script tag is echoed - before the (unrelated,
     * much larger) per-element rendering loop - via a bury() call counted
     * to fire on its second invocation (the first, inside
     * executeBeforeFormPiece(), must return false to let rendering
     * continue).
     *
     * @param list<object> $rows
     */
    private function makeProcessorReadyForCaptchaScript(array $rows): RenderingEngineProcessorDouble
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->app = new CMSApplication();
        $processor->formrow = (object) [
            'template_code_processed' => 'QuickMode',
            'template_code' => base64_encode(json_encode([
                'properties' => [
                    'themebootstrapThemeEngine' => 'bootstrap',
                ],
            ], JSON_THROW_ON_ERROR)),
            'class1' => '',
            'piece1cond' => 0,
            'heightmode' => 0,
            'height' => 0,
        ];
        $processor->okrun = true;
        $processor->form = 7;
        $processor->status = '';
        $processor->message = '';
        $processor->showgrid = false;
        $processor->ip = '127.0.0.1';
        $processor->agent = 'Test Agent';
        $processor->browser = 'Test Browser';
        $processor->opsys = 'Test OS';
        $processor->provider = 'Test Provider';
        $processor->submitted = 0;
        $processor->form_id = 7;
        $processor->page = 1;
        $processor->target = '';
        $processor->runmode = 0;
        $processor->inframe = 0;
        $processor->inline = 0;
        $processor->template = 0;
        $processor->homepage = 'https://example.test';
        $processor->mossite = 'https://example.test';
        $processor->images = 0;
        $processor->border = 0;
        $processor->align = '';
        $processor->top = 0;
        $processor->suffix = '';
        $processor->record_id = 0;
        $processor->traceBuffer = '';
        $processor->rows = $rows;
        $processor->rowcount = count($rows);
        $processor->buryOnCallNumber = 2;

        $GLOBALS['ff_config'] = (object) ['compress' => false];

        return $processor;
    }

    private function captureCaptchaScript(RenderingEngineProcessorDouble $processor): string
    {
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);

        $outputBufferLevel = ob_get_level();
        ob_start();
        try {
            $engine->view();
            self::assertSame($outputBufferLevel + 1, ob_get_level());

            return (string) ob_get_contents();
        } finally {
            while (ob_get_level() > $outputBufferLevel) {
                ob_end_clean();
            }
        }
    }

    public function testCaptchaScriptUsesDefaultCallbackWithoutACaptchaRow(): void
    {
        $html = $this->captureCaptchaScript($this->makeProcessorReadyForCaptchaScript([]));

        self::assertStringContainsString(
            'function bfCheckCaptcha(){if(checkFileExtensions())ff_submitForm2();}',
            $html
        );
        self::assertStringNotContainsString('bfAjaxObject101', $html);
        self::assertStringNotContainsString('bfReCaptchaLoaded', $html);
    }

    public function testCaptchaScriptBuildsLegacyAjaxCallbackForCaptchaRow(): void
    {
        $html = $this->captureCaptchaScript($this->makeProcessorReadyForCaptchaScript([
            (object) ['type' => 'Captcha', 'page' => 3],
        ]));

        self::assertStringContainsString('function bfAjaxObject101()', $html);
        self::assertStringContainsString('function bfCheckCaptcha(){', $html);
        self::assertStringContainsString('if(ff_currentpage != 3)ff_switchpage(3);', $html);
        self::assertStringContainsString(
            'alert("COM_BREEZINGFORMSNG_CAPTCHA_MISSING_WRONG");',
            $html
        );
        self::assertStringNotContainsString('bfReCaptchaLoaded', $html);
    }

    public function testCaptchaScriptBuildsReCaptchaCallbackForReCaptchaRow(): void
    {
        $html = $this->captureCaptchaScript($this->makeProcessorReadyForCaptchaScript([
            (object) ['type' => 'ReCaptcha', 'page' => 5],
        ]));

        self::assertStringContainsString('var bfReCaptchaLoaded = true;', $html);
        self::assertStringContainsString('function bfValidateCaptcha()', $html);
        self::assertStringContainsString('if(ff_currentpage != 5)ff_switchpage(5);', $html);
        self::assertStringContainsString(
            'inlineErrorElements.push(["bfReCaptchaEntry","COM_BREEZINGFORMSNG_CAPTCHA_MISSING_WRONG"]);',
            $html
        );
        self::assertStringNotContainsString('bfAjaxObject101', $html);
    }

    public function testCaptchaScriptStopsAtFirstCaptchaRowButLastReCaptchaRowWins(): void
    {
        $html = $this->captureCaptchaScript($this->makeProcessorReadyForCaptchaScript([
            (object) ['type' => 'ReCaptcha', 'page' => 1],
            (object) ['type' => 'ReCaptcha', 'page' => 9],
        ]));

        // The loop has no break on ReCaptcha, so with two such rows the last
        // one silently overwrites $capFunc - preserved verbatim from the
        // pre-extraction behavior, not "fixed".
        self::assertStringContainsString('if(ff_currentpage != 9)ff_switchpage(9);', $html);
        self::assertStringNotContainsString('if(ff_currentpage != 1)ff_switchpage(1);', $html);
    }

    public function testRegisterIconBorderScriptsLinksBothCallbacksWhenNotBuried(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $library = [];
        $linked = [];

        $shouldReturn = (new ReflectionClass($engine))->getMethod('registerIconBorderScripts')
            ->invokeArgs($engine, [&$library, &$linked]);

        self::assertFalse($shouldReturn);
        self::assertSame(
            ['ff_hideIconBorder', 'ff_dispIconBorder'],
            array_column($processor->linkedCallbacks, 'function')
        );
        self::assertStringContainsString('element.style.border = "none";', $processor->linkedCallbacks[0]['code']);
        self::assertStringContainsString(
            'element.style.border = "1px outset";',
            $processor->linkedCallbacks[1]['code']
        );
    }

    public function testRegisterIconBorderScriptsStopsAfterFirstCallbackWhenBuried(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->buryOnCallNumber = 1;
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $library = [];
        $linked = [];

        $shouldReturn = (new ReflectionClass($engine))->getMethod('registerIconBorderScripts')
            ->invokeArgs($engine, [&$library, &$linked]);

        self::assertTrue($shouldReturn);
        self::assertSame(['ff_hideIconBorder'], array_column($processor->linkedCallbacks, 'function'));
    }

    public function testRegisterIconBorderScriptsBuriesAfterSecondCallback(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->buryOnCallNumber = 2;
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $library = [];
        $linked = [];

        $shouldReturn = (new ReflectionClass($engine))->getMethod('registerIconBorderScripts')
            ->invokeArgs($engine, [&$library, &$linked]);

        self::assertTrue($shouldReturn);
        self::assertSame(
            ['ff_hideIconBorder', 'ff_dispIconBorder'],
            array_column($processor->linkedCallbacks, 'function')
        );
    }

    public function testRegisterElementCallbacksPreservesCallbackOrder(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $library = [];
        $linked = [];

        $shouldReturn = (new ReflectionClass($engine))->getMethod('registerElementCallbacks')->invokeArgs(
            $engine,
            [$this->elementCallbackRow(), &$library, &$linked]
        );

        self::assertFalse($shouldReturn);
        self::assertSame(
            ['ff_contact_init', 'ff_contact_action', 'ff_contact_validate'],
            $processor->callbackNames
        );
    }

    /** @param list<string> $expectedCallbacks */
    #[DataProvider('elementCallbackBuryProvider')]
    public function testRegisterElementCallbacksStopsAtEachBuryPoint(
        int $buryCall,
        array $expectedCallbacks,
        bool $needsOutputBuffer
    ): void {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->buryOnCallNumber = $buryCall;
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $library = [];
        $linked = [];
        $initialBufferLevel = ob_get_level();

        if ($needsOutputBuffer) {
            ob_start();
        }

        try {
            $shouldReturn = (new ReflectionClass($engine))->getMethod('registerElementCallbacks')->invokeArgs(
                $engine,
                [$this->elementCallbackRow(), &$library, &$linked]
            );
        } finally {
            while (ob_get_level() > $initialBufferLevel) {
                ob_end_clean();
            }
        }

        self::assertTrue($shouldReturn);
        self::assertSame($expectedCallbacks, $processor->callbackNames);
    }

    /**
     * @return iterable<string, array{int, list<string>, bool}>
     */
    public static function elementCallbackBuryProvider(): iterable
    {
        yield 'after init' => [1, ['ff_contact_init'], false];
        yield 'after action' => [2, ['ff_contact_init', 'ff_contact_action'], false];
        yield 'after validation' => [3, ['ff_contact_init', 'ff_contact_action', 'ff_contact_validate'], true];
    }

    private function elementCallbackRow(): object
    {
        return (object) [
            'id' => 17,
            'name' => 'contact',
            'script1cond' => 0,
            'script1id' => 101,
            'script1code' => 'init',
            'script2cond' => 0,
            'script2id' => 102,
            'script2code' => 'action',
            'script3cond' => 0,
            'script3id' => 103,
            'script3code' => 'validate',
        ];
    }

    public function testCollectElementMetadataCountsOnlyIconsAndTooltips(): void
    {
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        $icons = 0;
        $tooltips = 0;
        $method = (new ReflectionClass($engine))->getMethod('collectElementMetadata');

        $method->invokeArgs($engine, [(object) ['type' => 'Icon'], &$icons, &$tooltips]);
        $method->invokeArgs($engine, [(object) ['type' => 'Tooltip'], &$icons, &$tooltips]);
        $method->invokeArgs($engine, [(object) ['type' => 'Text'], &$icons, &$tooltips]);

        self::assertSame(1, $icons);
        self::assertSame(1, $tooltips);
    }

    public function testRegisterStaticTextScanCallbackOnlyHandlesStaticHtml(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        $library = [];
        $linked = [];
        $method = (new ReflectionClass($engine))->getMethod('registerStaticTextScanCallback');

        $method->invokeArgs(
            $engine,
            [(object) ['type' => 'Static Text/HTML', 'data1' => '<p>content</p>'], &$library, &$linked]
        );
        $method->invokeArgs(
            $engine,
            [(object) ['type' => 'Text', 'data1' => 'ignored'], &$library, &$linked]
        );

        self::assertCount(1, $processor->linkedCallbacks);
        self::assertSame('#scanonly', $processor->linkedCallbacks[0]['function']);
        self::assertSame('<p>content</p>', $processor->linkedCallbacks[0]['code']);
    }

    public function testViewRegistersElementCallbacksAndStaticTextScanCallbackInOrder(): void
    {
        $processor = $this->makeProcessorReadyForCaptchaScript([
            (object) [
                'id' => 31,
                'type' => 'Static Text/HTML',
                'name' => 'intro',
                'data1' => '<p>content</p>',
                'script1cond' => 0,
                'script1id' => 0,
                'script1code' => 'element init',
                'script2cond' => 0,
                'script2id' => 0,
                'script2code' => 'element action',
                'script3cond' => 0,
                'script3id' => 0,
                'script3code' => 'element validate',
            ],
        ]);
        $processor->formrow->script1cond = 0;
        $processor->formrow->script1id = 0;
        $processor->formrow->script1code = '';
        $processor->formrow->script2cond = 0;
        $processor->formrow->script2id = 0;
        $processor->formrow->script2code = '';
        $processor->buryOnCallNumber = 8;

        $this->captureCaptchaScript($processor);

        self::assertSame(
            ['ff_intro_init', 'ff_intro_action', 'ff_intro_validate'],
            array_slice($processor->callbackNames, -3)
        );
        self::assertSame('#scanonly', $processor->linkedCallbacks[1]['function']);
        self::assertSame('<p>content</p>', $processor->linkedCallbacks[1]['code']);
        self::assertSame(['ff_div31'], $processor->draggableDivIds);
    }

    public function testViewPreparesQueryListAndPreservesItsBuryPoint(): void
    {
        $processor = $this->makeProcessorReadyForCaptchaScript([
            (object) [
                'id' => 32,
                'type' => 'Query List',
                'flag1' => 1,
                'flag2' => 1,
                'height' => 15,
                'data1' => '',
                'data3' => '',
                'page' => 1,
                'name' => 'results',
                'script1cond' => 0,
                'script1id' => 0,
                'script1code' => '',
                'script2cond' => 0,
                'script2id' => 0,
                'script2code' => '',
                'script3cond' => 0,
                'script3id' => 0,
                'script3code' => '',
            ],
        ]);
        $processor->formrow->name = 'query';
        $processor->formrow->script1cond = 0;
        $processor->formrow->script1id = 0;
        $processor->formrow->script1code = '';
        $processor->formrow->script2cond = 0;
        $processor->formrow->script2id = 0;
        $processor->formrow->script2code = '';
        $processor->queryResultRows = [['result', 1]];
        $processor->buryOnCallNumber = 5;

        $this->captureCaptchaScript($processor);

        self::assertSame([['result', 1]], $processor->queryRows['ff_32']);
        self::assertSame([], $processor->queryCols['ff_32']);
        self::assertSame(['ff_div32'], $processor->draggableDivIds);
    }

    public function testViewLinksQueryListLibraryBeforeTheNextBuryPoint(): void
    {
        $processor = $this->makeProcessorReadyForCaptchaScript([
            (object) [
                'id' => 33,
                'type' => 'Query List',
                'flag1' => 1,
                'flag2' => 0,
                'height' => 15,
                'data1' => '',
                'data3' => '',
                'page' => 1,
                'name' => 'results',
                'script1cond' => 0,
                'script1id' => 0,
                'script1code' => '',
                'script2cond' => 0,
                'script2id' => 0,
                'script2code' => '',
                'script3cond' => 0,
                'script3id' => 0,
                'script3code' => '',
            ],
        ]);
        $processor->formrow->name = 'query';
        $processor->formrow->script1cond = 0;
        $processor->formrow->script1id = 0;
        $processor->formrow->script1code = '';
        $processor->formrow->script2cond = 0;
        $processor->formrow->script2id = 0;
        $processor->formrow->script2code = '';
        $processor->queryResultRows = [['result', 1]];
        $processor->buryOnCallNumber = 10;

        $this->captureCaptchaScript($processor);

        self::assertSame(
            'ff_dispQueryPage',
            $processor->linkedCallbacks[array_key_last($processor->linkedCallbacks)]['function']
        );
        $queryCode = $processor->linkedCallbacks[array_key_last($processor->linkedCallbacks)]['code'];
        self::assertStringContainsString('function ff_dispQueryPage(id,page)', $queryCode);
        self::assertStringContainsString('ff_queryCurrPage[id] = page;', $queryCode);
    }

    public function testViewReachesFinalizationWithQueryList(): void
    {
        $processor = $this->makeProcessorReadyForCaptchaScript([
            (object) [
                'id' => 34,
                'type' => 'Query List',
                'flag1' => 1,
                'flag2' => 0,
                'height' => 15,
                'data1' => '',
                'data3' => '',
                'page' => 1,
                'name' => 'results',
                'script1cond' => 0,
                'script1id' => 0,
                'script1code' => '',
                'script2cond' => 0,
                'script2id' => 0,
                'script2code' => '',
                'script3cond' => 0,
                'script3id' => 0,
                'script3code' => '',
            ],
        ]);
        $processor->formrow->name = 'query';
        $processor->formrow->piece2cond = 0;
        $processor->formrow->script1cond = 0;
        $processor->formrow->script1id = 0;
        $processor->formrow->script1code = '';
        $processor->formrow->script2cond = 0;
        $processor->formrow->script2id = 0;
        $processor->formrow->script2code = '';
        $processor->formrow->class2 = '';
        $processor->form_id = 7;
        $processor->target = 0;
        $processor->align = 0;
        $processor->top = 0;
        $processor->traceMode = 0;
        $processor->buryOnCallNumber = null;
        $processor->queryResultRows = [['result', 1]];
        $GLOBALS['ff_otherparams'] = [];

        $html = $this->captureCaptchaScript($processor);

        self::assertSame([['result', 1]], $processor->queryRows['ff_34']);
        self::assertStringContainsString(
            'function ff_dispQueryPage(id,page)',
            $processor->linkedCallbacks[array_key_last($processor->linkedCallbacks)]['code']
        );
        self::assertStringContainsString('</div><!-- form end -->', $html);
    }

    public function testViewReachesFinalizationForAnEmptyQuickModeForm(): void
    {
        $processor = $this->makeProcessorReadyForCaptchaScript([]);
        $processor->formrow->name = 'contact';
        $processor->formrow->piece2cond = 0;
        $processor->formrow->script1cond = 0;
        $processor->formrow->script1id = 0;
        $processor->formrow->script1code = '';
        $processor->formrow->script2cond = 0;
        $processor->formrow->script2id = 0;
        $processor->formrow->script2code = '';
        $processor->formrow->class2 = '';
        $processor->form_id = 7;
        $processor->target = 0;
        $processor->align = 0;
        $processor->top = 0;
        $processor->traceMode = 0;
        $processor->buryOnCallNumber = null;
        $GLOBALS['ff_otherparams'] = [];
        $processor->app->getInput()->set('cb_form_id', 88);
        $processor->app->getInput()->set('cb_record_id', 144);
        $processor->app->getInput()->set('cbIsNew', true);
        $processor->app->getInput()->set('return', 'https://example.test/thanks');
        $processor->app->getInput()->set('tmpl', 'component');

        $html = $this->captureCaptchaScript($processor);

        self::assertStringContainsString('</div><!-- form end -->', $html);
        self::assertStringContainsString('name="cb_form_id" value="88"', $html);
        self::assertStringContainsString('name="cb_record_id" value="144"', $html);
        self::assertStringContainsString('name="cbIsNew" value="1"', $html);
        self::assertStringContainsString('name="return" value="https://example.test/thanks"', $html);
        self::assertStringContainsString('name="tmpl" value="component"', $html);
        self::assertStringNotContainsString('name="ff_runmode"', $html);
        self::assertStringNotContainsString('name="ff_frame"', $html);
        self::assertStringNotContainsString('<piece>', $html);
    }

    public function testViewReachesBackendFinalizationWithRunmodeContext(): void
    {
        $processor = $this->makeProcessorReadyForCaptchaScript([]);
        $processor->formrow->name = 'contact';
        $processor->formrow->piece2cond = 0;
        $processor->formrow->script1cond = 0;
        $processor->formrow->script1id = 0;
        $processor->formrow->script1code = '';
        $processor->formrow->script2cond = 0;
        $processor->formrow->script2id = 0;
        $processor->formrow->script2code = '';
        $processor->formrow->class2 = '';
        $processor->form_id = 7;
        $processor->target = 0;
        $processor->align = 0;
        $processor->top = 0;
        $processor->runmode = _FF_RUNMODE_BACKEND;
        $processor->traceMode = 0;
        $processor->buryOnCallNumber = null;
        $GLOBALS['ff_otherparams'] = [];
        $processor->app->getInput()->set('return', 'administrator/index.php?option=com_breezingformsng');
        $processor->app->getInput()->set('tmpl', 'component');

        $html = $this->captureCaptchaScript($processor);

        self::assertStringContainsString('</div><!-- form end -->', $html);
        self::assertStringContainsString('name="ff_runmode" value="1"', $html);
        self::assertStringContainsString(
            'name="return" value="administrator/index.php?option=com_breezingformsng"',
            $html
        );
        self::assertStringContainsString('name="tmpl" value="component"', $html);
        self::assertStringNotContainsString('name="ff_frame"', $html);
        self::assertStringNotContainsString('<piece>', $html);
    }

    public function testViewReachesPreviewFinalizationOnlyInsideFrame(): void
    {
        $processor = $this->makeProcessorReadyForCaptchaScript([]);
        $processor->formrow->name = 'preview';
        $processor->formrow->piece2cond = 0;
        $processor->formrow->script1cond = 0;
        $processor->formrow->script1id = 0;
        $processor->formrow->script1code = '';
        $processor->formrow->script2cond = 0;
        $processor->formrow->script2id = 0;
        $processor->formrow->script2code = '';
        $processor->formrow->class2 = '';
        $processor->form_id = 7;
        $processor->target = 0;
        $processor->align = 0;
        $processor->top = 0;
        $processor->runmode = 2;
        $processor->inframe = 1;
        $processor->traceMode = 0;
        $processor->buryOnCallNumber = null;
        $GLOBALS['ff_otherparams'] = [];
        $processor->app->getInput()->set('return', 'index.php?preview=1');
        $processor->app->getInput()->set('tmpl', 'component');

        $html = $this->captureCaptchaScript($processor);

        self::assertStringContainsString('</div><!-- form end -->', $html);
        self::assertStringContainsString('name="ff_runmode" value="2"', $html);
        self::assertStringContainsString('name="ff_frame" value="1"', $html);
        self::assertStringContainsString('name="return" value="index.php?preview=1"', $html);
        self::assertStringContainsString('name="tmpl" value="component"', $html);
    }

    public function testViewOmitsPreviewHiddenFieldsOutsideAnIframe(): void
    {
        $processor = $this->makeProcessorReadyForCaptchaScript([]);
        $processor->formrow->name = 'preview';
        $processor->formrow->piece2cond = 0;
        $processor->formrow->script1cond = 0;
        $processor->formrow->script1id = 0;
        $processor->formrow->script1code = '';
        $processor->formrow->script2cond = 0;
        $processor->formrow->script2id = 0;
        $processor->formrow->script2code = '';
        $processor->formrow->class2 = '';
        $processor->form_id = 7;
        $processor->target = 0;
        $processor->align = 0;
        $processor->top = 0;
        $processor->runmode = 2;
        $processor->inframe = 0;
        $processor->traceMode = 0;
        $processor->buryOnCallNumber = null;
        $GLOBALS['ff_otherparams'] = [];

        $html = $this->captureCaptchaScript($processor);

        self::assertStringContainsString('</div><!-- form end -->', $html);
        self::assertStringNotContainsString('name="ff_runmode"', $html);
        self::assertStringNotContainsString('name="ff_frame"', $html);
    }

    public function testViewSelectsQuickModeRendererFromTemplateMetadata(): void
    {
        $processor = (new ReflectionClass(RenderingEngineProcessorDouble::class))->newInstanceWithoutConstructor();
        $processor->app = new CMSApplication();
        $processor->formrow = (object) [
            'template_code' => base64_encode(json_encode([
                'properties' => [
                    'themebootstrapThemeEngine' => 'bootstrap',
                    'themebootstrapMode' => false,
                    'fadeIn' => false,
                    'useErrorAlerts' => false,
                    'rollover' => false,
                    'rolloverColor' => '',
                    'theme' => '',
                ],
            ], JSON_THROW_ON_ERROR)),
        ];
        $processor->form = 7;

        $engine = (new ReflectionClass(RenderingEngine::class))->newInstanceWithoutConstructor();
        (new ReflectionClass($engine))->getProperty('processor')->setValue($engine, $processor);
        self::assertInstanceOf(
            BootstrapRenderer::class,
            (new QuickModeRendererFactory())->create(
                $processor,
                ['themebootstrapThemeEngine' => 'bootstrap', 'themebootstrapMode' => false]
            )
        );
        self::assertInstanceOf(
            OnePageRenderer::class,
            (new QuickModeRendererFactory())->create(
                $processor,
                ['themebootstrapThemeEngine' => 'bootstrap', 'themebootstrapMode' => true]
            )
        );
        self::assertInstanceOf(
            ClassicRenderer::class,
            (new QuickModeRendererFactory())->create($processor, ['themebootstrapThemeEngine' => 'classic'])
        );
    }
}
