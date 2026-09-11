<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 * 
 * @version 6.0.0
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\About\HtmlView $this */
/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\About\HtmlView $this */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseRepairService;

if (!function_exists('bf_about_read_json_file')) {
    function bf_about_read_json_file($path)
    {
        if (!is_file($path)) {
            return array();
        }

        $jsonData = @file_get_contents($path);

        if (!is_string($jsonData) || $jsonData === '') {
            return array();
        }

        $decoded = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return array();
        }

        return $decoded;
    }
}

if (!function_exists('bf_about_get_version_information')) {
    function bf_about_get_version_information()
    {
        $versionInformation = array(
            'version' => '',
            'creationDate' => '',
        'author' => '',
        'copyright' => '',
        'license' => '',
        'buildType' => '',
        'buildTimestamp' => '',
        );

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_breezingformsng'));

            $db->setQuery($query);
            $manifestCache = (string) $db->loadResult();

            if ($manifestCache !== '') {
                $manifestData = json_decode($manifestCache, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($manifestData)) {
                    $versionInformation['version'] = (string) ($manifestData['version'] ?? '');
                    $versionInformation['creationDate'] = (string) ($manifestData['creationDate'] ?? '');
                    $versionInformation['author'] = (string) ($manifestData['author'] ?? '');
                    $versionInformation['copyright'] = (string) ($manifestData['copyright'] ?? '');
                    $versionInformation['license'] = (string) ($manifestData['license'] ?? '');
                    $versionInformation['buildType'] = (string) ($manifestData['buildType'] ?? '');
                    $versionInformation['buildTimestamp'] = (string) ($manifestData['buildTimestamp'] ?? '');
                }
            }
        } catch (Throwable $e) {
            // Fallback to local manifest files.
        }

        if (!array_filter($versionInformation, static fn(string $value): bool => $value === '')) {
            return $versionInformation;
        }

        $manifestPaths = array(
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/com_breezingformsng.xml',
        );

        foreach ($manifestPaths as $manifestPath) {
            if (!is_file($manifestPath)) {
                continue;
            }

            $manifest = @simplexml_load_file($manifestPath);

            if (!$manifest instanceof SimpleXMLElement) {
                continue;
            }

            $versionInformation['version'] = (string) ($manifest->version ?? '');
            $versionInformation['creationDate'] = (string) ($manifest->creationDate ?? '');
            $versionInformation['author'] = (string) ($manifest->author ?? '');
            $versionInformation['copyright'] = (string) ($manifest->copyright ?? '');
            $versionInformation['license'] = (string) ($manifest->license ?? '');
            $versionInformation['buildType'] = (string) ($manifest->buildType ?? '');
            $versionInformation['buildTimestamp'] = (string) ($manifest->buildTimestamp ?? '');
            break;
        }

        return $versionInformation;
    }
}

if (!function_exists('bf_about_add_php_library')) {
    function bf_about_add_php_library(&$indexedLibraries, $name, $version, $isDev)
    {
        $name = trim((string) $name);
        $version = trim((string) $version);

        if ($name === '') {
            return;
        }

        if (!isset($indexedLibraries[$name])) {
            $indexedLibraries[$name] = array(
                'name' => $name,
                'version' => $version,
                'is_dev' => (bool) $isDev,
            );
            return;
        }

        if ($indexedLibraries[$name]['version'] === '' && $version !== '') {
            $indexedLibraries[$name]['version'] = $version;
        }

        $indexedLibraries[$name]['is_dev'] = (bool) $indexedLibraries[$name]['is_dev'] && (bool) $isDev;
    }
}

if (!function_exists('bf_about_collect_php_libraries_from_installed_json')) {
    function bf_about_collect_php_libraries_from_installed_json(&$indexedLibraries, $installedJsonPath)
    {
        $installedData = bf_about_read_json_file($installedJsonPath);

        if (!$installedData) {
            return;
        }

        $packages = array();

        if (isset($installedData['packages']) && is_array($installedData['packages'])) {
            $packages = $installedData['packages'];
        } elseif (is_array($installedData)) {
            $packages = $installedData;
        }

        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }

            $name = (string) ($package['name'] ?? '');
            $version = (string) ($package['pretty_version'] ?? $package['version'] ?? '');
            $isDev = (bool) ($package['dev_requirement'] ?? false);

            bf_about_add_php_library($indexedLibraries, $name, $version, $isDev);
        }
    }
}

if (!function_exists('bf_about_collect_php_library_from_composer_json')) {
    function bf_about_collect_php_library_from_composer_json(&$indexedLibraries, $composerJsonPath)
    {
        $composerData = bf_about_read_json_file($composerJsonPath);

        if (!$composerData) {
            return;
        }

        $name = (string) ($composerData['name'] ?? '');
        $version = (string) ($composerData['version'] ?? '');
        $isDev = false;

        bf_about_add_php_library($indexedLibraries, $name, $version, $isDev);
    }
}

if (!function_exists('bf_about_get_php_libraries')) {
    function bf_about_get_php_libraries()
    {
        $indexedLibraries = array();

        $vendorInstalled = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/vendor/composer/installed.json';

        bf_about_collect_php_libraries_from_installed_json($indexedLibraries, $vendorInstalled);
        bf_about_add_php_library($indexedLibraries, 'Securimage CAPTCHA', '4.0.4', false);

        $libraries = array_values($indexedLibraries);

        usort($libraries, function ($a, $b) {
            return strcasecmp((string) $a['name'], (string) $b['name']);
        });

        return $libraries;
    }
}

if (!function_exists('bf_about_extract_version_from_file')) {
    function bf_about_extract_version_from_file($filePath)
    {
        if (!is_file($filePath)) {
            return '';
        }

        $contents = @file_get_contents($filePath, false, null, 0, 8192);

        if (!is_string($contents) || $contents === '') {
            return '';
        }

        $patterns = array(
            '/jsTree\s+([0-9A-Za-z\.\-]+)/i',
            '/JQuery\s+([0-9A-Za-z\.\-]+)/i',
            '/version\s*[:=]\s*[\'"]([0-9A-Za-z\.\-]+)/i',
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $contents, $matches)) {
                return trim((string) $matches[1]);
            }
        }

        return '';
    }
}

if (!function_exists('bf_about_get_javascript_libraries')) {
    function bf_about_get_javascript_libraries()
    {
        $notAvailable = Text::_('COM_BREEZINGFORMSNG_NOT_AVAILABLE');
        $bundledSource = Text::_('COM_BREEZINGFORMSNG_JS_LIBRARY_SOURCE_BUNDLED');
        $externalSource = Text::_('COM_BREEZINGFORMSNG_JS_LIBRARY_SOURCE_EXTERNAL');
        $basePath = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/';
        $libraries = array();

        $candidates = array(
            array(
                'name' => 'jQuery',
                'script_path' => JPATH_ROOT . '/media/vendor/jquery/js/jquery.min.js',
                'css_path' => '',
            ),
            array(
                'name' => 'jsTree',
                'script_path' => $basePath . 'jtree/tree_component.min.js',
                'css_path' => $basePath . 'jtree/tree_component.css',
            ),
        );

        foreach ($candidates as $candidate) {
            $scriptPath = (string) $candidate['script_path'];

            if (!is_file($scriptPath)) {
                continue;
            }

            $version = bf_about_extract_version_from_file($scriptPath);
            $assets = 'JS';
            $cssPath = (string) $candidate['css_path'];

            if ($cssPath !== '' && is_file($cssPath)) {
                $assets = 'JS + CSS';
            }

            $libraries[] = array(
                'name' => (string) $candidate['name'],
                'version' => $version !== '' ? $version : $notAvailable,
                'assets' => $assets,
                'source' => $bundledSource,
            );
        }

        $libraries[] = array(
            'name' => 'Google reCAPTCHA',
            'version' => 'v2',
            'assets' => 'JS',
            'source' => $externalSource,
        );

        usort($libraries, function ($a, $b) {
            return strcasecmp((string) $a['name'], (string) $b['name']);
        });

        return $libraries;
    }
}

$versionInformation = bf_about_get_version_information();
$phpLibraries = bf_about_get_php_libraries();
$javascriptLibraries = bf_about_get_javascript_libraries();
$logReport = $this->logReport;
$auditReport = $this->auditReport;
$auditSummary = (array) ($auditReport['summary'] ?? array());
$auditTables = (array) ($auditReport['tables'] ?? array());
$auditMissingTables = (array) ($auditReport['missing_tables'] ?? array());
$auditUnexpectedTables = (array) ($auditReport['unexpected_tables'] ?? array());
$auditStaleLanguageFiles = (array) ($auditReport['stale_language_files'] ?? array());
$auditStaleInstallerTempDirs = (array) ($auditReport['stale_installer_temp_dirs'] ?? array());
$auditCollationIssues = (array) ($auditReport['collation_issues'] ?? array());
$auditColumnCollationIssues = (array) ($auditReport['column_collation_issues'] ?? array());
$auditCollationHistogram = (array) ($auditReport['collation_histogram'] ?? array());
$auditDuplicateIndexes = (array) ($auditReport['duplicate_indexes'] ?? array());
$auditMenuIssues = (array) ($auditReport['menu_issues'] ?? array());
$auditDuplicateForms = (array) ($auditReport['duplicate_forms'] ?? array());
$auditExtensionDuplicates = (array) ($auditReport['extension_duplicates'] ?? array());
$auditExtensionLegacy = (array) ($auditReport['extension_legacy'] ?? array());
$auditErrors = (array) ($auditReport['errors'] ?? array());
$auditOrphanChecks = array_values(array_filter(
    (array) ($auditReport['orphan_checks'] ?? array()),
    static fn(array $check): bool => (int) ($check['count'] ?? 0) > 0
));
$notAvailable = Text::_('COM_BREEZINGFORMSNG_NOT_AVAILABLE');

$versionValue = $versionInformation['version'] !== '' ? $versionInformation['version'] : $notAvailable;
$creationDateValue = $versionInformation['creationDate'] !== '' ? $versionInformation['creationDate'] : $notAvailable;
$authorValue = $versionInformation['author'] !== '' ? $versionInformation['author'] : $notAvailable;
$copyrightValue = $versionInformation['copyright'] !== '' ? $versionInformation['copyright'] : $notAvailable;
$licenseValue = trim((string) ($versionInformation['license'] ?? ''));
$buildTypeValue = strtolower(trim((string) ($versionInformation['buildType'] ?? '')));
$isProductionBuild = $buildTypeValue === 'production';
$buildTypeLabel = Text::_($isProductionBuild
    ? 'COM_BREEZINGFORMSNG_PRODUCTION_BUILD_LABEL'
    : 'COM_BREEZINGFORMSNG_DEV_BUILD_LABEL');
$formatBuildTimestamp = static function (string $timestamp): string {
    if ($timestamp === '') {
        return '';
    }

    try {
        return (new \DateTimeImmutable($timestamp, new \DateTimeZone('UTC')))->format('Y-m-d H:i:s T');
    } catch (\Throwable) {
        return '';
    }
};
$buildTimestampDisplay = $isProductionBuild
    ? $formatBuildTimestamp(trim((string) ($versionInformation['buildTimestamp'] ?? '')))
    : '';
$buildTypeDisplay = $buildTimestampDisplay !== '' ? $buildTypeLabel . ' · ' . $buildTimestampDisplay : $buildTypeLabel;
$genericLicenseValues = array('gpl', 'gnu/gpl', 'gnu/gpl v2 or later');

if ($licenseValue === '' || in_array(strtolower($licenseValue), $genericLicenseValues, true)) {
    $licenseValue = Text::_('COM_BREEZINGFORMSNG_LICENSE_FALLBACK');
}

$licenseUrl = 'https://www.gnu.org/licenses/gpl-2.0.html';
$vcmbUrl = 'https://breezingforms-ng.vcmb.fr';
$githubUrl = 'https://github.com/vcmb-cyclo/com_breezingformsng';
$githubOwner = 'vcmb-cyclo';
$githubRepo = 'com_breezingformsng';
$logFileName = (string) ($logReport['file'] ?? $notAvailable);
$logSize = (int) ($logReport['size'] ?? 0);
$logLoadedAt = (string) ($logReport['loaded_at'] ?? $notAvailable);
$logContent = (string) ($logReport['content'] ?? '');
$logDisplayContent = $logContent;

if ($logDisplayContent !== '') {
    $logLines = preg_split('/\r\n|\r|\n/', $logDisplayContent);

    if (is_array($logLines) && $logLines !== array()) {
        $logDisplayContent = implode(PHP_EOL, array_reverse($logLines));
    }
}

$logTruncated = (int) ($logReport['truncated'] ?? 0) === 1;
$logTailBytes = (int) ($logReport['tail_bytes'] ?? 0);
$aboutDescription = (string) Text::_('COM_BREEZINGFORMSNG_ABOUT_DESC');
$aboutDescription = str_replace(
    '<strong>BreezingForms</strong>',
    '<strong>BreezingForms NG</strong>',
    $aboutDescription
);
$aboutDescription = str_replace(
    'GPL-2.0-or-later',
    '<a href="https://www.gnu.org/licenses/old-licenses/gpl-2.0.html" target="_blank" rel="noopener noreferrer">GPL-2.0-or-later</a>',
    $aboutDescription
);
$aboutDescription = str_replace(
    'GPL-2.0+',
    '<a href="https://www.gnu.org/licenses/old-licenses/gpl-2.0.html" target="_blank" rel="noopener noreferrer">GPL-2.0+</a>',
    $aboutDescription
);
?>

<form action="index.php?option=com_breezingformsng&task=about.display&amp;view=about" method="post" name="adminForm" id="adminForm">
    <div class="bf-about-intro mt-3 mb-3">
        <div class="bf-about-intro-media">
            <img
                src="<?php echo htmlspecialchars(Uri::root(true) . '/media/com_breezingformsng/images/bf_logo.png', ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT'), ENT_QUOTES, 'UTF-8'); ?>"
                class="img-fluid"
                style="max-width: 150px; height: auto;"
                loading="lazy"
            />
        </div>
        <div class="bf-about-intro-content">
            <p class="mb-0">
                <?php echo $aboutDescription; ?>
            </p>
            <div class="bf-about-intro-links">
                <a class="bf-about-intro-link bf-about-intro-link--vcmb" href="<?php echo htmlspecialchars($vcmbUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_BREEZINGFORMSNG_VCMB_LINK'); ?></a>
                <a class="bf-about-intro-link bf-about-intro-link--github" href="<?php echo htmlspecialchars($githubUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_BREEZINGFORMSNG_GITHUB_LINK'); ?></a>
                <iframe
                    src="<?php echo htmlspecialchars('https://ghbtns.com/github-btn.html?user=' . $githubOwner . '&repo=' . $githubRepo . '&type=star&count=true&size=large', ENT_QUOTES, 'UTF-8'); ?>"
                    frameborder="0"
                    scrolling="0"
                    width="170"
                    height="30"
                    title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_GITHUB_STARS'), ENT_QUOTES, 'UTF-8'); ?>"
                    style="display:block;align-self:center;"
                    loading="lazy"
                ></iframe>
                <a class="bf-about-intro-link bf-about-intro-link--license" href="<?php echo htmlspecialchars($licenseUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_BREEZINGFORMSNG_LICENSE_LINK'); ?></a>
                <a class="bf-about-intro-link bf-about-intro-link--log" href="#bf-about-log"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_SHOW_LOG'); ?></a>
            </div>
        </div>
    </div>

    <?php if ($auditReport !== array()) : ?>
        <div class="card mt-3" id="bf-audit-section">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h3 class="h5 mb-0"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_REPORT_TITLE'); ?></h3>
                    <span class="text-muted small">
                        <?php echo Text::sprintf(
                            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_GENERATED_AT',
                            htmlspecialchars((string) ($auditReport['generated_at'] ?? $notAvailable), ENT_QUOTES, 'UTF-8')
                        ); ?>
                    </span>
                </div>

                <?php if ((int) ($auditSummary['audit_errors'] ?? 0) > 0) : ?>
                    <div class="alert alert-danger bf-audit-error-alert">
                        <?php echo Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_REPORT_ERRORS', (int) ($auditSummary['audit_errors'] ?? 0)); ?>
                    </div>
                <?php elseif ((int) ($auditSummary['issues_total'] ?? 0) === 0) : ?>
                    <div class="alert alert-success bf-audit-ok-alert">
                        <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_REPORT_CLEAN'); ?>
                    </div>
                <?php else : ?>
                    <div class="alert alert-warning bf-audit-warning-alert">
                        <?php echo Text::plural('COM_BREEZINGFORMSNG_ABOUT_AUDIT_REPORT_ISSUES', (int) ($auditSummary['issues_total'] ?? 0)); ?>
                    </div>
                <?php endif; ?>

                <?php if ($auditErrors !== array()) : ?>
                    <div class="bf-audit-section-block mb-3 border-danger">
                        <h4 class="h6 text-danger"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ERRORS'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditErrors as $error) : ?>
                                <li><code><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <dl class="row mb-3">
                    <dt class="col-sm-4"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLES'); ?></dt>
                    <dd class="col-sm-8"><?php echo (int) ($auditSummary['scanned_tables'] ?? 0); ?> / <?php echo (int) ($auditSummary['expected_tables'] ?? 0); ?></dd>
                    <dt class="col-sm-4"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ROWS'); ?></dt>
                    <dd class="col-sm-8"><?php echo number_format((int) ($auditSummary['total_rows'] ?? 0), 0, '.', ' '); ?></dd>
                    <dt class="col-sm-4"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHANS'); ?></dt>
                    <dd class="col-sm-8"><?php echo (int) ($auditSummary['orphan_rows'] ?? 0); ?></dd>
                </dl>

                <?php if ($auditMissingTables !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MISSING_TABLES'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditMissingTables as $table) : ?>
                                <li><code><?php echo htmlspecialchars((string) $table, ENT_QUOTES, 'UTF-8'); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditUnexpectedTables !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_UNEXPECTED_TABLES'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditUnexpectedTables as $table) : ?>
                                <li><code><?php echo htmlspecialchars((string) $table, ENT_QUOTES, 'UTF-8'); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditStaleLanguageFiles !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_LANGUAGE_FILES'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditStaleLanguageFiles as $file) : ?>
                                <li><code><?php echo htmlspecialchars((string) $file, ENT_QUOTES, 'UTF-8'); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditStaleInstallerTempDirs !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP'); ?></h4>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-check mb-0">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    data-bf-select-all="stale-installer-temp"
                                    aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_SELECT_ALL'), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <span class="form-check-label"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_SELECT_ALL'); ?></span>
                            </label>
                            <button
                                type="submit"
                                class="btn btn-sm btn-warning"
                                onclick="document.getElementById('bf-about-task').value='about.deleteStaleInstallerTemp';"
                                title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_REPAIR_SELECTED_TIP'), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <span class="icon-trash me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_REPAIR_SELECTED'); ?>
                            </button>
                        </div>
                        <ul class="mb-0">
                            <?php foreach ($auditStaleInstallerTempDirs as $index => $dir) : ?>
                                <li>
                                    <input
                                        type="checkbox"
                                        class="form-check-input me-1"
                                        data-bf-select-item="stale-installer-temp"
                                        name="stale_installer_temp_dirs[]"
                                        value="<?php echo htmlspecialchars((string) $dir, ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_SELECT_ONE', (int) $index + 1), ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                    <code><?php echo htmlspecialchars((string) $dir, ENT_QUOTES, 'UTF-8'); ?></code>
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-warning ms-2"
                                        name="stale_installer_temp_dir"
                                        value="<?php echo htmlspecialchars((string) $dir, ENT_QUOTES, 'UTF-8'); ?>"
                                        onclick="document.getElementById('bf-about-task').value='about.deleteStaleInstallerTemp';"
                                        title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_REPAIR_TIP'), ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_STALE_INSTALLER_TEMP_REPAIR'), ENT_QUOTES, 'UTF-8'); ?>"
                                    ><span class="icon-trash" aria-hidden="true"></span></button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditMenuIssues !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_ISSUES'); ?></h4>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_FORM'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ISSUE'); ?></th></tr></thead>
                                <tbody>
                                <?php foreach ($auditMenuIssues as $issue) : ?>
                                    <tr class="table-warning">
                                        <td><?php echo htmlspecialchars((string) ($issue['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> <span class="text-muted">(#<?php echo (int) ($issue['menu_id'] ?? 0); ?>)</span></td>
                                        <td><code><?php echo htmlspecialchars((string) ($issue['form_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                                        <td>
                                            <?php foreach ((array) ($issue['issues'] ?? array()) as $code) : ?>
                                                <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MENU_' . strtoupper((string) $code)); ?><br />
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($auditDuplicateForms !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORMS'); ?></h4>
                        <p class="text-muted small mb-2"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORMS_DESC'); ?></p>
                        <ul class="mb-0">
                            <?php foreach ($auditDuplicateForms as $group) : ?>
                                <li>
                                    <code><?php echo htmlspecialchars((string) ($group['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                    <?php if ((string) ($group['package'] ?? '') !== '') : ?>
                                        <span class="text-muted">(<?php echo htmlspecialchars((string) $group['package'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                                    <?php endif; ?>
                                    &mdash;
                                    <?php echo Text::plural(
                                        'COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORMS_HINT_KEEP',
                                        (int) ($group['keep']['record_count'] ?? 0),
                                        (int) ($group['keep']['id'] ?? 0)
                                    ); ?>
                                    <?php foreach ((array) ($group['drop'] ?? array()) as $entry) : ?>
                                        <?php
                                        $dropId = (int) ($entry['id'] ?? 0);
                                        $dropRecordCount = (int) ($entry['record_count'] ?? 0);
                                        ?>
                                        <span class="text-nowrap me-1">
                                            #<?php echo $dropId; ?><?php if ($dropRecordCount > 0) : ?><span class="text-muted"> (<?php echo $dropRecordCount; ?>)</span><?php else : ?><button
                                                type="button"
                                                class="btn btn-sm btn-link link-danger p-0 align-baseline"
                                                title="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_DELETE', $dropId), ENT_QUOTES, 'UTF-8'); ?>"
                                                onclick="if (window.confirm('<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_DELETE_CONFIRM', $dropId), ENT_QUOTES, 'UTF-8'); ?>')) { document.getElementById('bf-duplicate-form-id').value = '<?php echo $dropId; ?>'; Joomla.submitform('about.deleteDuplicateForm'); }"
                                            ><span class="icon-trash" aria-hidden="true"></span><span class="visually-hidden"><?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_FORM_DELETE', $dropId), ENT_QUOTES, 'UTF-8'); ?></span></button><?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditExtensionDuplicates !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_DUPLICATES'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditExtensionDuplicates as $group) : ?>
                                <li>
                                    <code><?php echo htmlspecialchars((string) ($group['keep']['type'] ?? '') . '/' . (string) ($group['keep']['element'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                    &mdash;
                                    <?php echo Text::sprintf(
                                        'COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_DUPLICATE_HINT',
                                        (int) ($group['keep']['extension_id'] ?? 0),
                                        htmlspecialchars(implode(', ', array_map(
                                            static fn(array $entry): string => '#' . (int) ($entry['extension_id'] ?? 0),
                                            (array) ($group['drop'] ?? array())
                                        )), ENT_QUOTES, 'UTF-8')
                                    ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditExtensionLegacy !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXTENSION_LEGACY'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditExtensionLegacy as $entry) : ?>
                                <li>
                                    <code><?php echo htmlspecialchars((string) ($entry['type'] ?? '') . '/' . ((string) ($entry['folder'] ?? '') !== '' ? (string) $entry['folder'] . '/' : '') . (string) ($entry['element'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                    <span class="text-muted">(#<?php echo (int) ($entry['extension_id'] ?? 0); ?>)</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditCollationIssues !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLLATIONS'); ?></h4>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-check mb-0">
                                <input type="checkbox" class="form-check-input" data-bf-select-all="table-collations" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'), ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="form-check-label"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'); ?></span>
                            </label>
                            <button type="submit" class="btn btn-sm btn-warning" onclick="document.getElementById('bf-about-task').value='about.repairTableCollations';document.getElementById('bf-table-collation-issue').value='';"><span class="fa-solid fa-wrench me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR_SELECTED'); ?></button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_CURRENT'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXPECTED'); ?></th><th><?php echo Text::_('JGRID_HEADING_ACTIONS'); ?></th></tr></thead>
                                <tbody>
                                <?php foreach ($auditCollationIssues as $index => $issue) : ?>
                                    <?php $tableCollationToken = DatabaseRepairService::getTableCollationSelectionToken((array) $issue); ?>
                                    <tr class="table-warning"><td><code><?php echo htmlspecialchars((string) ($issue['table'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars((string) ($issue['collation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($issue['expected'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td class="text-nowrap"><input type="checkbox" class="form-check-input me-2" data-bf-select-item="table-collations" name="table_collation_issues[]" value="<?php echo htmlspecialchars($tableCollationToken, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ONE', (int) $index + 1), ENT_QUOTES, 'UTF-8'); ?>"><button type="submit" class="btn btn-sm btn-warning" onclick="document.getElementById('bf-about-task').value='about.repairTableCollations';document.getElementById('bf-table-collation-issue').value='<?php echo $tableCollationToken; ?>';" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_MIGRATE_PACKED_DATA'), ENT_QUOTES, 'UTF-8'); ?>"><span class="fa-solid fa-wrench" aria-hidden="true"></span></button></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($auditColumnCollationIssues !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATIONS'); ?></h4>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-check mb-0">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    data-bf-select-all="column-collations"
                                    aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_SELECT_ALL'), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <span class="form-check-label"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_SELECT_ALL'); ?></span>
                            </label>
                            <button
                                type="submit"
                                class="btn btn-sm btn-warning"
                                onclick="document.getElementById('bf-about-task').value='about.repairColumnCollations';document.getElementById('bf-column-collation-issue').value='';"
                                title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_REPAIR_SELECTED_TIP'), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <span class="fa-solid fa-wrench me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_REPAIR_SELECTED'); ?>
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_CHARSET'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLLATION'); ?></th><th><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_EXPECTED'); ?></th><th><?php echo Text::_('JGRID_HEADING_ACTIONS'); ?></th></tr></thead>
                                <tbody>
                                <?php foreach ($auditColumnCollationIssues as $index => $issue) : ?>
                                    <?php $columnCollationToken = DatabaseRepairService::getColumnCollationSelectionToken((array) $issue); ?>
                                    <tr class="table-warning"><td><code><?php echo htmlspecialchars((string) ($issue['table'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td><td><code><?php echo htmlspecialchars((string) ($issue['column'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td><td><?php echo htmlspecialchars((string) ($issue['charset'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($issue['collation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($issue['expected_charset'] ?? '') . ' / ' . (string) ($issue['expected'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td class="text-nowrap"><input type="checkbox" class="form-check-input me-2" data-bf-select-item="column-collations" name="column_collation_issues[]" value="<?php echo htmlspecialchars($columnCollationToken, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_SELECT_ONE', (int) $index + 1), ENT_QUOTES, 'UTF-8'); ?>"><button type="submit" class="btn btn-sm btn-warning" onclick="document.getElementById('bf-about-task').value='about.repairColumnCollations';document.getElementById('bf-column-collation-issue').value='<?php echo $columnCollationToken; ?>';" title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_REPAIR_TIP'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLUMN_COLLATION_REPAIR'), ENT_QUOTES, 'UTF-8'); ?>"><span class="fa-solid fa-wrench" aria-hidden="true"></span></button></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (count($auditCollationHistogram) > 1) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_MIXED_COLLATIONS'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditCollationHistogram as $collationName => $count) : ?>
                                <li><code><?php echo htmlspecialchars((string) $collationName, ENT_QUOTES, 'UTF-8'); ?></code>: <?php echo (int) $count; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditDuplicateIndexes !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEXES'); ?></h4>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-check mb-0">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    data-bf-select-all="duplicate-indexes"
                                    aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'), ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                <span class="form-check-label"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ALL'); ?></span>
                            </label>
                            <button
                                type="submit"
                                class="btn btn-sm btn-warning"
                                onclick="document.getElementById('bf-about-task').value='about.repairDuplicateIndexes';document.getElementById('bf-duplicate-index-group').value='';"
                                title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR_SELECTED_TIP'), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <span class="fa-solid fa-wrench me-1" aria-hidden="true"></span><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR_SELECTED'); ?>
                            </button>
                        </div>
                        <ul class="mb-0">
                            <?php foreach ($auditDuplicateIndexes as $index => $issue) : ?>
                                <?php $duplicateIndexToken = DatabaseRepairService::getDuplicateIndexSelectionToken((array) $issue); ?>
                                <li>
                                    <input
                                        type="checkbox"
                                        class="form-check-input me-1"
                                        data-bf-select-item="duplicate-indexes"
                                        name="duplicate_index_groups[]"
                                        value="<?php echo htmlspecialchars($duplicateIndexToken, ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="<?php echo htmlspecialchars(Text::sprintf('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_SELECT_ONE', (int) $index + 1), ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                    <code><?php echo htmlspecialchars((string) ($issue['table'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>:
                                    <?php echo htmlspecialchars(implode(', ', (array) ($issue['indexes'] ?? array())), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (($issue['keep'] ?? '') !== '' && (array) ($issue['drop'] ?? array()) !== array()) : ?>
                                        &mdash;
                                        <?php echo Text::sprintf(
                                            'COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_HINT',
                                            htmlspecialchars((string) $issue['keep'], ENT_QUOTES, 'UTF-8'),
                                            htmlspecialchars(implode(', ', (array) $issue['drop']), ENT_QUOTES, 'UTF-8')
                                        ); ?>
                                    <?php endif; ?>
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-warning ms-2"
                                        onclick="document.getElementById('bf-about-task').value='about.repairDuplicateIndexes';document.getElementById('bf-duplicate-index-group').value='<?php echo $duplicateIndexToken; ?>';"
                                        title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR_TIP'), ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_DUPLICATE_INDEX_REPAIR'), ENT_QUOTES, 'UTF-8'); ?>"
                                    ><span class="fa-solid fa-wrench" aria-hidden="true"></span></button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($auditOrphanChecks !== array()) : ?>
                    <div class="bf-audit-section-block mb-3">
                        <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ORPHAN_REFERENCES'); ?></h4>
                        <ul class="mb-0">
                            <?php foreach ($auditOrphanChecks as $check) : ?>
                                <li><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_' . strtoupper((string) ($check['id'] ?? ''))); ?>: <strong><?php echo (int) ($check['count'] ?? 0); ?></strong></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="bf-audit-section-block">
                    <h4 class="h6"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE_INVENTORY'); ?></h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle mb-0" data-bf-sortable-table>
                            <thead><tr><th aria-sort="none"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none" data-bf-table-sort="text"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_TABLE'); ?></button></th><th aria-sort="none"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none" data-bf-table-sort="number"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ROWS'); ?></button></th><th aria-sort="none"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none" data-bf-table-sort="text"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_ENGINE'); ?></button></th><th aria-sort="none"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none" data-bf-table-sort="text"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_COLLATION'); ?></button></th><th aria-sort="none"><button type="button" class="btn btn-link p-0 text-reset text-decoration-none" data-bf-table-sort="number"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_AUDIT_SIZE'); ?></button></th></tr></thead>
                            <tbody>
                            <?php foreach ($auditTables as $table) : ?>
                                <tr><td><code><?php echo htmlspecialchars((string) ($table['table'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td><td data-bf-sort-value="<?php echo (int) ($table['rows'] ?? 0); ?>"><?php echo number_format((int) ($table['rows'] ?? 0), 0, '.', ' '); ?></td><td><?php echo htmlspecialchars((string) ($table['engine'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars((string) ($table['collation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><td data-bf-sort-value="<?php echo (int) ($table['size_bytes'] ?? 0); ?>"><?php echo HTMLHelper::_('number.bytes', (int) ($table['size_bytes'] ?? 0)); ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card mt-3 bf-about-version-card">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 bf-about-version-header">
                <h3 class="h5 mb-0 bf-about-version-title"><?php echo Text::_('COM_BREEZINGFORMSNG_VERSION_INFORMATION'); ?></h3>
                <span class="bf-about-version-meta">
                    <span class="bf-about-version-badge">BreezingForms NG</span>
                    <span class="bf-about-version-badge <?php echo $isProductionBuild ? 'bf-about-version-badge--production' : 'bf-about-version-badge--dev'; ?>"><?php echo htmlspecialchars($buildTypeDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="bf-about-platform-badges" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_PLATFORM_BADGES_LABEL'), ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="bf-about-platform-badge"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_PLATFORM_JOOMLA_6'); ?></span>
                        <span class="bf-about-platform-badge"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_PLATFORM_PHP_83'); ?></span>
                    </span>
                </span>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-lg-2">
                    <div class="bf-about-version-tile bf-about-version-tile--version">
                        <span class="bf-about-version-icon" aria-hidden="true">VER</span>
                        <p class="bf-about-version-label"><?php echo Text::_('COM_BREEZINGFORMSNG_VERSION_LABEL'); ?></p>
                        <p class="bf-about-version-value"><?php echo htmlspecialchars((string) $versionValue, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <div class="bf-about-version-tile bf-about-version-tile--date">
                        <span class="bf-about-version-icon" aria-hidden="true">DATE</span>
                        <p class="bf-about-version-label"><?php echo Text::_('COM_BREEZINGFORMSNG_CREATION_DATE_LABEL'); ?></p>
                        <p class="bf-about-version-value"><?php echo htmlspecialchars((string) $creationDateValue, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="bf-about-version-tile bf-about-version-tile--author">
                        <span class="bf-about-version-icon" aria-hidden="true">DEV</span>
                        <p class="bf-about-version-label"><?php echo Text::_('COM_BREEZINGFORMSNG_COPYRIGHT_LABEL'); ?></p>
                        <p class="bf-about-version-value"><?php echo htmlspecialchars((string) $copyrightValue, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <div class="col-12 col-md-12 col-lg-4">
                    <div class="bf-about-version-tile bf-about-version-tile--license">
                        <span class="bf-about-version-icon" aria-hidden="true">GPL</span>
                        <p class="bf-about-version-label"><?php echo Text::_('COM_BREEZINGFORMSNG_LICENSE_LABEL'); ?></p>
                        <p class="bf-about-version-value"><?php echo htmlspecialchars((string) $licenseValue, ENT_QUOTES, 'UTF-8'); ?></p>
                        <a class="bf-about-version-link" href="<?php echo htmlspecialchars($licenseUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_BREEZINGFORMSNG_LICENSE_LINK'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <div class="card mt-3">
        <div class="card-body">
            <h3 class="h6 card-title mb-3"><?php echo Text::_('COM_BREEZINGFORMSNG_PHP_LIBRARIES'); ?></h3>
            <?php if (empty($phpLibraries)) : ?>
                <div class="alert alert-info mb-0">
                    <?php echo Text::_('COM_BREEZINGFORMSNG_PHP_LIBRARIES_NOT_AVAILABLE'); ?>
                </div>
            <?php else : ?>
                <p class="text-muted small">
                    <?php echo Text::plural('COM_BREEZINGFORMSNG_PHP_LIBRARIES_COUNT', count($phpLibraries)); ?>
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_PHP_LIBRARY'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_PHP_LIBRARY_VERSION'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_PHP_LIBRARY_SCOPE'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($phpLibraries as $library) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($library['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) (($library['version'] ?? '') !== '' ? $library['version'] : $notAvailable), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo Text::_(!empty($library['is_dev']) ? 'COM_BREEZINGFORMSNG_PHP_LIBRARY_SCOPE_DEV' : 'COM_BREEZINGFORMSNG_PHP_LIBRARY_SCOPE_RUNTIME'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h3 class="h6 card-title mb-3"><?php echo Text::_('COM_BREEZINGFORMSNG_JS_LIBRARIES'); ?></h3>
            <?php if (empty($javascriptLibraries)) : ?>
                <div class="alert alert-info mb-0">
                    <?php echo Text::_('COM_BREEZINGFORMSNG_JS_LIBRARIES_NOT_AVAILABLE'); ?>
                </div>
            <?php else : ?>
                <p class="text-muted small">
                    <?php echo Text::plural('COM_BREEZINGFORMSNG_JS_LIBRARIES_COUNT', count($javascriptLibraries)); ?>
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_JS_LIBRARY'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_JS_LIBRARY_VERSION'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_JS_LIBRARY_ASSETS'); ?></th>
                            <th scope="col"><?php echo Text::_('COM_BREEZINGFORMSNG_JS_LIBRARY_SOURCE'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($javascriptLibraries as $library) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($library['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($library['version'] ?? $notAvailable), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($library['assets'] ?? $notAvailable), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($library['source'] ?? $notAvailable), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <input type="hidden" name="option" value="com_breezingformsng" />
    <input type="hidden" name="task" id="bf-about-task" value="about.display" />
    <input type="hidden" name="view" value="about" />
    <input type="hidden" name="duplicate_form_id" id="bf-duplicate-form-id" value="" />
    <input type="hidden" name="duplicate_index_group" id="bf-duplicate-index-group" value="" />
    <input type="hidden" name="column_collation_issue" id="bf-column-collation-issue" value="" />
    <input type="hidden" name="table_collation_issue" id="bf-table-collation-issue" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
