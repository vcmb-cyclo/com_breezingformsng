<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\VendorHelper;
use Vcmb\Component\BreezingformsNG\Site\Service\Security\CaptchaCodeStore;
use Vcmb\Component\BreezingformsNG\Site\Service\Security\CaptchaImage;

/**
 * Security code callbacks: the image (bfCaptcha=1) and its check (checkCaptcha=1).
 */
final class CaptchaCallback
{
    public function __construct(private readonly CMSApplication $application)
    {
    }

    public function check(): void
    {
        $this->clearOutputBuffer();

        $capValue = $this->application->getInput()->getString('value', '');

        // The browser checks the code before it sends the form: the submission
        // checks it again, so this check leaves it in place.
        echo $this->codeStore()->matches(str_replace('?', '', $capValue), false)
            ? 'capResult=>true'
            : 'capResult=>false';

        $this->application->close();
    }

    public function image(): void
    {
        $this->clearOutputBuffer();
        VendorHelper::load();

        $captcha = (new CaptchaImage())->create();
        $this->codeStore()->store($captcha['phrase']);

        // A new image every time: the browser must not show an earlier one for a code that has changed.
        $this->application->setHeader('Content-Type', CaptchaImage::MIME_TYPE, true);
        $this->application->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate', true);
        $this->application->setHeader('Pragma', 'no-cache', true);
        $this->application->setHeader('status', '200', true);
        $this->application->sendHeaders();
        echo $captcha['image'];

        $this->application->close();
    }

    private function codeStore(): CaptchaCodeStore
    {
        return new CaptchaCodeStore($this->application->getSession());
    }

    private function clearOutputBuffer(): void
    {
        // The response is an image or a capResult value: any error text written into it would break it.
        ini_set('display_errors', '0');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
