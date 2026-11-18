<?php

/**
 * Run with Joomla's vendor autoloader and a non-root user:
 * php tests/InstallerCaptchaLibraryCleanupRegression.php /var/www/html/libraries/vendor/autoload.php
 */

declare(strict_types=1);

require $argv[1];

use Joomla\Filesystem\Folder;

$source = file_get_contents(__DIR__ . '/../script.php');
$start = strpos($source, '    private function removeObsoleteCaptchaLibrary(): void');
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
$root = sys_get_temp_dir() . '/bfng-captcha-library-cleanup-' . bin2hex(random_bytes(6));
define('JPATH_ROOT', $root);
define('JPATH_ADMINISTRATOR', $root . '/administrator');
$vendor = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/vendor';
$library = $vendor . '/bgli100/securimage';
$protected = [
    $vendor . '/gregwar/captcha/src/Gregwar/Captcha/Font/captcha0.ttf' => 'New CAPTCHA library',
    $vendor . '/autoload.php' => 'Composer autoloader',
    $vendor . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php' => 'XLSX export',
    JPATH_ADMINISTRATOR . '/components/com_breezingformsng/config.php' => 'Configuration',
    $root . '/media/com_breezingformsng/images/bfng-easter-egg-2026.webp' => 'Easter Egg',
    $root . '/forms/example.php' => 'Form data',
    $root . '/sessions/example' => 'Session data',
];
$populate = static function () use ($library): void {
    mkdir($library . '/audio/en', 0755, true);
    file_put_contents($library . '/securimage.php', 'Old engine');
    file_put_contents($library . '/audio/en/0.wav', 'Old audio');
};

try {
    foreach ($protected as $file => $content) {
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }
        file_put_contents($file, $content);
    }

    // Fresh installation, or an installation already cleaned: nothing to do.
    $runner->removeObsoleteCaptchaLibrary();
    $assert($runner->logs === [], 'A missing library directory must be a no-op.');

    $populate();
    $runner->removeObsoleteCaptchaLibrary();
    $assert(!is_dir($library), 'The old CAPTCHA library must be removed recursively.');
    $assert(!is_dir(dirname($library)), 'The folder of the publisher must go when it is empty.');
    $assert(count($runner->logs) === 1 && $runner->logs[0][1] !== 3, 'Successful removal must be logged.');
    $runner->removeObsoleteCaptchaLibrary();
    $assert(count($runner->logs) === 1, 'Reinstallation must remain idempotent.');

    // Another package of the same publisher keeps its folder.
    $populate();
    mkdir(dirname($library) . '/other', 0755);
    file_put_contents(dirname($library) . '/other/keep.php', 'Another package');
    $runner->removeObsoleteCaptchaLibrary();
    $assert(!is_dir($library), 'The old library must be removed.');
    $assert(is_file(dirname($library) . '/other/keep.php'), 'A sibling package of the publisher must be kept.');
    Folder::delete(dirname($library));

    // Permission failure is genuine only when the harness runs as non-root.
    $populate();
    chmod($library, 0555);
    $runner->removeObsoleteCaptchaLibrary();
    $assert(is_file($library . '/securimage.php'), 'Run this test as non-root to reproduce a genuine deletion failure.');
    $last = $runner->logs[array_key_last($runner->logs)];
    $assert($last[1] === 3 && $last[0] !== '', 'A native deletion failure must be logged as an error.');
    chmod($library, 0755);
    $runner->removeObsoleteCaptchaLibrary();
    $assert(!is_dir($library), 'Removal must succeed once target permissions are corrected.');

    // A deployment symlink must never cause deletion outside the exact target.
    mkdir(dirname($library), 0755, true);
    symlink($vendor . '/gregwar', $library);
    $runner->removeObsoleteCaptchaLibrary();
    $assert(!is_link($library), 'A library symlink itself must be removed.');

    foreach ($protected as $file => $content) {
        $assert(is_file($file) && file_get_contents($file) === $content, 'Protected asset changed: ' . $file);
    }
} finally {
    if (is_dir($library) && !is_link($library)) {
        chmod($library, 0755);
    }
    if (is_dir($root)) {
        Folder::delete($root);
    }
}

echo "PASS: cleanup of the old CAPTCHA library, missing-directory no-op, reinstall, publisher folder, failure reporting, symlink safety and protected assets preserved.\n";
