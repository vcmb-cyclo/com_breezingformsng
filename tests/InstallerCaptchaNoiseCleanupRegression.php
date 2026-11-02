<?php

/**
 * Run with Joomla's vendor autoloader and a non-root user:
 * php tests/InstallerCaptchaNoiseCleanupRegression.php /var/www/html/libraries/vendor/autoload.php
 */

declare(strict_types=1);

require $argv[1];

use Joomla\Filesystem\Folder;

$source = file_get_contents(__DIR__ . '/../script.php');
$start = strpos($source, '    private function removeObsoleteCaptchaNoise(): void');
$end = strpos($source, '    private function removeObsoleteSystemPlugin(): void', $start);
$method = substr($source, $start, $end - $start);
$method = str_replace('private function', 'public function', $method);
$method = str_replace('Log::ERROR', '3', $method);
$runner = eval('use Joomla\\Filesystem\\File; use Joomla\\Filesystem\\Folder;'
    . 'use Joomla\\Filesystem\\Exception\\FilesystemException;'
    . 'use Joomla\\CMS\\Language\\Text;'
    . 'return new class { public array $logs = [];'
    . 'public function log($message, $priority = 6): void { $this->logs[] = [$message, $priority]; }'
    . $method . '};');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$root = sys_get_temp_dir() . '/bfng-noise-cleanup-' . bin2hex(random_bytes(6));
define('JPATH_ROOT', $root);
define('JPATH_ADMINISTRATOR', $root . '/administrator');
$noise = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/vendor/bgli100/securimage/audio/noise';
$protected = [
    dirname($noise) . '/en/0.wav' => 'English audio',
    dirname($noise, 2) . '/securimage.php' => 'CAPTCHA engine',
    JPATH_ADMINISTRATOR . '/components/com_breezingformsng/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php' => 'XLSX export',
    JPATH_ADMINISTRATOR . '/components/com_breezingformsng/config.php' => 'Configuration',
    $root . '/media/com_breezingformsng/images/bfng-easter-egg-2026.webp' => 'Easter Egg',
    $root . '/forms/example.php' => 'Form data',
    $root . '/sessions/example' => 'Session data',
];

try {
    foreach ($protected as $file => $content) {
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
        file_put_contents($file, $content);
    }

    // Fresh installation has no old noise directory and requires no action.
    $runner->removeObsoleteCaptchaNoise();
    $assert($runner->logs === [], 'A missing noise directory must be a no-op.');

    mkdir($noise . '/nested', 0755, true);
    file_put_contents($noise . '/noise.wav', 'Old noise');
    file_put_contents($noise . '/nested/noise.wav', 'Old nested noise');
    $runner->removeObsoleteCaptchaNoise();
    $assert(!is_dir($noise), 'Existing noise files must be removed recursively.');
    $assert(count($runner->logs) === 1 && $runner->logs[0][1] !== 3, 'Successful removal must be logged.');
    $runner->removeObsoleteCaptchaNoise();
    $assert(count($runner->logs) === 1, 'Reinstallation must remain idempotent.');

    // Permission failure is genuine only when the harness runs as non-root.
    mkdir($noise, 0755);
    file_put_contents($noise . '/locked.wav', 'Old noise');
    chmod($noise, 0555);
    $runner->removeObsoleteCaptchaNoise();
    $assert(is_file($noise . '/locked.wav'), 'Run this test as non-root to reproduce a genuine deletion failure.');
    $last = $runner->logs[array_key_last($runner->logs)];
    $assert($last[1] === 3 && $last[0] !== '', 'A native deletion failure must be logged as an error.');
    chmod($noise, 0755);
    $runner->removeObsoleteCaptchaNoise();
    $assert(!is_dir($noise), 'Removal must succeed once target permissions are corrected.');

    // A deployment symlink must never cause deletion outside the exact target.
    symlink(dirname($noise) . '/en', $noise);
    $runner->removeObsoleteCaptchaNoise();
    $assert(!is_link($noise), 'A noise symlink itself must be removed.');

    foreach ($protected as $file => $content) {
        $assert(is_file($file) && file_get_contents($file) === $content, 'Protected asset changed: ' . $file);
    }
} finally {
    if (is_dir($noise) && !is_link($noise)) {
        chmod($noise, 0755);
    }
    if (is_dir($root)) {
        Folder::delete($root);
    }
}

echo "PASS: native cleanup, missing-directory no-op, reinstall, failure reporting, symlink safety, English audio and protected assets preserved.\n";
