<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Site\Service\Security;

\defined('_JEXEC') or die;

use Securimage\CaptchaObject;
use Securimage\StorageAdapter\AdapterInterface;

/**
 * Securimage storage that writes the code of the image into the Joomla session.
 * Its own session adapter stores a CaptchaObject in $_SESSION, which Joomla
 * reads back before Securimage is loaded: the code was never found again.
 */
final class SecurimageSessionAdapter implements AdapterInterface
{
    public function __construct(private readonly CaptchaCodeStore $store)
    {
    }

    #[\Override]
    /**
     * @param string $captchaId
     * @param mixed  $captchaInfo
     */
    public function store($captchaId, $captchaInfo): bool
    {
        if ($captchaInfo instanceof CaptchaObject) {
            $this->store->store((string) $captchaInfo->code);
        }

        return true;
    }

    #[\Override]
    /**
     * @param string $captchaId
     * @param mixed  $audioData
     */
    public function storeAudioData($captchaId, $audioData): bool
    {
        // The audio captcha is not offered.
        return false;
    }

    #[\Override]
    /**
     * @param string      $captchaId
     * @param int         $what
     */
    public function get($captchaId, $what = null): ?CaptchaObject
    {
        $stored = $this->store->get();

        if ($stored === null) {
            return null;
        }

        $captcha = new CaptchaObject();
        $captcha->captchaId = $captchaId;
        $captcha->code = $stored['code'];
        $captcha->code_display = $stored['code'];
        $captcha->creationTime = $stored['time'];

        return $captcha;
    }

    #[\Override]
    /** @param string $captchaId */
    public function delete($captchaId): bool
    {
        $this->store->clear();

        return true;
    }
}
