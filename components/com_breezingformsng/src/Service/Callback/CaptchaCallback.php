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

        $securimage = $this->createSecurimage();
        $capValue = $this->application->getInput()->getString('value', '');

        echo $securimage->check(str_replace('?', '', $capValue))
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
        $securimage->show(JPATH_SITE . '/media/com_breezingformsng/images/site/captcha/backgrounds/bg6.jpg');

        $this->application->close();
    }

    private function createSecurimage(): Securimage
    {
        VendorHelper::load();

        return new Securimage();
    }

    private function clearOutputBuffer(): void
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
