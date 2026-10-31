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

use PHPUnit\Framework\TestCase;
use Vcmb\Component\BreezingformsNG\Site\Service\Rendering\HiddenFormFieldsBuilder;

final class HiddenFormFieldsBuilderTest extends TestCase
{
    public function testPaymentMethodReturnsThePaymentMethodHiddenField(): void
    {
        self::assertSame(
            "    <input type=\"hidden\" name=\"ff_payment_method\" id=\"bfPaymentMethod\" value=\"\"/>\n",
            (new HiddenFormFieldsBuilder())->paymentMethod('    ')
        );
    }

    public function testTokenFormatsTokenWithIndentationAndHistoricalNewline(): void
    {
        self::assertSame(
            "\t<input type=\"hidden\" name=\"token\" value=\"abc\"/>\r\n",
            (new HiddenFormFieldsBuilder())->token(
                '<input type="hidden" name="token" value="abc"/>',
                "\t"
            )
        );
    }

    public function testTokenPreservesEmptyTokenInput(): void
    {
        self::assertSame("\r\n", (new HiddenFormFieldsBuilder())->token('', ''));
    }

    public function testAdditionalBuildsParametersInOrderWithUrlEncoding(): void
    {
        self::assertSame(
            "\t<input type=\"hidden\" name=\"alpha&amp;key\" value=\"a%2Bb\"/>\n"
            . "\t<input type=\"hidden\" name=\"second\" value=\"value+with+spaces\"/>\n",
            (new HiddenFormFieldsBuilder())->additional(
                ['alpha&key' => 'a+b', 'second' => 'value with spaces'],
                "\t",
                "\n"
            )
        );
    }

    public function testAdditionalBuildsNoOutputForEmptyParameters(): void
    {
        self::assertSame('', (new HiddenFormFieldsBuilder())->additional([], ''));
    }

    public function testRoutingBuildsReturnAndComponentTemplateFields(): void
    {
        self::assertSame(
            '<input type="hidden" name="return" value="https&quot;next"/>' . "\r\n"
            . '<input type="hidden" name="tmpl" value="component"/>' . "\r\n",
            (new HiddenFormFieldsBuilder())->routing('https"next', 'component')
        );
    }

    public function testRoutingOmitsFieldsWhenRoutingParametersAreAbsent(): void
    {
        self::assertSame('', (new HiddenFormFieldsBuilder())->routing('', ''));
        self::assertSame(
            '<input type="hidden" name="return" value="next"/>' . "\r\n",
            (new HiddenFormFieldsBuilder())->routing('next', 'html')
        );
    }

    public function testSubmissionBuildsFrontendSubmissionFields(): void
    {
        self::assertSame(
            "  <input type=\"hidden\" name=\"ff_form\" value=\"12\"/>\n"
            . "  <input type=\"hidden\" name=\"ff_task\" value=\"submit\"/>\n",
            (new HiddenFormFieldsBuilder())->submission(12, '  ', "\n")
        );
    }

    public function testContextBuildsFieldsInProvidedOrderAndEscapesValues(): void
    {
        self::assertSame(
            "\t<input type=\"hidden\" name=\"ff_applic\" value=\"module&amp;name\"/>\r\n"
            . "\t<input type=\"hidden\" name=\"ff_record_id\" value=\"12\"/>\r\n",
            (new HiddenFormFieldsBuilder())->context(
                ['ff_applic' => 'module&name', 'ff_record_id' => 12],
                "\t"
            )
        );
    }

    public function testContextBuildsNoOutputForEmptyContext(): void
    {
        self::assertSame('', (new HiddenFormFieldsBuilder())->context([], ''));
    }

    public function testOptionalContextBuildsFieldsInHistoricalOrder(): void
    {
        self::assertSame(
            "\t<input type=\"hidden\" name=\"ff_target\" value=\"2\"/>\n"
            . "\t<input type=\"hidden\" name=\"ff_frame\" value=\"1\"/>\n"
            . "\t<input type=\"hidden\" name=\"ff_border\" value=\"1\"/>\n"
            . "\t<input type=\"hidden\" name=\"ff_page\" value=\"3\"/>\n"
            . "\t<input type=\"hidden\" name=\"ff_align\" value=\"2\"/>\n"
            . "\t<input type=\"hidden\" name=\"ff_top\" value=\"4\"/>\n",
            (new HiddenFormFieldsBuilder())->optionalContext(2, true, true, 3, 2, 4, "\t", true, true, true, true, "\n")
        );
    }

    public function testOptionalContextOmitsPreviewDefaults(): void
    {
        $builder = new HiddenFormFieldsBuilder();

        self::assertSame(
            "<input type=\"hidden\" name=\"ff_page\" value=\"2\"/>\r\n",
            $builder->optionalContext(1, false, false, 2, 2, 4, '', false, false, false, false)
        );
        self::assertSame('', $builder->optionalContext(1, false, false, 1, 1, 0, '', false, false, false, false));
    }

    public function testContentBuilderTechnicalBuildsRecordMarkers(): void
    {
        $builder = new HiddenFormFieldsBuilder();

        self::assertSame(
            "    <input type=\"hidden\" name=\"cb_form_id\" value=\"12\"/>\n"
            . "    <input type=\"hidden\" name=\"cb_record_id\" value=\"34\"/>\n"
            . "    <input type=\"hidden\" name=\"cbIsNew\" value=\"1\"/>\n",
            $builder->contentBuilderTechnical('    ', 12, 34, true)
        );

        self::assertSame("<input type=\"hidden\" name=\"cb_form_id\" value=\"12\"/>\n", $builder->contentBuilderTechnical('', 12, 0, false));
        self::assertSame('', $builder->contentBuilderTechnical('    ', 0, 34, true));
    }
}
