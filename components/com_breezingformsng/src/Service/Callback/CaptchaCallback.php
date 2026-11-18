<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Site\Service\Callback;

\defined('_JEXEC') or die;

use Securimage;
use Joomla\CMS\Application\CMSApplication;
use Vcmb\Component\BreezingformsNG\Administrator\Helper\VendorHelper;
use Vcmb\Component\BreezingformsNG\Site\Service\Security\CaptchaCodeStore;
use Vcmb\Component\BreezingformsNG\Site\Service\Security\SecurimageSessionAdapter;

/**
 * Securimage captcha validation callback (checkCaptcha=1).
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

        $securimage = $this->createSecurimage();
        $securimage->image_width = 230;
        $securimage->image_height = 80;
        $securimage->perturbation = 0.9;
        $securimage->image_bg_color = new \Securimage_Color('#6495ED');
        $securimage->text_color = new \Securimage_Color('#B0E0E6');
        $securimage->line_color = new \Securimage_Color('#B0E0E6');
        $securimage->noise_color = new \Securimage_Color('#B0E0E6');
        $securimage->use_transparent_text = false;
        $securimage->text_transparency_percentage = 60;
        $securimage->num_lines = 15;
        $securimage->image_signature = '';
        $securimage->use_wordlist = true;

        $this->application->setHeader('status', '200', true);
        // Securimage returns instead of exiting, so that Joomla saves the session with the new code.
        $securimage->show(JPATH_SITE . '/media/com_breezingformsng/images/site/captcha/backgrounds/bg6.jpg');

        $this->application->close();
    }

    private function createSecurimage(): Securimage
    {
        // The response is a PNG or a capResult value: any error text in it breaks it.
        // Securimage raises a PHP 8 deprecation for every pixel it warps, which
        // would also flood the error log, so deprecations are left out here.
        ini_set('display_errors', '0');
        error_reporting(error_reporting() & ~E_DEPRECATED);
        VendorHelper::load();

        return new Securimage([
            'captchaId' => CaptchaCodeStore::CAPTCHA_ID,
            'storage_adapters' => [new SecurimageSessionAdapter($this->codeStore())],
            'no_exit' => true,
        ]);
    }

    private function codeStore(): CaptchaCodeStore
    {
        return new CaptchaCodeStore($this->application->getSession());
    }

    private function clearOutputBuffer(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
