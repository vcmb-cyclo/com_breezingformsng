<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering\QuickMode;

use HTML_facileFormsProcessor;
use Joomla\CMS\Application\CMSApplication;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\ClassicRenderer;

// HTML_facileFormsProcessor lives in the global namespace with no PSR-4
// mapping to it (it is normally require_once'd at runtime by FormRenderer,
// not autoloaded) - pull it in the same way here. Its file-scope code
// probes JPATH_ADMINISTRATOR for an optional ContentBuilderNG integration
// (com_contentbuilderng doesn't exist in this repo, so that probe just
// resolves to false and is skipped) - same convention as PdfDocumentTest.
if (!defined('JPATH_ADMINISTRATOR')) {
    define('JPATH_ADMINISTRATOR', __DIR__ . '/../../../../../administrator');
}

if (!class_exists(HTML_facileFormsProcessor::class)) {
    require_once __DIR__ . '/../../../../../components/com_breezingformsng/src/Support/processor_facade.php';
}

require_once __DIR__ . '/joomla-htmlhelper-stub.php';
require_once __DIR__ . '/joomla-text-stub.php';
require_once __DIR__ . '/joomla-uri-stub.php';
require_once __DIR__ . '/joomla-cmsapplication-stub.php';

/**
 * Characterization tests for ClassicRenderer::process().
 *
 * These pin down the *current* HTML output for one field type at a time, as
 * ground truth to protect against accidental regressions while the QuickMode
 * renderers are split up (see docs/maintenance/js-libraries-migration-plan.md
 * discussion on renderer file size). They are NOT a spec of "correct"
 * behaviour - if a snapshot legitimately needs to change, update it
 * deliberately and say why in the commit, the same way you would review a
 * diff of generated code.
 *
 * ClassicRenderer::process() is far too coupled to the Joomla runtime
 * (CMSApplication, ComponentHelper, the WebAssetManager, ...) to construct
 * normally in a unit test. Both HTML_facileFormsProcessor and ClassicRenderer
 * are built via ReflectionClass::newInstanceWithoutConstructor() here,
 * skipping their real constructors entirely, then only the handful of
 * private properties process() actually reads for a given field type are
 * injected directly. This is deliberately narrow: each test documents, via
 * its stub, exactly which collaborators that field type's rendering path
 * touches - if a future change makes a type touch something new, the test
 * fails loudly (missing property/type error) rather than silently mocking
 * around it.
 */
final class ClassicRendererCharacterizationTest extends TestCase
{
    private const SNAPSHOT_DIR = __DIR__ . '/__snapshots__';

    public function testTextfieldElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfTextfield', [
            'dbId' => 42,
            'bfName' => 'firstname',
            'label' => 'Prénom',
            'hint' => '',
            'required' => true,
            'password' => false,
            'maxLength' => '',
            'size' => '',
            'placeholder' => 'Votre prénom',
            'value' => '',
            'mailbackAsSender' => false,
        ]));

        $this->assertMatchesSnapshot('classic_bfTextfield.html', $html);
    }

    public function testTextfieldElementWithExistingValue(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfTextfield', [
            'dbId' => 43,
            'bfName' => 'lastname',
            'label' => 'Nom <script>',
            'hint' => '',
            'required' => false,
            'password' => false,
            'maxLength' => 40,
            'size' => '20em',
            'placeholder' => '',
            'value' => 'Valeur "pré-remplie" & spéciale',
            'mailbackAsSender' => false,
        ]));

        $this->assertMatchesSnapshot('classic_bfTextfield_prefilled.html', $html);
    }

    public function testLegacyTooltipStyleMarkerIsNotRendered(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfTextfield', [
            'dbId' => 44,
            'bfName' => 'email',
            'label' => 'Adresse e-mail',
            'hint' => 'background: #ffc; color: #000;<<<styleSaisissez une adresse valide.',
        ]));

        self::assertStringContainsString('Saisissez une adresse valide.', $html);
        self::assertStringNotContainsString('<<<style', $html);
        self::assertStringNotContainsString('background: #ffc; color: #000;', $html);
        self::assertStringContainsString('class="editlinktip hasTooltip"', $html);
    }

    public function testPageNavigationRendersPreviousNextAndCancelActions(): void
    {
        $renderer = $this->makeRenderer();
        $this->setPrivate($renderer, 'rootMdata', [
            'joomlaHint' => false,
            'useErrorAlerts' => true,
            'lastPageThankYou' => false,
            'pagingInclude' => true,
            'pagingPrevLabel' => 'Previous',
            'pagingNextLabel' => 'Next',
            'submitInclude' => false,
            'cancelInclude' => true,
            'cancelLabel' => 'Cancel',
        ]);
        $this->setPrivate($renderer, 'dataObject', ['children' => [[], [], []]]);

        $html = $this->render($renderer, [
            'attributes' => ['id' => 'page2'],
            'properties' => [
                'type' => 'page',
                'pageNumber' => 2,
                'pageIntro' => '',
            ],
        ]);

        self::assertStringContainsString('class="btn btn-primary bfPrevButton', $html);
        self::assertStringContainsString("ff_validate_prevpage(this, 'click');", $html);
        self::assertStringContainsString('class="btn btn-primary bfNextButton', $html);
        self::assertStringContainsString("ff_validate_nextpage(this, 'click');", $html);

        $lastPageHtml = $this->render($renderer, [
            'attributes' => ['id' => 'page3'],
            'properties' => [
                'type' => 'page',
                'pageNumber' => 3,
                'pageIntro' => '',
            ],
        ]);

        self::assertStringContainsString('class="btn btn-primary bfCancelButton', $lastPageHtml);
        self::assertStringContainsString("ff_resetForm(this, 'click');", $lastPageHtml);
    }

    /**
     * Issue #69: a start page coming only from the menu item (no ff_page in
     * the request) reaches the renderer through the processor.
     */
    public function testStartPageResolvedByTheProcessorIsTheVisibleOne(): void
    {
        $renderer = $this->makeRenderer();
        $this->setPrivate($renderer, 'rootMdata', [
            'joomlaHint' => false,
            'useErrorAlerts' => true,
            'lastPageThankYou' => false,
            'pagingInclude' => false,
            'submitInclude' => false,
            'cancelInclude' => false,
        ]);
        $this->setPrivate($renderer, 'dataObject', ['children' => [[], [], []]]);

        $processor = (new ReflectionClass($renderer))->getProperty('p')->getValue($renderer);
        $processor->page = 3;

        $page = static fn(int $number): array => [
            'attributes' => ['id' => 'page' . $number],
            'properties' => ['type' => 'page', 'pageNumber' => $number, 'pageIntro' => ''],
        ];

        self::assertStringContainsString('<div id="bfPage3" class="bfPage">', $this->render($renderer, $page(3)));
        self::assertStringContainsString(
            '<div id="bfPage1" class="bfPage" style="display:none;">',
            $this->render($renderer, $page(1))
        );
    }

    public function testTextareaElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfTextarea', [
            'dbId' => 44,
            'bfName' => 'message',
            'label' => 'Message',
            'hint' => '',
            'required' => true,
            'width' => '',
            'height' => '',
            'placeholder' => '',
            'value' => "Ligne 1\nLigne 2",
            'is_html' => false,
        ]));

        $this->assertMatchesSnapshot('classic_bfTextarea.html', $html);
    }

    public function testHiddenElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfHidden', [
            'dbId' => 45,
            'bfName' => 'source',
            'hideLabel' => true,
            'value' => 'newsletter-2026',
        ]));

        $this->assertMatchesSnapshot('classic_bfHidden.html', $html);
    }

    public function testCheckboxElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfCheckbox', [
            'dbId' => 46,
            'bfName' => 'accept',
            'label' => "J'accepte les conditions",
            'hint' => '',
            'required' => true,
            'checked' => false,
            'value' => '1',
            'mailbackAccept' => false,
        ]));

        $this->assertMatchesSnapshot('classic_bfCheckbox.html', $html);
    }

    public function testSelectElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfSelect', [
            'dbId' => 47,
            'bfName' => 'country',
            'label' => 'Pays',
            'hint' => '',
            'required' => false,
            'multiple' => false,
            'width' => '',
            'height' => '',
            'list' => "1;France;fr\n0;Belgique;be\n0;Suisse;ch",
        ]));

        $this->assertMatchesSnapshot('classic_bfSelect.html', $html);
    }

    public function testRadioGroupElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfRadioGroup', [
            'dbId' => 48,
            'bfName' => 'gender',
            'label' => 'Civilité',
            'hint' => '',
            'required' => true,
            'wrap' => true,
            'group' => "0;Madame;mme\n1;Monsieur;mr",
        ]));

        $this->assertMatchesSnapshot('classic_bfRadioGroup.html', $html);
    }

    public function testCheckboxGroupElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfCheckboxGroup', [
            'dbId' => 49,
            'bfName' => 'interests',
            'label' => 'Centres d\'intérêt',
            'hint' => '',
            'required' => false,
            'wrap' => false,
            'group' => "0;Route;road\n1;VTT;mtb\n0;Piste;track",
        ]));

        $this->assertMatchesSnapshot('classic_bfCheckboxGroup.html', $html);
    }

    /**
     * bfNumberInput's `size` attribute path (RuntimeAssetLoader::script())
     * touches $this->p->app - out of scope for this fixture, so `size` stays
     * empty here to keep this a pure string-building assertion, same as the
     * other covered types.
     */
    public function testNumberInputElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfNumberInput', [
            'dbId' => 50,
            'bfName' => 'age',
            'label' => 'Âge',
            'hint' => '',
            'required' => false,
            'range' => false,
            'value' => '',
            'step' => 1,
            'max' => 120,
            'min' => 0,
        ]));

        $this->assertMatchesSnapshot('classic_bfNumberInput.html', $html);
    }

    public function testSummarizeElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfSummarize', [
            'dbId' => 51,
            'hideLabel' => true,
            'connectWith' => ['price', 'qty'],
            'connectType' => 'multiply',
            'emptyMessage' => '0',
            'hideIfEmpty' => false,
            'fieldCalc' => '',
        ]));

        $this->assertMatchesSnapshot('classic_bfSummarize.html', $html);
    }

    public function testSubmitButtonElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfSubmitButton', [
            'dbId' => 52,
            'hideLabel' => true,
            'src' => '',
            'value' => 'Envoyer',
            'actionClick' => 0,
            'actionFunctionName' => '',
        ]));

        $this->assertMatchesSnapshot('classic_bfSubmitButton.html', $html);
    }

    public function testStripeElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfStripe', [
            'dbId' => 53,
            'hideLabel' => true,
            'image' => '',
            'actionClick' => 0,
            'actionFunctionName' => '',
        ]));

        $this->assertMatchesSnapshot('classic_bfStripe.html', $html);
    }

    public function testPayPalElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfPayPal', [
            'dbId' => 54,
            'hideLabel' => true,
            'image' => '',
            'actionClick' => 0,
            'actionFunctionName' => '',
        ]));

        $this->assertMatchesSnapshot('classic_bfPayPal.html', $html);
    }

    public function testSofortueberweisungElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfSofortueberweisung', [
            'dbId' => 55,
            'hideLabel' => true,
            'image' => '',
            'actionClick' => 0,
            'actionFunctionName' => '',
        ]));

        $this->assertMatchesSnapshot('classic_bfSofortueberweisung.html', $html);
    }

    public function testCaptchaElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfCaptcha', [
            'dbId' => 56,
            'hideLabel' => true,
            'width' => '',
        ]));

        $this->assertMatchesSnapshot('classic_bfCaptcha.html', $html);
    }

    public function testSignatureElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfSignature', [
            'dbId' => 57,
            'bfName' => 'signature',
            'hideLabel' => true,
        ]));

        $this->assertMatchesSnapshot('classic_bfSignature.html', $html);
    }

    public function testCalendarResponsiveElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfCalendarResponsive', [
            'dbId' => 58,
            'bfName' => 'eventdate',
            'label' => 'Date',
            'hint' => '',
            'required' => true,
            'value' => '',
            'format' => '%Y-%m-%d',
            'firstDay' => '1',
        ]));

        $this->assertMatchesSnapshot('classic_bfCalendarResponsive.html', $html);
    }

    public function testReCaptchaElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfReCaptcha', [
            'dbId' => 59,
            'hideLabel' => true,
            'pubkey' => '6Lc-test-pubkey',
            'invisibleCaptcha' => false,
            'theme' => '',
            'size' => '',
        ]));

        $this->assertMatchesSnapshot('classic_bfReCaptcha.html', $html);
    }

    public function testInvisibleReCaptchaElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfReCaptcha', [
            'dbId' => 59,
            'hideLabel' => true,
            'pubkey' => '6Lc-test-pubkey',
            'invisibleCaptcha' => true,
            'theme' => 'invisible_inline',
        ]));

        $this->assertMatchesSnapshot('classic_bfReCaptcha_invisible.html', $html);
    }

    public function testCalendarElement(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfCalendar', [
            'dbId' => 60,
            'bfName' => 'birthdate',
            'label' => 'Date de naissance',
            'hint' => '',
            'required' => false,
            'value' => '',
            'format' => '%Y-%m-%d',
            'timeFormat' => false,
            'singleHeader' => false,
            'todayButton' => false,
            'weekNumbers' => false,
            'minYear' => '',
            'maxYear' => '',
            'firstDay' => '',
        ]));

        $this->assertMatchesSnapshot('classic_bfCalendar.html', $html);
    }

    public function testFileElementPlain(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfFile', [
            'dbId' => 61,
            'bfName' => 'attachment',
            'hideLabel' => true,
            'flashUploader' => false,
            'html5' => false,
            'attachToAdminMail' => false,
            'attachToUserMail' => false,
        ]));

        $this->assertMatchesSnapshot('classic_bfFile_plain.html', $html);
    }

    public function testFileElementFlashUploader(): void
    {
        $renderer = $this->makeRenderer();

        $html = $this->render($renderer, $this->elementNode('bfFile', [
            'dbId' => 62,
            'bfName' => 'photo',
            'hideLabel' => true,
            'flashUploader' => true,
            'html5' => true,
            'flashUploaderMulti' => false,
            'flashUploaderBytes' => 2097152,
            'flashUploaderWidth' => '',
            'flashUploaderHeight' => '',
            'allowedFileExtensions' => 'jpg,png',
            'attachToAdminMail' => true,
            'attachToUserMail' => false,
        ]));

        $this->assertMatchesSnapshot('classic_bfFile_flashUploader.html', $html);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function elementNode(string $bfType, array $overrides): array
    {
        $defaults = [
            'type' => 'element',
            'bfType' => $bfType,
            'dbId' => 1,
            'bfName' => 'field',
            'label' => 'Label',
            'hint' => '',
            'required' => false,
            'hideLabel' => false,
            'labelPosition' => 'left',
            'readonly' => false,
            'tabIndex' => -1,
            'off' => false,
            'theme' => '',
            'password' => false,
            'maxLength' => '',
            'size' => '',
            'placeholder' => '',
            'value' => '',
            'mailbackAsSender' => false,
        ];

        $properties = array_merge($defaults, $overrides);

        return [
            'attributes' => ['id' => 'element' . $properties['dbId']],
            'properties' => $properties,
        ];
    }

    private function makeRenderer(): ClassicRenderer
    {
        $processor = (new ReflectionClass(HTML_facileFormsProcessor::class))->newInstanceWithoutConstructor();
        $processor->rowcount = 0;
        $processor->rows = [];
        $processor->app = new CMSApplication();
        $processor->form = 27;

        $renderer = (new ReflectionClass(ClassicRenderer::class))->newInstanceWithoutConstructor();

        $this->setPrivate($renderer, 'p', $processor);
        $this->setPrivate($renderer, 'rootMdata', [
            'joomlaHint' => false,
            'useErrorAlerts' => true,
        ]);
        $this->setPrivate($renderer, 'fadingClass', '');
        $this->setPrivate($renderer, 'language_tag', 'zz-ZZ');
        $this->setPrivate($renderer, 'flashUploadTicket', 'test-ticket-fixed');
        $this->setPrivate($renderer, 'uploadImagePath', '/media/breezingforms/themes/upload.png');
        $this->setPrivate($renderer, 'cancelImagePath', '/media/breezingforms/themes/cancel.png');

        return $renderer;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function render(ClassicRenderer $renderer, array $node): string
    {
        ob_start();
        try {
            $renderer->process($node);
        } finally {
            $html = ob_get_clean();
        }

        return $html;
    }

    private function setPrivate(object $object, string $property, mixed $value): void
    {
        $ref = new ReflectionClass($object);
        $ref->getProperty($property)->setValue($object, $value);
    }

    private function assertMatchesSnapshot(string $file, string $actual): void
    {
        $path = self::SNAPSHOT_DIR . '/' . $file;
        $updating = getenv('BF_UPDATE_SNAPSHOTS') === '1';

        if (!is_file($path)) {
            if (!$updating) {
                self::markTestIncomplete(
                    "No snapshot yet at tests/Site/Service/Rendering/QuickMode/__snapshots__/{$file} - "
                    . 'run with BF_UPDATE_SNAPSHOTS=1 to create it, review it, then commit it.'
                );
            }

            file_put_contents($path, $actual);
        } elseif ($updating) {
            file_put_contents($path, $actual);
        }

        self::assertSame(file_get_contents($path), $actual);
    }
}
