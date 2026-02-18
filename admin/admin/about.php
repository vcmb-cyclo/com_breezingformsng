<?php
/**
 * BreezingForms - A Joomla Forms Application
 * @version 5.0.0
 * @package BreezingForms
 * @copyright (C) 2026 by XDA+GIL
 * @license Released under the terms of the GNU General Public License
 */

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

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
        );

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_breezingforms'));

            $db->setQuery($query);
            $manifestCache = (string) $db->loadResult();

            if ($manifestCache !== '') {
                $manifestData = json_decode($manifestCache, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($manifestData)) {
                    $versionInformation['version'] = (string) ($manifestData['version'] ?? '');
                    $versionInformation['creationDate'] = (string) ($manifestData['creationDate'] ?? '');
                    $versionInformation['author'] = (string) ($manifestData['author'] ?? '');
                }
            }
        } catch (Throwable $e) {
            // Fallback to local manifest files.
        }

        if ($versionInformation['version'] !== '') {
            return $versionInformation;
        }

        $manifestPaths = array(
            JPATH_ADMINISTRATOR . '/components/com_breezingforms/com_breezingforms_ng.xml',
            JPATH_ADMINISTRATOR . '/components/com_breezingforms/com_breezingforms-ng.xml',
            JPATH_ADMINISTRATOR . '/components/com_breezingforms/com_breezingforms.xml',
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

        $stripeInstalled = JPATH_ADMINISTRATOR . '/components/com_breezingforms/admin/libraries/stripe/vendor/composer/installed.json';
        $dropboxInstalled = JPATH_ADMINISTRATOR . '/components/com_breezingforms/admin/libraries/dropbox/v2/composer/installed.json';
        $tcpdfComposer = JPATH_ADMINISTRATOR . '/components/com_breezingforms/admin/libraries/tcpdf/composer.json';
        $vendorComposer = JPATH_ADMINISTRATOR . '/components/com_breezingforms/admin/libraries/vendor/composer.json';

        bf_about_collect_php_libraries_from_installed_json($indexedLibraries, $stripeInstalled);
        bf_about_collect_php_libraries_from_installed_json($indexedLibraries, $dropboxInstalled);
        bf_about_collect_php_library_from_composer_json($indexedLibraries, $tcpdfComposer);

        if (empty($indexedLibraries)) {
            bf_about_collect_php_library_from_composer_json($indexedLibraries, $vendorComposer);
        }

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
            '/Inline Form Validation Engine\s+([0-9A-Za-z\.\-]+)/i',
            '/jsTree\s+([0-9A-Za-z\.\-]+)/i',
            '/JQuery\s+([0-9A-Za-z\.\-]+)/i',
            '/version\s*[:=]\s*[\'"]([0-9A-Za-z\.\-]+)/i',
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $contents, $matches)) {
                return trim((string) ($matches[1] ?? ''));
            }
        }

        return '';
    }
}

if (!function_exists('bf_about_get_javascript_libraries')) {
    function bf_about_get_javascript_libraries()
    {
        $notAvailable = BFText::_('COM_BREEZINGFORMS_NOT_AVAILABLE');
        $bundledSource = BFText::_('COM_BREEZINGFORMS_JS_LIBRARY_SOURCE_BUNDLED');
        $basePath = JPATH_ADMINISTRATOR . '/components/com_breezingforms/admin/libraries/jquery/';
        $libraries = array();

        $candidates = array(
            array(
                'name' => 'jQuery',
                'script_path' => $basePath . 'jq.js',
                'css_path' => '',
            ),
            array(
                'name' => 'jQuery UI',
                'script_path' => $basePath . 'jq-ui.min.js',
                'css_path' => '',
            ),
            array(
                'name' => 'jsTree',
                'script_path' => $basePath . 'jtree/tree_component.min.js',
                'css_path' => $basePath . 'jtree/tree_component.css',
            ),
            array(
                'name' => 'ValidationEngine',
                'script_path' => $basePath . 'jquery.validationEngine.js',
                'css_path' => $basePath . 'validationEngine.jquery.css',
            ),
        );

        foreach ($candidates as $candidate) {
            $scriptPath = (string) ($candidate['script_path'] ?? '');

            if ($scriptPath === '' || !is_file($scriptPath)) {
                continue;
            }

            $version = bf_about_extract_version_from_file($scriptPath);
            $assets = 'JS';
            $cssPath = (string) ($candidate['css_path'] ?? '');

            if ($cssPath !== '' && is_file($cssPath)) {
                $assets = 'JS + CSS';
            }

            $libraries[] = array(
                'name' => (string) ($candidate['name'] ?? ''),
                'version' => $version !== '' ? $version : $notAvailable,
                'assets' => $assets,
                'source' => $bundledSource,
            );
        }

        usort($libraries, function ($a, $b) {
            return strcasecmp((string) $a['name'], (string) $b['name']);
        });

        return $libraries;
    }
}

$versionInformation = bf_about_get_version_information();
$phpLibraries = bf_about_get_php_libraries();
$javascriptLibraries = bf_about_get_javascript_libraries();
$notAvailable = BFText::_('COM_BREEZINGFORMS_NOT_AVAILABLE');

$versionValue = $versionInformation['version'] !== '' ? $versionInformation['version'] : $notAvailable;
$creationDateValue = $versionInformation['creationDate'] !== '' ? $versionInformation['creationDate'] : $notAvailable;
$authorValue = $versionInformation['author'] !== '' ? $versionInformation['author'] : $notAvailable;
$aboutDescription = (string) BFText::_('COM_BREEZINGFORMS_ABOUT_DESC');
$aboutDescription = str_replace(
    '<strong>BreezingForms</strong>',
    '<strong>BreezingForms NG</strong>',
    $aboutDescription
);
$aboutDescription = str_replace(
    'GPL-2.0+',
    '<a href="https://www.gnu.org/licenses/old-licenses/gpl-2.0.html" target="_blank" rel="noopener noreferrer">GPL-2.0+</a>',
    $aboutDescription
);
?>
<style>
    .bf-about-intro {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .bf-about-intro-media {
        flex: 0 0 auto;
    }
    .bf-about-intro-content {
        flex: 1 1 auto;
        min-width: 0;
    }
    .bf-about-intro-content p {
        margin: 0;
        padding: 0;
        text-align: left;
    }
    @media (max-width: 767.98px) {
        .bf-about-intro {
            flex-wrap: wrap;
        }
    }
    .bf-about-version-card {
        background:
            radial-gradient(circle at 100% 0%, rgba(13, 110, 253, .10), transparent 48%),
            radial-gradient(circle at 0% 100%, rgba(25, 135, 84, .09), transparent 44%),
            linear-gradient(140deg, #f8fafc 0%, #ffffff 72%);
        border: 1px solid #dbe4ee;
        border-radius: 1rem;
        overflow: hidden;
    }
    .bf-about-version-header {
        border-bottom: 1px dashed #d2dbe6;
        padding-bottom: .75rem;
    }
    .bf-about-version-title {
        color: #172b4d;
        font-weight: 700;
        letter-spacing: .01em;
    }
    .bf-about-version-badge {
        background-color: #172b4d;
        color: #ffffff;
        border-radius: 999px;
        font-size: .72rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        padding: .35rem .65rem;
    }
    .bf-about-version-tile {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: .35rem;
        height: 100%;
        border: 1px solid #dce3eb;
        border-radius: .9rem;
        background: linear-gradient(180deg, #ffffff 0%, #fcfdff 100%);
        padding: 1.05rem 1.05rem .95rem;
        box-shadow: 0 .5rem 1rem rgba(15, 23, 42, .06);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .bf-about-version-tile::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: .23rem;
        border-radius: .9rem .9rem 0 0;
        background: var(--bf-accent-color, #0d6efd);
    }
    .bf-about-version-tile:hover {
        transform: translateY(-2px);
        box-shadow: 0 .65rem 1.25rem rgba(15, 23, 42, .1);
    }
    .bf-about-version-tile--version {
        --bf-accent-color: #0d6efd;
    }
    .bf-about-version-tile--date {
        --bf-accent-color: #198754;
    }
    .bf-about-version-tile--author {
        --bf-accent-color: #fd7e14;
    }
    .bf-about-version-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .04em;
        background-color: #e8f1ff;
        color: #0d6efd;
    }
    .bf-about-version-tile--date .bf-about-version-icon {
        background-color: #e7f6ed;
        color: #198754;
    }
    .bf-about-version-tile--author .bf-about-version-icon {
        background-color: #fff1e8;
        color: #fd7e14;
    }
    .bf-about-version-label {
        margin: .15rem 0 0;
        color: #6c757d;
        font-size: .74rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .bf-about-version-value {
        margin: 0;
        color: #1b2a41;
        font-size: 1.22rem;
        font-weight: 700;
        line-height: 1.25;
        word-break: break-word;
    }
</style>

<form action="index.php?option=com_breezingforms&act=about" method="post" name="adminForm" id="adminForm">
    <div class="bf-about-intro mt-3 mb-3">
        <div class="bf-about-intro-media">
            <img
                src="<?php echo htmlspecialchars(Uri::root(true) . '/administrator/components/com_breezingforms/assets/images/bf_logo.png', ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars(BFText::_('COM_BREEZINGFORMS_ABOUT'), ENT_QUOTES, 'UTF-8'); ?>"
                class="img-fluid"
                style="max-width: 150px; height: auto;"
                loading="lazy"
            />
        </div>
        <div class="bf-about-intro-content">
            <p class="mb-0">
                <?php echo $aboutDescription; ?>
                <a href="https://breezingforms.vcmb.fr" target="_blank" rel="noopener noreferrer">VCMB migration</a>
            </p>
        </div>
    </div>

    <div class="card mt-3 bf-about-version-card">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 bf-about-version-header">
                <h3 class="h5 mb-0 bf-about-version-title"><?php echo BFText::_('COM_BREEZINGFORMS_VERSION_INFORMATION'); ?></h3>
                <span class="bf-about-version-badge">BreezingForms</span>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="bf-about-version-tile bf-about-version-tile--version">
                        <span class="bf-about-version-icon" aria-hidden="true">VER</span>
                        <p class="bf-about-version-label"><?php echo BFText::_('COM_BREEZINGFORMS_VERSION_LABEL'); ?></p>
                        <p class="bf-about-version-value"><?php echo htmlspecialchars((string) $versionValue, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="bf-about-version-tile bf-about-version-tile--date">
                        <span class="bf-about-version-icon" aria-hidden="true">DATE</span>
                        <p class="bf-about-version-label"><?php echo BFText::_('COM_BREEZINGFORMS_CREATION_DATE_LABEL'); ?></p>
                        <p class="bf-about-version-value"><?php echo htmlspecialchars((string) $creationDateValue, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="bf-about-version-tile bf-about-version-tile--author">
                        <span class="bf-about-version-icon" aria-hidden="true">DEV</span>
                        <p class="bf-about-version-label"><?php echo BFText::_('COM_BREEZINGFORMS_AUTHOR_LABEL'); ?></p>
                        <p class="bf-about-version-value"><?php echo htmlspecialchars((string) $authorValue, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h3 class="h6 card-title mb-3"><?php echo BFText::_('COM_BREEZINGFORMS_PHP_LIBRARIES'); ?></h3>
            <?php if (empty($phpLibraries)) : ?>
                <div class="alert alert-info mb-0">
                    <?php echo BFText::_('COM_BREEZINGFORMS_PHP_LIBRARIES_NOT_AVAILABLE'); ?>
                </div>
            <?php else : ?>
                <p class="text-muted small">
                    <?php echo sprintf(BFText::_('COM_BREEZINGFORMS_PHP_LIBRARIES_COUNT'), count($phpLibraries)); ?>
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th scope="col"><?php echo BFText::_('COM_BREEZINGFORMS_PHP_LIBRARY'); ?></th>
                            <th scope="col"><?php echo BFText::_('COM_BREEZINGFORMS_PHP_LIBRARY_VERSION'); ?></th>
                            <th scope="col"><?php echo BFText::_('COM_BREEZINGFORMS_PHP_LIBRARY_SCOPE'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($phpLibraries as $library) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($library['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) (($library['version'] ?? '') !== '' ? $library['version'] : $notAvailable), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo BFText::_(!empty($library['is_dev']) ? 'COM_BREEZINGFORMS_PHP_LIBRARY_SCOPE_DEV' : 'COM_BREEZINGFORMS_PHP_LIBRARY_SCOPE_RUNTIME'); ?></td>
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
            <h3 class="h6 card-title mb-3"><?php echo BFText::_('COM_BREEZINGFORMS_JS_LIBRARIES'); ?></h3>
            <?php if (empty($javascriptLibraries)) : ?>
                <div class="alert alert-info mb-0">
                    <?php echo BFText::_('COM_BREEZINGFORMS_JS_LIBRARIES_NOT_AVAILABLE'); ?>
                </div>
            <?php else : ?>
                <p class="text-muted small">
                    <?php echo sprintf(BFText::_('COM_BREEZINGFORMS_JS_LIBRARIES_COUNT'), count($javascriptLibraries)); ?>
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th scope="col"><?php echo BFText::_('COM_BREEZINGFORMS_JS_LIBRARY'); ?></th>
                            <th scope="col"><?php echo BFText::_('COM_BREEZINGFORMS_JS_LIBRARY_VERSION'); ?></th>
                            <th scope="col"><?php echo BFText::_('COM_BREEZINGFORMS_JS_LIBRARY_ASSETS'); ?></th>
                            <th scope="col"><?php echo BFText::_('COM_BREEZINGFORMS_JS_LIBRARY_SOURCE'); ?></th>
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

    <input type="hidden" name="option" value="com_breezingforms" />
    <input type="hidden" name="act" value="about" />
    <input type="hidden" name="task" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
