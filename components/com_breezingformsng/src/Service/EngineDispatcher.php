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

namespace Vcmb\Component\BreezingformsNG\Site\Service;

defined('_JEXEC') or die;

use Closure;
use Joomla\Input\Input;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\CaptchaCallback;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\FlashUploadCallback;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\OptCallback;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\PayPalCallback;
use Vcmb\Component\BreezingformsNG\Site\Service\Callback\StripeCallback;

/**
 * Dispatches form rendering and the dedicated frontend callbacks.
 */
final class EngineDispatcher
{
    public function __construct(
        private readonly Input $input,
        /** @var Closure(): FormRenderer */
        private readonly Closure $formRendererFactory,
        /** @var Closure(): CaptchaCallback */
        private readonly Closure $captchaCallbackFactory,
        /** @var Closure(): PayPalCallback */
        private readonly Closure $payPalCallbackFactory,
        /** @var Closure(): StripeCallback */
        private readonly Closure $stripeCallbackFactory,
        /** @var Closure(): FlashUploadCallback */
        private readonly Closure $flashUploadCallbackFactory,
        /** @var Closure(): OptCallback */
        private readonly Closure $optCallbackFactory,
    ) {
    }

    /** @param array<string, mixed> $engineContext */
    public function dispatch(array $engineContext, string $application): void
    {
        if (!$this->isCallbackRequest()) {
            ($this->formRendererFactory)()->render($engineContext);

            return;
        }

        if ($this->input->getBool('bfCaptcha', false)) {
            ($this->captchaCallbackFactory)()->image();
        } elseif ($this->input->getBool('checkCaptcha', false)) {
            ($this->captchaCallbackFactory)()->check();
        } elseif ($this->input->getBool('confirmPayPalIpn', false) && $application === '') {
            ($this->payPalCallbackFactory)()->confirmIpn();
        } elseif ($this->input->getBool('confirmStripe', false) && $application === '') {
            ($this->stripeCallbackFactory)()->confirm();
        } elseif ($this->input->getBool('stripeDownload', false) && $application === '') {
            ($this->stripeCallbackFactory)()->download();
        } elseif ($this->input->getBool('confirmPayPal', false) && $application === '') {
            ($this->payPalCallbackFactory)()->confirm();
        } elseif ($this->input->getBool('paypalDownload', false) && $application === '') {
            ($this->payPalCallbackFactory)()->download();
        } elseif ($this->input->getBool('showPayPalConnectMsg', false)) {
            ($this->payPalCallbackFactory)()->connectMessage();
        } elseif ($this->input->getBool('flashUpload', false)) {
            ($this->flashUploadCallbackFactory)()->handle();
        } elseif ($this->input->getString('opt_in', '') === 'true') {
            ($this->optCallbackFactory)()->optIn();
        } elseif ($this->input->getString('opt_out', '') === 'true') {
            ($this->optCallbackFactory)()->optOut();
        }
    }

    private function isCallbackRequest(): bool
    {
        return $this->input->getBool('bfCaptcha', false)
            || $this->input->getBool('checkCaptcha', false)
            || $this->input->getBool('confirmStripe', false)
            || $this->input->getBool('confirmPayPal', false)
            || $this->input->getBool('confirmPayPalIpn', false)
            || $this->input->getBool('paypalDownload', false)
            || $this->input->getBool('stripeDownload', false)
            || $this->input->getBool('showPayPalConnectMsg', false)
            || $this->input->getBool('flashUpload', false)
            || $this->input->getString('opt_in', '') === 'true'
            || $this->input->getString('opt_out', '') === 'true';
    }
}
