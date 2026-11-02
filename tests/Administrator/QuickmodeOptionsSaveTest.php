<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\TestCase;

final class QuickmodeOptionsSaveTest extends TestCase
{
    public function testTheOptionsSaveKeepsItsMessagesForThePage(): void
    {
        $controller = $this->read('administrator/components/com_breezingformsng/src/Controller/FormsController.php');
        $html = $this->read('administrator/components/com_breezingformsng/src/Helper/QuickmodeHtml.php');
        $app = $this->read('media/com_breezingformsng/js/admin/quickmode-app.js');

        self::assertStringContainsString('<input type="hidden" name="task" value="forms.saveOptions">', $html);
        self::assertStringNotContainsString('bfOptionsSaveFrame', $html . $app);
        self::assertStringContainsString("\$this->checkToken('post', false)", $controller);
        self::assertStringContainsString("set('application.queue', \$app->getMessageQueue())", $controller);
    }

    public function testTheOptionsPostDoesNotPutBackWhatTheTreeSaved(): void
    {
        $app = $this->read('media/com_breezingformsng/js/admin/quickmode-app.js');

        self::assertStringContainsString("JQuery('#jf_pages').val(app.dataObject.children.length);", $app);
        self::assertStringContainsString("JQuery('#jf_emailadr').val(recipient);", $app);
        self::assertStringContainsString("\$wrap.find('[name=\"script2code\"]').val(mdata.submittedScriptCode);", $app);
    }

    public function testASaveCanBeTriedAgainAfterAFailure(): void
    {
        $app = $this->read('media/com_breezingformsng/js/admin/quickmode-app.js');

        self::assertStringContainsString('function optionsSaveFailed(message)', $app);
        self::assertStringContainsString("rndAdd = Math.random();\n                            chunki = 0;", $app);
    }

    private function read(string $path): string
    {
        $contents = file_get_contents(__DIR__ . '/../../' . $path);
        self::assertIsString($contents);

        return $contents;
    }
}
