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

$report = $argv[1] ?? '';
$minimum = isset($argv[2]) ? (float) $argv[2] : 0.0;
// Optional third argument: the baseline file of the ratchet (see CoverageRatchet).
$baselineFile = $argv[3] ?? '';

require_once __DIR__ . '/CoverageRatchet.php';

if ($report === '' || !is_file($report)) {
    fwrite(STDERR, "::error::Coverage report not found: {$report}\n");
    exit(1);
}

$document = new DOMDocument();

if (!$document->load($report)) {
    fwrite(STDERR, "::error::Unable to read coverage report: {$report}\n");
    exit(1);
}

$metrics = (new DOMXPath($document))->query('/coverage/project/metrics')->item(0);

if (!$metrics instanceof DOMElement) {
    fwrite(STDERR, "::error::Coverage metrics are missing from {$report}\n");
    exit(1);
}

$statements = (int) $metrics->getAttribute('statements');
$covered = (int) $metrics->getAttribute('coveredstatements');
$percentage = $statements === 0 ? 0.0 : ($covered / $statements) * 100;
$summary = sprintf(
    'Line coverage: %.2f%% (%d/%d), required: %.2f%%',
    $percentage,
    $covered,
    $statements,
    $minimum,
);

$stepSummary = getenv('GITHUB_STEP_SUMMARY');

if (is_string($stepSummary) && $stepSummary !== '') {
    file_put_contents($stepSummary, "## PHPUnit coverage\n\n{$summary}\n", FILE_APPEND);
}

$ratchet = null;

if ($baselineFile !== '') {
    try {
        $ratchet = CoverageRatchet::evaluate($percentage, CoverageRatchet::readBaseline($baselineFile));
    } catch (RuntimeException $exception) {
        fwrite(STDERR, "::error::{$exception->getMessage()}\n");
        exit(1);
    }

    $summary .= "\n" . $ratchet['message'];
}

if (is_string($stepSummary) && $stepSummary !== '' && $ratchet !== null) {
    file_put_contents($stepSummary, "\n{$ratchet['message']}\n" . ($ratchet['notice'] !== '' ? "\n{$ratchet['notice']}\n" : ''), FILE_APPEND);
}

fwrite(STDOUT, "{$summary}\n");

if ($ratchet !== null && $ratchet['notice'] !== '') {
    fwrite(STDOUT, "::notice::{$ratchet['notice']}\n");
}

if ($percentage < $minimum) {
    fwrite(STDERR, "::error::Coverage is below the required threshold.\n");
    exit(1);
}

if ($ratchet !== null && $ratchet['failed']) {
    fwrite(STDERR, "::error::Coverage dropped below the baseline of {$baselineFile} minus the tolerance.\n");
    exit(1);
}
