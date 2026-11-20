<?php
/**
 * BreezingForms NG - A Joomla Forms Application
 * 
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\About\HtmlView $this */
/** @var \Vcmb\Component\BreezingformsNG\Administrator\View\About\HtmlView $this */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseRepairService;



if (!function_exists('bf_about_read_json_file')) {
    /**
     * @param string $path
     * @return array<mixed>
     */
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
    /**
     * @return array{version: string, creationDate: string, author: string, copyright: string, license: string, buildType: string, buildTimestamp: string}
     */
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
            $query = $db->createQuery()
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
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/breezingformsng.xml',
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
    /**
     * @param array<string, array{name: string, version: string, is_dev: bool}> $indexedLibraries
     * @param string $name
     * @param string $version
     * @param bool $isDev
     * @return void
     */
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
    /**
     * @param array<string, array{name: string, version: string, is_dev: bool}> $indexedLibraries
     * @param string $installedJsonPath
     * @return void
     */
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
    /**
     * @param array<string, array{name: string, version: string, is_dev: bool}> $indexedLibraries
     * @param string $composerJsonPath
     * @return void
     */
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
    /**
     * @return list<array{name: string, version: string, is_dev: bool}>
     */
    function bf_about_get_php_libraries()
    {
        $indexedLibraries = array();

        $vendorInstalled = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/vendor/composer/installed.json';

        bf_about_collect_php_libraries_from_installed_json($indexedLibraries, $vendorInstalled);

        $libraries = array_values($indexedLibraries);

        usort($libraries, function ($a, $b) {
            return strcasecmp((string) $a['name'], (string) $b['name']);
        });

        return $libraries;
    }
}

if (!function_exists('bf_about_extract_version_from_file')) {
    /**
     * @param string $filePath
     * @return string
     */
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
            '/jsTree[\s\-]+v?([0-9]+(?:\.[0-9]+)*)/i',
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
    /**
     * @return list<array{name: string, version: string, assets: string, source: string}>
     */
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
                'script_path' => $basePath . 'jstree3/jstree.min.js',
                'css_path' => $basePath . 'jstree3/themes/default/style.min.css',
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
            'external' => true,
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
$licenseShortValue = preg_match('/gpl|general public license/i', $licenseValue) === 1
    ? Text::_('COM_BREEZINGFORMSNG_LICENSE_SHORT')
    : $licenseValue;

$creationDateDisplay = $creationDateValue;

if ($versionInformation['creationDate'] !== '') {
    try {
        $creationDateDisplay = (new \IntlDateFormatter(
            str_replace('-', '_', Factory::getApplication()->getLanguage()->getTag()),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            'UTC',
            \IntlDateFormatter::GREGORIAN,
            'd MMMM y'
        ))->format(new \DateTimeImmutable($versionInformation['creationDate'], new \DateTimeZone('UTC'))) ?: $creationDateValue;
    } catch (\Throwable) {
        $creationDateDisplay = $creationDateValue;
    }
}

$authorDisplay = $authorValue;
$copyrightLegend = $copyrightValue;

if (preg_match('/(\d{4})(?:\s*[-–]\s*(\d{4}))?/u', $copyrightValue, $copyrightYears) === 1) {
    $copyrightLegend = '© ' . $copyrightYears[1] . (isset($copyrightYears[2]) && $copyrightYears[2] !== $copyrightYears[1] ? '–' . $copyrightYears[2] : '');
}
$vcmbUrl = 'https://breezingforms-ng.vcmb.fr';
$githubUrl = 'https://github.com/vcmb-cyclo/com_breezingformsng';
$githubOwner = 'vcmb-cyclo';
$githubRepo = 'com_breezingformsng';
$aboutDescription = Text::_('COM_BREEZINGFORMSNG_ABOUT_DESC');

HTMLHelper::_('bootstrap.collapse');
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
            <button
                type="button"
                class="bf-about-easter-egg-hotspot"
                data-bf-easter-egg-hotspot
                aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT'), ENT_QUOTES, 'UTF-8'); ?>"
            ></button>
        </div>
        <div class="bf-about-intro-content">
            <p class="mb-0">
                <?php echo $aboutDescription; ?>
            </p>
            <div class="bf-about-intro-links">
                <a class="bf-about-intro-link bf-about-intro-link--vcmb" href="<?php echo htmlspecialchars($vcmbUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_BREEZINGFORMSNG_VCMB_LINK'); ?></a>
                <a class="bf-about-intro-link bf-about-intro-link--github" data-bf-github-stars="<?php echo htmlspecialchars($githubOwner . '/' . $githubRepo, ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars($githubUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_BREEZINGFORMSNG_GITHUB_LINK'); ?><span class="bf-about-github-stars" hidden title="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_GITHUB_STARS'), ENT_QUOTES, 'UTF-8'); ?>"><span aria-hidden="true">★</span> <span data-bf-github-stars-count></span></span></a>
            </div>
        </div>
    </div>

    <div class="card mt-3 bf-about-version-card">
        <div class="bf-about-version-header">
            <h3 class="h5 mb-0 bf-about-version-title"><?php echo Text::_('COM_BREEZINGFORMSNG_VERSION_INFORMATION'); ?></h3>
            <span class="bf-about-platform-badges" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT_PLATFORM_BADGES_LABEL'), ENT_QUOTES, 'UTF-8'); ?>">
                <span class="bf-about-platform-badge bf-about-platform-badge--joomla">
                    <span class="icon-joomla" aria-hidden="true"></span>
                    <?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_PLATFORM_JOOMLA_6'); ?>
                </span>
                <span class="bf-about-platform-badge"><?php echo Text::_('COM_BREEZINGFORMSNG_ABOUT_PLATFORM_PHP_84'); ?></span>
            </span>
        </div>

        <div class="bf-about-version-grid">
            <div class="bf-about-version-tile bf-about-version-tile--version">
                <span class="bf-about-version-icon"><?php echo Text::_('COM_BREEZINGFORMSNG_VERSION_LABEL'); ?></span>
                <p class="bf-about-version-value"><?php echo htmlspecialchars((string) $versionValue, ENT_QUOTES, 'UTF-8'); ?></p>
                <span class="bf-about-version-sub"><?php echo htmlspecialchars($buildTypeDisplay, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="bf-about-version-tile bf-about-version-tile--date">
                <span class="bf-about-version-icon"><?php echo Text::_('COM_BREEZINGFORMSNG_CREATION_DATE_LABEL'); ?></span>
                <p class="bf-about-version-value"><?php echo htmlspecialchars((string) $creationDateDisplay, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="bf-about-version-tile bf-about-version-tile--author">
                <span class="bf-about-version-icon"><?php echo Text::_('COM_BREEZINGFORMSNG_AUTHOR_LABEL'); ?></span>
                <p class="bf-about-version-value"><?php echo htmlspecialchars((string) $authorDisplay, ENT_QUOTES, 'UTF-8'); ?></p>
                <span class="bf-about-version-sub"><?php echo htmlspecialchars($copyrightLegend, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="bf-about-version-tile bf-about-version-tile--license">
                <span class="bf-about-version-icon"><?php echo Text::_('COM_BREEZINGFORMSNG_LICENSE_LABEL'); ?></span>
                <p class="bf-about-version-value"><?php echo htmlspecialchars((string) $licenseShortValue, ENT_QUOTES, 'UTF-8'); ?></p>
                <a class="bf-about-version-sub" href="<?php echo htmlspecialchars($licenseUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo Text::_('COM_BREEZINGFORMSNG_LICENSE_LINK'); ?></a>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body p-0">
            <div class="bf-about-libraries" id="bf-about-php-libraries">
                <h3 class="h6 mb-0 bf-about-libraries-title">
                    <a href="#" id="bf-about-php-libraries-toggle" class="bf-collapse-toggle collapsed d-block text-decoration-none text-reset"
                        data-bs-toggle="collapse" data-bs-target="#bf-about-php-libraries-collapse"
                        aria-expanded="false" aria-controls="bf-about-php-libraries-collapse">
                        <span class="icon-chevron-down bf-collapse-icon" aria-hidden="true"></span> <?php echo Text::plural('COM_BREEZINGFORMSNG_N_PHP_LIBRARIES', count($phpLibraries)); ?>
                    </a>
                </h3>
                <div id="bf-about-php-libraries-collapse" class="collapse">
                    <div class="bf-about-libraries-body">
                        <?php if (empty($phpLibraries)) : ?>
                            <div class="alert alert-info mb-0">
                                <?php echo Text::_('COM_BREEZINGFORMSNG_PHP_LIBRARIES_NOT_AVAILABLE'); ?>
                            </div>
                        <?php else : ?>
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
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body p-0">
            <div class="bf-about-libraries" id="bf-about-js-libraries">
                <h3 class="h6 mb-0 bf-about-libraries-title">
                    <a href="#" id="bf-about-js-libraries-toggle" class="bf-collapse-toggle collapsed d-block text-decoration-none text-reset"
                        data-bs-toggle="collapse" data-bs-target="#bf-about-js-libraries-collapse"
                        aria-expanded="false" aria-controls="bf-about-js-libraries-collapse">
                        <span class="icon-chevron-down bf-collapse-icon" aria-hidden="true"></span> <?php echo Text::plural('COM_BREEZINGFORMSNG_N_JS_LIBRARIES', count($javascriptLibraries)); ?>
                    </a>
                </h3>
                <div id="bf-about-js-libraries-collapse" class="collapse">
                    <div class="bf-about-libraries-body">
                        <?php if (empty($javascriptLibraries)) : ?>
                            <div class="alert alert-info mb-0">
                                <?php echo Text::_('COM_BREEZINGFORMSNG_JS_LIBRARIES_NOT_AVAILABLE'); ?>
                            </div>
                        <?php else : ?>
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
                                            <td>
                                                <?php if (!empty($library['external'])) : ?>
                                                    <span class="bf-about-source-badge bf-about-source-badge--external"><?php echo htmlspecialchars((string) ($library['source'] ?? $notAvailable), ENT_QUOTES, 'UTF-8'); ?></span>
                                                <?php else : ?>
                                                    <?php echo htmlspecialchars((string) ($library['source'] ?? $notAvailable), ENT_QUOTES, 'UTF-8'); ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="option" value="com_breezingformsng" />
    <input type="hidden" name="task" id="bf-about-task" value="about.display" />
    <input type="hidden" name="view" value="about" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<div class="bf-about-easter-egg" data-bf-easter-egg hidden aria-hidden="true">
    <div class="bf-about-easter-egg-dialog" role="dialog" aria-modal="true" aria-label="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT'), ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="bf-about-easter-egg-close" data-bf-easter-egg-close aria-label="<?php echo htmlspecialchars(Text::_('JCLOSE'), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="fa-solid fa-xmark" aria-hidden="true"></span>
        </button>
        <img data-bf-easter-egg-image
            data-src="<?php echo htmlspecialchars(Uri::root(true) . '/media/com_breezingformsng/images/bfng-easter-egg-2026.webp', ENT_QUOTES, 'UTF-8'); ?>"
            alt="<?php echo htmlspecialchars(Text::_('COM_BREEZINGFORMSNG_ABOUT'), ENT_QUOTES, 'UTF-8'); ?>" />
    </div>
</div>
