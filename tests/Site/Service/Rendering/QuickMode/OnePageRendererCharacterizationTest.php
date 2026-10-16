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

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Rendering\QuickMode;

use HTML_facileFormsProcessor;
use Joomla\CMS\Application\CMSApplication;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\QuickMode\OnePageRenderer;

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
 * Characterization coverage for the one-page QuickMode renderer.
 */
final class OnePageRendererCharacterizationTest extends TestCase
{
    public function testPageNavigationRendersSharedOnePageNextAction(): void
    {
        $renderer = $this->makeRenderer();
        $this->setPrivate($renderer, 'rootMdata', [
            'themebootstrapThemeEngine' => 'bootstrap',
            'themebootstrap' => '',
            'useErrorAlerts' => true,
            'lastPageThankYou' => false,
            'pagingInclude' => true,
            'pagingNextLabel' => 'Next',
            'submitInclude' => false,
            'cancelInclude' => false,
        ]);
        $this->setPrivate($renderer, 'dataObject', ['children' => [[], [], []]]);

        ob_start();
        try {
            $node = [
                'attributes' => ['id' => 'page2'],
                'properties' => ['type' => 'page', 'pageNumber' => 2, 'pageIntro' => ''],
            ];
            $renderer->process($node);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertStringContainsString('bfNextButton', (string) $html);
        self::assertStringContainsString('ff_currentpage = 2;bf_validate_nextpage(3);', (string) $html);
    }

    private const SNAPSHOT_DIR = __DIR__ . '/__snapshots__';

    public function testTextfieldElement(): void
    {
        $renderer = $this->makeRenderer();
        $node = [
            'attributes' => ['id' => 'element42'],
            'properties' => [
                'type' => 'element',
                'bfType' => 'bfTextfield',
                'dbId' => 42,
                'bfName' => 'firstname',
                'label' => 'Prénom',
                'hint' => '',
                'required' => true,
                'hideLabel' => false,
                'password' => false,
                'maxLength' => '',
                'size' => '',
                'placeholder' => 'Votre prénom',
                'value' => 'Jean',
                'tabIndex' => -1,
                'readonly' => false,
                'off' => false,
                'mailbackAsSender' => false,
                'icon' => '',
            ],
        ];

        ob_start();
        try {
            $renderer->process($node);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertIsString($html);
        self::assertStringContainsString('name="ff_nm_firstname[]"', $html);
        self::assertStringContainsString('value="Jean"', $html);
        self::assertStringContainsString('id="ff_elem42"', $html);
        self::assertStringContainsString('Votre pr&eacute;nom', $html);
        $this->assertMatchesSnapshot('onepage_bfTextfield.html', $html);
    }

    public function testTextareaElement(): void
    {
        $html = $this->renderElement('bfTextarea', [
            'dbId' => 44,
            'bfName' => 'message',
            'label' => 'Message',
            'required' => true,
            'width' => '',
            'height' => '',
            'value' => "Ligne 1\nLigne 2",
            'is_html' => false,
        ]);

        self::assertStringContainsString('name="ff_nm_message[]"', $html);
        self::assertStringContainsString("Ligne 1\nLigne 2", $html);
        self::assertStringContainsString('<textarea', $html);
    }

    public function testRadioGroupElement(): void
    {
        $html = $this->renderElement('bfRadioGroup', [
            'dbId' => 48,
            'bfName' => 'gender',
            'label' => 'Civilité',
            'required' => true,
            'wrap' => true,
            'group' => "0;Madame;mme\n1;Monsieur;mr",
        ]);

        self::assertStringContainsString('type="radio"', $html);
        self::assertStringContainsString('value="mme"', $html);
        self::assertStringContainsString('checked="checked"  class="ff_elem form-check-input" ', $html);
        self::assertStringContainsString('Madame</label>', $html);
    }

    public function testCheckboxGroupElement(): void
    {
        $html = $this->renderElement('bfCheckboxGroup', [
            'dbId' => 49,
            'bfName' => 'interests',
            'label' => "Centres d'intérêt",
            'wrap' => false,
            'group' => "0;Route;road\n1;VTT;mtb\n0;Piste;track",
        ]);

        self::assertStringContainsString('type="checkbox"', $html);
        self::assertStringContainsString('value="mtb"', $html);
        self::assertStringContainsString('Route</label>', $html);
    }

    public function testCheckboxElement(): void
    {
        $html = $this->renderElement('bfCheckbox', [
            'dbId' => 46,
            'bfName' => 'accept',
            'label' => "J'accepte les conditions",
            'required' => true,
            'checked' => false,
            'value' => '1',
            'mailbackAccept' => false,
        ]);

        self::assertStringContainsString('type="checkbox"', $html);
        self::assertStringContainsString('name="ff_nm_accept[]"', $html);
        self::assertStringNotContainsString('checked="checked"', $html);
    }

    public function testSelectElement(): void
    {
        $html = $this->renderElement('bfSelect', [
            'dbId' => 47,
            'bfName' => 'country',
            'label' => 'Pays',
            'multiple' => false,
            'width' => '',
            'height' => '',
            'list' => "1;France;fr\n0;Belgique;be\n0;Suisse;ch",
        ]);

        self::assertStringContainsString('<select', $html);
        self::assertStringContainsString('<option selected="selected" value="fr">France</option>', $html);
        self::assertStringContainsString('<option value="be">Belgique</option>', $html);
    }

    public function testSubmitButtonElement(): void
    {
        $html = $this->renderElement('bfSubmitButton', [
            'dbId' => 52,
            'hideLabel' => true,
            'src' => '',
            'value' => 'Envoyer',
            'actionClick' => 0,
            'actionFunctionName' => '',
        ]);

        self::assertStringContainsString('type="submit"', $html);
        self::assertStringContainsString('name="ff_nm_field[]"', $html);
        self::assertStringContainsString('>Envoyer<', $html);
    }

    /**
     * OnePageRenderer's bfFile case has its flashUploader/html5 condition
     * fully commented out in production - the flash/HTML5 upload widget
     * always renders regardless of those mdata flags, and the plain
     * <input type="file"> fallback branch is unreachable dead code. This
     * test documents that actual (if surprising) behavior, not a guess at
     * what it "should" do.
     */
    public function testFileElementAlwaysRendersFlashUploader(): void
    {
        $html = $this->renderElement('bfFile', [
            'dbId' => 62,
            'bfName' => 'photo',
            'hideLabel' => true,
            'flashUploader' => false,
            'html5' => false,
            'flashUploaderMulti' => false,
            'flashUploaderBytes' => 2097152,
            'allowedFileExtensions' => 'jpg,png',
            'attachToAdminMail' => true,
            'attachToUserMail' => false,
        ]);

        self::assertStringContainsString('flashUploadphoto', $html);
        self::assertStringContainsString('plupload.Uploader', $html);
        self::assertStringContainsString('name="attachToAdminMail[photo]"', $html);
        self::assertStringNotContainsString('type="file"', $html);
    }

    public function testCaptchaElement(): void
    {
        $html = $this->renderElement('bfCaptcha', [
            'dbId' => 56,
            'hideLabel' => true,
            'width' => '',
        ]);

        self::assertStringContainsString('id="ff_capimgValue"', $html);
        self::assertStringContainsString('bfCaptcha=1', $html);
        self::assertStringContainsString('name="bfCaptchaEntry"', $html);
    }

    public function testReCaptchaElement(): void
    {
        $html = $this->renderElement('bfReCaptcha', [
            'dbId' => 59,
            'hideLabel' => true,
            'pubkey' => '6Lc-test-pubkey',
            'invisibleCaptcha' => false,
            'theme' => '',
            'size' => '',
        ]);

        self::assertStringContainsString('newrecaptcha', $html);
        self::assertStringContainsString('bfInitVisibleReCaptcha', $html);
        self::assertStringContainsString('"sitekey":"6Lc-test-pubkey"', $html);
    }

    public function testInvisibleReCaptchaElement(): void
    {
        $html = $this->renderElement('bfReCaptcha', [
            'dbId' => 59,
            'hideLabel' => true,
            'pubkey' => '6Lc-test-pubkey',
            'invisibleCaptcha' => true,
            'theme' => 'invisible_inline',
        ], '_invisible');

        self::assertStringContainsString('bfInvisibleReCaptchaContainer', $html);
        self::assertStringContainsString(
            'bfInitInvisibleReCaptcha({"sitekey":"6Lc-test-pubkey","badge":"inline","hasFlashUpload":false,"resetFlagOnCallback":true,"submitFunction":"bf_validate_submit"});',
            $html
        );
    }

    public function testCalendarElement(): void
    {
        $html = $this->renderElement('bfCalendar', [
            'dbId' => 60,
            'bfName' => 'birthdate',
            'label' => 'Date de naissance',
            'value' => '',
            'format' => '%Y-%m-%d',
            'timeFormat' => false,
            'singleHeader' => false,
            'todayButton' => false,
            'weekNumbers' => false,
            'minYear' => '',
            'maxYear' => '',
            'firstDay' => '',
        ]);

        self::assertStringContainsString('HTMLHelper::_(["calendar"', $html);
        self::assertStringContainsString('ff_nm_birthdate[]', $html);
    }

    public function testCalendarResponsiveElement(): void
    {
        $html = $this->renderElement('bfCalendarResponsive', [
            'dbId' => 58,
            'bfName' => 'eventdate',
            'label' => 'Date',
            'required' => true,
            'value' => '',
            'format' => '%Y-%m-%d',
            'firstDay' => '1',
            'size' => '',
        ]);

        self::assertStringContainsString('bfInitCalendarResponsive', $html);
        self::assertStringContainsString('name="ff_nm_eventdate[]"', $html);
    }

    public function testHiddenElement(): void
    {
        $html = $this->renderElement('bfHidden', [
            'dbId' => 45,
            'bfName' => 'source',
            'hideLabel' => true,
            'value' => 'newsletter-2026',
        ]);

        self::assertStringContainsString('type="hidden"', $html);
        self::assertStringContainsString('value="newsletter-2026"', $html);
    }

    public function testNumberInputElement(): void
    {
        $html = $this->renderElement('bfNumberInput', [
            'dbId' => 50,
            'bfName' => 'age',
            'label' => 'Âge',
            'range' => false,
            'value' => '',
            'step' => 1,
            'max' => 120,
            'min' => 0,
        ]);

        self::assertStringContainsString('type="number"', $html);
        self::assertStringContainsString('name="ff_nm_age[]"', $html);
    }

    /**
     * Regression coverage for a real fix, not the "Azure theme" field types
     * generally: bfTextfield, bfTextarea and bfNumberInput each build a
     * field-label icon only when the "Azure" bootstrap theme is active, and
     * OnePageRenderer used the obsolete FontAwesome 4 'fa' prefix for it
     * while BootstrapRenderer already used the correct FontAwesome 5+ 'fas'
     * prefix - a real inconsistency (found comparing the two renderers'
     * source directly), not a duplicate worth mutualizing. 'fa' alone
     * renders no icon at all under FA5+, so this was a real visual bug on
     * OnePage specifically. None of the other characterization tests here
     * exercise this branch (they don't set themebootstrap to 'Azure'),
     * so it had no coverage before this test.
     */
    #[DataProvider('azureThemeIconFieldProvider')]
    public function testAzureThemeIconUsesFontAwesome5Prefix(string $bfType, array $overrides): void
    {
        $renderer = $this->makeRenderer();
        $this->setPrivate($renderer, 'rootMdata', [
            'themebootstrapThemeEngine' => 'bootstrap',
            'themebootstrap' => 'Azure',
            'useErrorAlerts' => true,
        ]);

        $defaults = [
            'type' => 'element',
            'bfType' => $bfType,
            'dbId' => 1,
            'bfName' => 'field',
            'label' => 'Label',
            'hint' => '',
            'required' => false,
            'hideLabel' => false,
            'password' => false,
            'maxLength' => '',
            'size' => '',
            'placeholder' => '',
            'value' => '',
            'tabIndex' => -1,
            'readonly' => false,
            'off' => false,
            'mailbackAsSender' => false,
            'icon' => '',
        ];

        $node = [
            'attributes' => ['id' => 'element1'],
            'properties' => array_merge($defaults, $overrides),
        ];

        ob_start();
        try {
            $renderer->process($node);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertStringContainsString('fas fa-pencil', (string) $html);
        self::assertStringNotContainsString('"fa fa-pencil"', (string) $html);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function azureThemeIconFieldProvider(): iterable
    {
        yield 'bfTextfield' => ['bfTextfield', ['bfName' => 'name']];
        yield 'bfTextarea' => ['bfTextarea', ['bfName' => 'message', 'width' => '', 'height' => '']];
        yield 'bfNumberInput' => [
            'bfNumberInput',
            ['bfName' => 'age', 'range' => false, 'step' => 1, 'max' => 120, 'min' => 0],
        ];
    }

    public function testSummarizeElement(): void
    {
        $html = $this->renderElement('bfSummarize', [
            'dbId' => 51,
            'hideLabel' => true,
            'connectWith' => ['price', 'qty'],
            'connectType' => 'multiply',
            'emptyMessage' => '0',
            'hideIfEmpty' => false,
            'fieldCalc' => '',
        ]);

        self::assertStringContainsString('bfRegisterSummarize', $html);
        self::assertStringContainsString('"multiply"', $html);
    }

    public function testSignatureElement(): void
    {
        $html = $this->renderElement('bfSignature', [
            'dbId' => 57,
            'bfName' => 'signature',
            'hideLabel' => true,
        ]);

        self::assertStringContainsString('bfSignature57', $html);
        self::assertStringContainsString('name="ff_nm_signature[]"', $html);
    }

    public function testStripeElement(): void
    {
        $html = $this->renderElement('bfStripe', [
            'dbId' => 53,
            'hideLabel' => true,
            'image' => '',
            'actionClick' => 0,
            'actionFunctionName' => '',
        ]);

        self::assertStringContainsString("'bfPaymentMethod'", $html);
        self::assertStringContainsString("'Stripe'", $html);
    }

    public function testPayPalElement(): void
    {
        $html = $this->renderElement('bfPayPal', [
            'dbId' => 54,
            'hideLabel' => true,
            'image' => '',
            'actionClick' => 0,
            'actionFunctionName' => '',
        ]);

        self::assertStringContainsString("'bfPaymentMethod'", $html);
        self::assertStringContainsString("'PayPal'", $html);
    }

    public function testSofortueberweisungElement(): void
    {
        $html = $this->renderElement('bfSofortueberweisung', [
            'dbId' => 55,
            'hideLabel' => true,
            'image' => '',
            'actionClick' => 0,
            'actionFunctionName' => '',
        ]);

        self::assertStringContainsString("'bfPaymentMethod'", $html);
        self::assertStringContainsString("'Sofortueberweisung'", $html);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function renderElement(string $bfType, array $overrides, string $snapshotSuffix = ''): string
    {
        $renderer = $this->makeRenderer();

        $defaults = [
            'type' => 'element',
            'bfType' => $bfType,
            'dbId' => 1,
            'bfName' => 'field',
            'label' => 'Label',
            'hint' => '',
            'required' => false,
            'hideLabel' => false,
            'password' => false,
            'maxLength' => '',
            'size' => '',
            'placeholder' => '',
            'value' => '',
            'tabIndex' => -1,
            'readonly' => false,
            'off' => false,
            'mailbackAsSender' => false,
            'icon' => '',
        ];

        $node = [
            'attributes' => ['id' => 'element' . ($overrides['dbId'] ?? 1)],
            'properties' => array_merge($defaults, $overrides),
        ];

        ob_start();
        try {
            $renderer->process($node);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertIsString($html);
        $this->assertMatchesSnapshot('onepage_' . $bfType . $snapshotSuffix . '.html', $html);

        return $html;
    }

    private function makeRenderer(): OnePageRenderer
    {
        $processor = (new ReflectionClass(HTML_facileFormsProcessor::class))->newInstanceWithoutConstructor();
        $processor->rowcount = 0;
        $processor->rows = [];
        $processor->app = new CMSApplication();
        $processor->form = 27;

        $renderer = (new ReflectionClass(OnePageRenderer::class))->newInstanceWithoutConstructor();
        $this->setPrivate($renderer, 'p', $processor);
        $this->setPrivate($renderer, 'rootMdata', [
            'themebootstrapThemeEngine' => 'bootstrap',
            'themebootstrap' => '',
            'useErrorAlerts' => true,
        ]);
        $this->setPrivate($renderer, 'fadingClass', '');
        $this->setPrivate($renderer, 'language_tag', 'zz-ZZ');
        $this->setPrivate($renderer, 'bsClasses', [5 => [
            'controls' => '',
            'alert' => 'alert',
            'alert-error' => 'alert-error',
            'form-inline' => 'bf-form-inline',
            'form-group' => 'bf-form-group mb-3',
            'form-actions' => 'form-actions',
            'form-actions-buttons' => 'form-actions-buttons',
            'control-group' => 'mb-3',
            'control-label' => 'form-label',
            'form-control' => 'form-control',
            'icon-asterisk' => 'fas fa-asterisk',
            'icon-question-sign' => 'fas fa-question-circle',
            'nonform-control' => 'nonform-control',
            'radio-form-group' => 'radio-form-group',
            'inline' => 'form-check-inline',
            'radio' => 'form-check-label',
            'checkbox' => 'form-check-label',
            'form-select' => 'form-select',
            'other-form-group' => 'other-form-group',
            'btn' => 'btn',
            'btn-primary' => 'btn-primary',
            'icon-upload' => 'fas fa-upload',
            'row' => 'row',
            'img-thumbnail' => 'img-thumbnail',
            'input-append' => 'input-group',
            'custom-form-control' => 'custom-form-control',
            'icon-refresh' => 'fas fa-sync',
            'well' => 'card',
            'well-small' => 'card-body',
            'icon-calendar' => 'fas fa-calendar',
            'float-start' => 'float-start',
            'float-end' => 'float-end',
        ]]);

        return $renderer;
    }

    private function setPrivate(object $object, string $property, mixed $value): void
    {
        (new ReflectionClass($object))->getProperty($property)->setValue($object, $value);
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
