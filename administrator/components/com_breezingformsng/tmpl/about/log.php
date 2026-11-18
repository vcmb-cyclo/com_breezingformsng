<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Tools / Log: the tail of the latest installation log.
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Language\Text;

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\About\HtmlView $this */

$notAvailable = Text::_('COM_BREEZINGFORMSNG_NOT_AVAILABLE');
/** @psalm-suppress InvalidScope */
$logReport = $this->logReport;
$logFileName = (string) ($logReport['file'] ?? $notAvailable);
$logSize = (int) ($logReport['size'] ?? 0);
$logLoadedAt = (string) ($logReport['loaded_at'] ?? $notAvailable);
$logContent = (string) ($logReport['content'] ?? '');
$logTruncated = (int) ($logReport['truncated'] ?? 0) === 1;
$logTailBytes = (int) ($logReport['tail_bytes'] ?? 0);

// Newest line first.
$logLines = $logContent === '' ? [] : (preg_split('/\r\n|\r|\n/', $logContent) ?: []);
$logDisplayContent = implode(PHP_EOL, array_reverse($logLines));
?>

    <div class="card mt-3" id="bf-about-log">
        <div class="card-body">
            <h3 class="h6 card-title mb-3"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_LOG_TITLE'); ?></h3>
            <p class="text-muted small mb-2">
                <?php echo sprintf(
                    Text::_('COM_BREEZINGFORMSNG_ABOUT_LOG_LAST_READ'),
                    htmlspecialchars($logFileName, ENT_QUOTES, 'UTF-8'),
                    number_format($logSize, 0, '.', ' '),
                    htmlspecialchars($logLoadedAt, ENT_QUOTES, 'UTF-8')
                ); ?>
            </p>

            <?php if ($logTruncated) : ?>
                <div class="alert alert-warning py-2">
                    <?php echo sprintf(Text::_('COM_BREEZINGFORMSNG_ABOUT_LOG_TRUNCATED'), max(1, $logTailBytes)); ?>
                </div>
            <?php endif; ?>

            <?php if ($logDisplayContent === '') : ?>
                <div class="alert alert-info mb-0">
                    <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_LOG_EMPTY'); ?>
                </div>
            <?php else : ?>
                <pre class="bg-body-tertiary text-body p-3 border rounded small mb-0" style="max-height: 420px; overflow: auto;"><?php echo htmlspecialchars($logDisplayContent, ENT_QUOTES, 'UTF-8'); ?></pre>
            <?php endif; ?>
        </div>
    </div>
