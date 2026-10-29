<?php

/**
 * @package   BreezingFormsNG
 * @author    Markus Bopp
 * @link      http://breezings.vcmb.fr
 * @copyright Copyright (C) 2008-2020 Markus Bopp
 * @copyright Copyright (C) 2024-2026 XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

use Joomla\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Table\Menu;

Log::addLogger(
    [
        'text_file' => 'breezingforms_install.log',
        'text_entry_format' => '{DATETIME}\t{PRIORITY}\t{MESSAGE}',
        'text_file_path'     => JPATH_ADMINISTRATOR . '/logs'
    ],
    Log::ALL,
    ['com_breezingformsng.install']
);


// Logs de démarrage
Log::add('[OK] BreezingformsNG installation/update started.', Log::INFO, 'com_breezingformsng.install');
Log::add('PHP Version: ' . PHP_VERSION . '.', Log::INFO, 'com_breezingformsng.install');
Log::add('Joomla Version : ' . JVERSION . '.', Log::INFO, 'com_breezingformsng.install');
Log::add('User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'CLI') . '.', Log::INFO, 'com_breezingformsng.install');

class com_breezingformsngInstallerScript implements InstallerScriptInterface
{
    private const TARGET_COMPONENT = 'com_breezingformsng';
    private const MINIMUM_PHP = '8.3';
    private const MINIMUM_JOOMLA = '6.0';
    // SHA-256 of every shipped version of the generic PDF templates that
    // media/breezingforms/pdftpl no longer uses, line endings normalised to
    // LF and trailing whitespace removed. Both footers always had the same
    // content.
    private const STOCK_GENERIC_PDF_FOOTERS = [
        '5428c72dac8015058c5a745f9044afb4294c6acb2e0d8e17ab21e399e98de82a',
        '62427a65323562fa77a23ed0de268736886bc7d3f47f14a366190799787192f0',
        '9ee06e901666534231c5c9e5dafca18fa311a1bb67b51ebeb4d6b0cec77e2505',
        'ad53e551963ddcaf8eef6dc5cfdd22dcdfa0bfbf931a16767caf379222c7d238',
        'c42aac9997ce93b8472df2ad7a2029c751dfb904a63d0a00d417caa465905e9d',
        'fa11de00d14e58218ad5c904cb248f3d459462d031a774ce101e671a5630a0fa',
    ];
    private const STOCK_GENERIC_PDF_TEMPLATES = [
        'export_header_pdf.php' => [
            '67771e09be2d09e625db4f11d0d6d6ce90e15d2fbc52635c41ad220d3532c990',
            'a28df7b87a9bfd7dc80e839ea29bf5619927c6f6386bc2f4a22b26db5e3fdbe7',
            'be8e0dedbed4fec232fc77fdc2199c58c8efdc247668ab312168c60b081286ed',
            'ee1c9a42d6805274fed6ddbb07f108eb2f9ea389048c0f4e04ff5d2eab5a8d45',
        ],
        'export_footer_pdf.php' => self::STOCK_GENERIC_PDF_FOOTERS,
        'pdf_attachment_header.php' => [
            '15cc955a0e89adc30bde85dbb96cfac49ff9db275907997c6956e66cb34fe3a4',
            '5b8ec60953da69a17c2c61d27f67113b6e6649b8599529c65f62f93c31fcdd4a',
            '5e64d445a8e3d3a67d7b301e71f59e0239e26b1bc6cf2daeb73b20ba9ee33b1f',
            '8ef950acb2bf26760d3c62836cee24c029d1e73852c39833a8ebfd9f1575f2bb',
        ],
        'pdf_attachment_footer.php' => self::STOCK_GENERIC_PDF_FOOTERS,
    ];
    private const LEGACY_COMPONENT_ALIASES = [
        'breezingforms',
        'com_breezingforms',
        'com_breezingformsng',
    ];

    /** @var float */
    private $installStartedAt = 0.0;
    /** @var string */
    private $incomingPackageVersion = '';
    /** @var string */
    private $currentInstalledVersion = '0.0.0';

    /** Schema version of the installed component, read before the SQL updates run. */
    private string $schemaVersionBeforeUpdate = '';
    /** @var bool */
    private $utf8mb4SupportChecked = false;
    /** @var bool */
    private $utf8mb4Supported = false;
    /** @var string */
    private $utf8mb4Collation = 'utf8mb4_general_ci';
    /** @var bool */
    private $utf8mb4CheckPerformed = false;
    /** @var bool */
    private $utf8mb4CapabilityAnnounced = false;
    /** @var bool */
    private $criticalFailureDetected = false;
    /** @var list<string> */
    private $criticalFailureMessages = [];
    /** @var bool */
    private $logRotationChecked = false;

    public function __construct()
    {
        $this->installStartedAt = microtime(true);
        $this->applyJoomlaTimezoneForLogging();
        $this->currentInstalledVersion = $this->getCurrentInstalledVersion();
    }

    /**
     * Sets PHP's default timezone to the site's configured one, once, so
     * that Joomla's own Log::add() calls (including the bootstrap ones at
     * the top of this file, executed before this class even exists) and
     * any other date()-based code in this request use the correct offset,
     * not just this script's own custom log line.
     */
    private function applyJoomlaTimezoneForLogging(): void
    {
        try {
            $offset = (string) Factory::getApplication()->get('offset', 'UTC');
            new \DateTimeZone($offset !== '' ? $offset : 'UTC');
            date_default_timezone_set($offset !== '' ? $offset : 'UTC');
        } catch (\Throwable) {
            // Leave PHP's default timezone untouched if it can't be resolved.
        }
    }

    /**
     * Translated installer message with an untranslated-fallback sprintf,
     * matching this project's rule of routing user-facing strings through
     * translation keys even in the installer script.
     *
     * @param string|int|float ...$args
     */
    private function installerText(string $key, string $fallback, ...$args): string
    {
        try {
            $translated = $args === [] ? Text::_($key) : Text::sprintf($key, ...$args);
        } catch (\Throwable) {
            $translated = $key;
        }

        return $translated === $key ? sprintf($fallback, ...$args) : $translated;
    }

    private function checkRequirements(): bool
    {
        if (!version_compare(PHP_VERSION, self::MINIMUM_PHP, '>=')) {
            $this->log(
                $this->installerText(
                    'COM_BREEZINGFORMSNG_INSTALLER_ERROR_PHP_REQUIRES',
                    'PHP %s is too old. Requires PHP %s or later.',
                    PHP_VERSION,
                    self::MINIMUM_PHP
                ),
                Log::ERROR
            );

            return false;
        }

        if (defined('JVERSION') && !version_compare(JVERSION, self::MINIMUM_JOOMLA, '>=')) {
            $this->log(
                $this->installerText(
                    'COM_BREEZINGFORMSNG_INSTALLER_ERROR_JOOMLA_REQUIRES',
                    'Joomla %s is too old. Requires Joomla %s or later.',
                    JVERSION,
                    self::MINIMUM_JOOMLA
                ),
                Log::ERROR
            );

            return false;
        }

        return true;
    }

    private function checkInstalledFilePermissionsBeforeCopy(): bool
    {
        $paths = $this->getInstalledWritableCheckPaths();
        $blocked = [];

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            $blocked = array_merge($blocked, $this->collectNonWritablePaths($path, 25 - count($blocked)));

            if (count($blocked) >= 25) {
                break;
            }
        }

        if ($blocked === []) {
            $this->log('Existing BreezingForms NG files are writable for this update.');

            return true;
        }

        $sample = array_slice($blocked, 0, 10);
        $lines = array_map(
            static fn(string $path): string => '<li><code>'
                . htmlspecialchars($path, ENT_QUOTES, 'UTF-8')
                . '</code></li>',
            $sample
        );
        $remaining = max(0, count($blocked) - count($sample));

        if ($remaining > 0) {
            $lines[] = '<li>... +' . $remaining . ' more</li>';
        }

        $this->log(
            'BreezingForms NG update cannot copy files because some installed files or directories'
            . ' are not writable by Joomla.'
            . '<br>Fix ownership/permissions before reinstalling.'
            . '<br>Examples of blocked paths:<ul>' . implode('', $lines) . '</ul>',
            Log::ERROR
        );

        return false;
    }

    /**
     * @return list<string>
     */
    private function getInstalledWritableCheckPaths(): array
    {
        return [
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng',
            JPATH_ROOT . '/components/com_breezingformsng',
            JPATH_ROOT . '/media/com_breezingformsng',
        ];
    }

    /**
     * @return list<string>
     */
    private function collectNonWritablePaths(string $path, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        if (!is_writable($path)) {
            return [$path];
        }

        if (!is_dir($path)) {
            return [];
        }

        $blocked = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $itemPath = $item instanceof \SplFileInfo ? $item->getPathname() : (string) $item;

                if ($itemPath !== '' && !is_writable($itemPath)) {
                    $blocked[] = $itemPath;

                    if (count($blocked) >= $limit) {
                        break;
                    }
                }
            }
        } catch (\Throwable) {
            if (!is_readable($path)) {
                $blocked[] = $path;
            }
        }

        return $blocked;
    }

    private function detectUtf8mb4Support(): void
    {
        if ($this->utf8mb4SupportChecked) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $db->setQuery("SHOW CHARACTER SET LIKE 'utf8mb4'");
            $charset = $db->loadAssoc();
            $this->utf8mb4Supported = is_array($charset) && !empty($charset);

            if ($this->utf8mb4Supported) {
                $db->setQuery("SHOW COLLATION WHERE Charset = 'utf8mb4'");
                $collations = (array) $db->loadAssocList();
                $available = [];

                foreach ($collations as $collation) {
                    if (!empty($collation['Collation'])) {
                        $available[] = $collation['Collation'];
                    }
                }

                foreach (['utf8mb4_unicode_ci', 'utf8mb4_general_ci'] as $preferredCollation) {
                    if (in_array($preferredCollation, $available, true)) {
                        $this->utf8mb4Collation = $preferredCollation;
                        break;
                    }
                }

                if (!in_array($this->utf8mb4Collation, $available, true) && !empty($available)) {
                    $this->utf8mb4Collation = $available[0];
                }
            }
        } catch (\Throwable $e) {
            $this->utf8mb4Supported = false;
            $this->log('Unable to detect utf8mb4 support on this database server: ' . $e->getMessage(), Log::WARNING);
        }

        $this->utf8mb4SupportChecked = true;
    }

    private function announceUtf8mb4Capability(): void
    {
        if ($this->utf8mb4CapabilityAnnounced) {
            return;
        }

        $this->detectUtf8mb4Support();

        if ($this->utf8mb4Supported) {
            $message = 'utf8mb4 support detected on the database server.'
                . ' BreezingForms tables will be checked and converted when needed.';
            $this->announce($message . ' Target collation: ' . $this->utf8mb4Collation . '.', 'message', Log::INFO);
        } else {
            $message = 'utf8mb4 is not available on the database server.'
                . ' BreezingForms cannot auto-convert its text columns to utf8mb4 on this installation.';
            $this->announce($message, 'warning', Log::WARNING);
        }

        $this->utf8mb4CapabilityAnnounced = true;
    }

    /**
     * @return list<string>
     */
    private function getBreezingFormsTables(DatabaseInterface $db): array
    {
        $prefix = $db->getPrefix();
        $tables = [];

        foreach ($db->getTableList() as $tableName) {
            if (strpos($tableName, $prefix . 'facileforms_') === 0) {
                $tables[] = $tableName;
            }
        }

        sort($tables);

        return $tables;
    }

    /**
     * @param list<string> $tables
     * @return array<string, list<string>>
     */
    private function getUtf8mb4NonCompliantTables(DatabaseInterface $db, array $tables): array
    {
        if (empty($tables)) {
            return [];
        }

        $quotedTables = array_map([$db, 'quote'], $tables);

        $tableQuery = $db->createQuery()
            ->select([
                $db->quoteName('TABLE_NAME'),
                $db->quoteName('TABLE_COLLATION'),
            ])
            ->from($db->quoteName('information_schema.TABLES'))
            ->where($db->quoteName('TABLE_SCHEMA') . ' = DATABASE()')
            ->where($db->quoteName('TABLE_NAME') . ' IN (' . implode(',', $quotedTables) . ')');

        $db->setQuery($tableQuery);
        $tableRows = (array) $db->loadAssocList();

        $columnQuery = $db->createQuery()
            ->select([
                $db->quoteName('TABLE_NAME'),
                $db->quoteName('COLUMN_NAME'),
                $db->quoteName('CHARACTER_SET_NAME'),
                $db->quoteName('COLLATION_NAME'),
            ])
            ->from($db->quoteName('information_schema.COLUMNS'))
            ->where($db->quoteName('TABLE_SCHEMA') . ' = DATABASE()')
            ->where($db->quoteName('TABLE_NAME') . ' IN (' . implode(',', $quotedTables) . ')')
            ->where($db->quoteName('CHARACTER_SET_NAME') . ' IS NOT NULL');

        $db->setQuery($columnQuery);
        $columnRows = (array) $db->loadAssocList();

        $issues = [];

        foreach ($tableRows as $row) {
            $tableName = $row['TABLE_NAME'] ?? '';
            $tableCollation = (string) ($row['TABLE_COLLATION'] ?? '');

            if ($tableName !== '' && strpos($tableCollation, 'utf8mb4_') !== 0) {
                $issues[$tableName][] = 'table collation=' . ($tableCollation !== '' ? $tableCollation : 'none');
            }
        }

        foreach ($columnRows as $row) {
            $tableName = $row['TABLE_NAME'] ?? '';
            $columnName = $row['COLUMN_NAME'] ?? '';
            $charset = (string) ($row['CHARACTER_SET_NAME'] ?? '');
            $collation = (string) ($row['COLLATION_NAME'] ?? '');

            if ($tableName === '' || $columnName === '') {
                continue;
            }

            if ($charset !== 'utf8mb4' || strpos($collation, 'utf8mb4_') !== 0) {
                $issues[$tableName][] = $columnName
                    . '=' . ($charset !== '' ? $charset : 'none')
                    . '/' . ($collation !== '' ? $collation : 'none');
            }
        }

        return $issues;
    }

    /**
     * Legacy BreezingForms installs can still carry MyISAM tables (1000-byte
     * key limit) or InnoDB tables using the old Antelope row format
     * (767-byte limit). A blind CONVERT TO CHARACTER SET utf8mb4 then fails
     * on any single-column index over a varchar(255) column, since utf8mb4
     * needs up to 4 bytes/char (255 * 4 = 1020 bytes). Move the table to
     * InnoDB/DYNAMIC first, then shrink oversized index prefixes to 191
     * characters (191 * 4 = 764 bytes), which fits under every limit above.
     */
    private function prepareTableForUtf8mb4Conversion(DatabaseInterface $db, string $tableName): void
    {
        $db->setQuery(
            $db->createQuery()
                ->select([$db->quoteName('ENGINE'), $db->quoteName('ROW_FORMAT')])
                ->from($db->quoteName('information_schema.TABLES'))
                ->where($db->quoteName('TABLE_SCHEMA') . ' = DATABASE()')
                ->where($db->quoteName('TABLE_NAME') . ' = ' . $db->quote($tableName))
        );
        $tableInfo = (array) $db->loadAssoc();
        $engine = strtoupper((string) ($tableInfo['ENGINE'] ?? ''));
        $rowFormat = strtoupper((string) ($tableInfo['ROW_FORMAT'] ?? ''));

        if ($engine !== 'INNODB' || !in_array($rowFormat, ['DYNAMIC', 'COMPRESSED'], true)) {
            $db->setQuery(
                'ALTER TABLE ' . $db->quoteName($tableName) . ' ENGINE=InnoDB, ROW_FORMAT=DYNAMIC'
            )->execute();
            $this->log('Moved table ' . $tableName . ' to ENGINE=InnoDB ROW_FORMAT=DYNAMIC before utf8mb4 conversion.');
        }

        $this->shrinkOversizedIndexes($db, $tableName);
    }

    private function shrinkOversizedIndexes(DatabaseInterface $db, string $tableName): void
    {
        $db->setQuery(
            $db->createQuery()
                ->select([
                    $db->quoteName('s.INDEX_NAME'),
                    $db->quoteName('s.SEQ_IN_INDEX'),
                    $db->quoteName('s.COLUMN_NAME'),
                    $db->quoteName('s.NON_UNIQUE'),
                    $db->quoteName('s.SUB_PART'),
                    $db->quoteName('c.CHARACTER_MAXIMUM_LENGTH'),
                ])
                ->from($db->quoteName('information_schema.STATISTICS', 's'))
                ->join(
                    'INNER',
                    $db->quoteName('information_schema.COLUMNS', 'c') .
                    ' ON ' . $db->quoteName('c.TABLE_SCHEMA') . ' = ' . $db->quoteName('s.TABLE_SCHEMA') .
                    ' AND ' . $db->quoteName('c.TABLE_NAME') . ' = ' . $db->quoteName('s.TABLE_NAME') .
                    ' AND ' . $db->quoteName('c.COLUMN_NAME') . ' = ' . $db->quoteName('s.COLUMN_NAME')
                )
                ->where($db->quoteName('s.TABLE_SCHEMA') . ' = DATABASE()')
                ->where($db->quoteName('s.TABLE_NAME') . ' = ' . $db->quote($tableName))
                ->where($db->quoteName('s.INDEX_NAME') . ' != ' . $db->quote('PRIMARY'))
                ->where($db->quoteName('s.INDEX_TYPE') . ' != ' . $db->quote('FULLTEXT'))
                ->order([$db->quoteName('s.INDEX_NAME'), $db->quoteName('s.SEQ_IN_INDEX')])
        );

        $rows = (array) $db->loadAssocList();
        $indexes = [];

        foreach ($rows as $row) {
            $indexName = (string) ($row['INDEX_NAME'] ?? '');
            $columnName = (string) ($row['COLUMN_NAME'] ?? '');

            if ($indexName === '' || $columnName === '') {
                continue;
            }

            $indexes[$indexName]['unique'] = ((int) ($row['NON_UNIQUE'] ?? 1)) === 0;
            $indexes[$indexName]['columns'][] = [
                'name' => $columnName,
                'subPart' => $row['SUB_PART'] !== null ? (int) $row['SUB_PART'] : null,
                'maxLength' => $row['CHARACTER_MAXIMUM_LENGTH'] !== null
                    ? (int) $row['CHARACTER_MAXIMUM_LENGTH']
                    : null,
            ];
        }

        foreach ($indexes as $indexName => $index) {
            $needsShrinking = false;
            $columnDefinitions = [];

            foreach ($index['columns'] as $column) {
                if ($column['subPart'] === null && $column['maxLength'] !== null && $column['maxLength'] > 191) {
                    $needsShrinking = true;
                    $columnDefinitions[] = $db->quoteName($column['name']) . '(191)';
                } elseif ($column['subPart'] !== null) {
                    $columnDefinitions[] = $db->quoteName($column['name']) . '(' . $column['subPart'] . ')';
                } else {
                    $columnDefinitions[] = $db->quoteName($column['name']);
                }
            }

            if (!$needsShrinking) {
                continue;
            }

            $keyword = $index['unique'] ? 'UNIQUE KEY' : 'KEY';

            // A single multi-clause ALTER TABLE (rather than a separate DROP
            // INDEX followed by its own ADD KEY) is one DDL operation: if the
            // ADD half fails (e.g. a future UNIQUE KEY whose 191-char prefix
            // collides between existing rows), the whole statement is
            // rejected and the index is never dropped in the first place.
            $db->setQuery(
                'ALTER TABLE ' . $db->quoteName($tableName)
                . ' DROP INDEX ' . $db->quoteName($indexName)
                . ', ADD ' . $keyword . ' ' . $db->quoteName($indexName)
                . ' (' . implode(',', $columnDefinitions) . ')'
            )->execute();

            $this->log(
                'Shrunk index ' . $indexName . ' on ' . $tableName .
                ' to fit utf8mb4 key length limits (191-character prefix on oversized text columns).'
            );
        }
    }

    private function ensureUtf8mb4Columns(): void
    {
        if ($this->utf8mb4CheckPerformed) {
            return;
        }

        $this->detectUtf8mb4Support();
        $this->utf8mb4CheckPerformed = true;

        if (!$this->utf8mb4Supported) {
            return;
        }

        // ENGINE=InnoDB conversion rebuilds the whole table and can outlast the
        // default PHP execution time limit on large record tables; a timeout here
        // is a fatal error that bypasses the per-table try/catch below.
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $tables = $this->getBreezingFormsTables($db);

        if (empty($tables)) {
            $this->log('No BreezingForms NG tables found yet for utf8mb4 verification.', Log::INFO);
            return;
        }

        $issues = $this->getUtf8mb4NonCompliantTables($db, $tables);

        if (empty($issues)) {
            $message = 'BreezingForms NG utf8mb4 check: all existing component tables already use utf8mb4.';
            $this->announce($message, 'message', Log::INFO);
            return;
        }

        $converted = [];
        $failed = [];

        foreach ($issues as $tableName => $tableIssues) {
            try {
                $this->prepareTableForUtf8mb4Conversion($db, $tableName);

                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($tableName) .
                    ' CONVERT TO CHARACTER SET utf8mb4 COLLATE ' . $this->utf8mb4Collation
                )->execute();

                $converted[] = $tableName;
                $this->log(
                    'Converted table ' . $tableName . ' to utf8mb4/' . $this->utf8mb4Collation .
                    '. Previous issues: ' . implode(', ', $tableIssues)
                );
            } catch (\Throwable $e) {
                $failed[] = $tableName;
                $message = 'Failed to convert table ' . $tableName . ' to utf8mb4: ' . $e->getMessage();
                $this->announce($message, 'error', Log::ERROR);
            }
        }

        if (!empty($converted)) {
            $message = 'BreezingForms utf8mb4 correction applied on ' . count($converted)
                . ' table(s): ' . implode(', ', $converted) . '.';
            $this->announce($message, 'warning', Log::INFO);
        }

        if (empty($failed)) {
            $message = 'BreezingForms utf8mb4 verification completed successfully.';
            $this->announce($message, 'message', Log::INFO);
        }
    }

    /**
     * Free-text form/element content authored before the NG migration -
     * custom "thank you" redirect URLs, PayPal/Stripe
     * return URLs, custom init/action/validation code - can hardcode
     * `option=com_breezingforms`, the pre-NG component name. That
     * component no longer exists, so any such stored link now 404s with
     * "Composant introuvable". Rewrite it to `com_breezingformsng`
     * wherever it appears, without touching content that already says
     * `com_breezingformsng` (a blind str_replace('com_breezingforms',
     * 'com_breezingformsng', ...) would double-migrate that to
     * "com_breezingformsngng").
     */
    private function migrateLegacyComponentOptionReferences(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $prefix = $db->getPrefix();
        $tables = self::getTableFields($db->getTableList());

        $elementsTable = $prefix . 'facileforms_elements';
        $formsTable = $prefix . 'facileforms_forms';
        $scriptsTable = $prefix . 'facileforms_scripts';
        $piecesTable = $prefix . 'facileforms_pieces';

        if (isset($tables[$elementsTable])) {
            $this->migrateLegacyOptionInPlainColumns(
                $db,
                $elementsTable,
                ['data1', 'data2', 'data3', 'script1code', 'script2code', 'script3code', 'script3msg', 'mailbackfile']
            );
        }

        if (isset($tables[$formsTable])) {
            $this->migrateLegacyOptionInFormTrees($db, $formsTable);
            // template_areas is the *compiled*, plain-JSON copy of the QuickMode tree
            // that site-side callbacks (e.g. StripeCallback::confirm() via
            // PaymentFormLoader::decodeAreas()) actually read from at runtime -
            // template_code (handled above) only feeds the admin QuickMode editor.
            // template_code_processed is the classic (non-QuickMode) builder's
            // equivalent compiled copy. script*code/piece*code are the form-level
            // custom init/action/validation/piece code, executed at runtime the
            // same way as their per-element counterparts above.
            $this->migrateLegacyOptionInPlainColumns(
                $db,
                $formsTable,
                [
                    'template_areas', 'template_code_processed',
                    'script1code', 'script2code',
                    'piece1code', 'piece2code', 'piece3code', 'piece4code',
                ]
            );
        }

        // Scripts and Pieces are reusable code snippets, independent of any one
        // form, executed at runtime wherever a form references them - the same
        // kind of free-text content that can hardcode the pre-NG option value.
        if (isset($tables[$scriptsTable])) {
            $this->migrateLegacyOptionInPlainColumns($db, $scriptsTable, ['code']);
        }

        if (isset($tables[$piecesTable])) {
            $this->migrateLegacyOptionInPlainColumns($db, $piecesTable, ['code']);
        }
    }

    /**
     * Rewrites legacy option=com_breezingforms references found in a set of
     * plain-text columns (i.e. not base64-encoded) of the given table.
     *
     * @param list<string> $columns
     */
    private function migrateLegacyOptionInPlainColumns(DatabaseInterface $db, string $table, array $columns): void
    {
        $query = $db->createQuery()
            ->select(array_merge([$db->quoteName('id')], array_map([$db, 'quoteName'], $columns)))
            ->from($db->quoteName($table));
        $rows = (array) $db->setQuery($query)->loadAssocList();

        $updatedRows = 0;

        foreach ($rows as $row) {
            $changes = [];

            foreach ($columns as $column) {
                $original = (string) ($row[$column] ?? '');

                if ($original === '' || strpos($original, 'com_breezingforms') === false) {
                    continue;
                }

                $rewritten = preg_replace('/com_breezingforms(?!ng)/', 'com_breezingformsng', $original);

                if ($rewritten !== $original) {
                    $changes[$column] = $rewritten;
                }
            }

            if ($changes === []) {
                continue;
            }

            $update = $db->createQuery()->update($db->quoteName($table));

            foreach ($changes as $column => $value) {
                $update->set($db->quoteName($column) . ' = :' . $column)
                    ->bind(':' . $column, $changes[$column], ParameterType::STRING);
            }

            $id = (int) $row['id'];
            $update->where($db->quoteName('id') . ' = :id')->bind(':id', $id, ParameterType::INTEGER);
            $db->setQuery($update)->execute();
            $updatedRows++;
        }

        if ($updatedRows > 0) {
            $this->log(
                "Rewrote legacy option=com_breezingforms references in {$updatedRows} {$table} row(s)."
            );
        }
    }

    private function migrateLegacyOptionInFormTrees(DatabaseInterface $db, string $table): void
    {
        $query = $db->createQuery()
            ->select([$db->quoteName('id'), $db->quoteName('template_code')])
            ->from($db->quoteName($table));
        $rows = (array) $db->setQuery($query)->loadAssocList();

        $updatedRows = 0;

        foreach ($rows as $row) {
            $encoded = (string) ($row['template_code'] ?? '');

            if ($encoded === '') {
                continue;
            }

            $decoded = base64_decode($encoded, true);

            if ($decoded === false || strpos($decoded, 'com_breezingforms') === false) {
                continue;
            }

            $rewritten = preg_replace('/com_breezingforms(?!ng)/', 'com_breezingformsng', $decoded);

            // On a PCRE error, keep the stored form instead of writing it empty.
            if ($rewritten === null || $rewritten === $decoded) {
                continue;
            }

            $newEncoded = base64_encode($rewritten);
            $id = (int) $row['id'];
            $update = $db->createQuery()
                ->update($db->quoteName($table))
                ->set($db->quoteName('template_code') . ' = :templateCode')
                ->bind(':templateCode', $newEncoded, ParameterType::STRING)
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);
            $db->setQuery($update)->execute();
            $updatedRows++;
        }

        if ($updatedRows > 0) {
            $this->log(
                "Rewrote legacy option=com_breezingforms references in "
                . "{$updatedRows} facileforms_forms.template_code row(s)."
            );
        }
    }

    private function getTextCollationClause(): string
    {
        $this->detectUtf8mb4Support();

        if (!$this->utf8mb4Supported) {
            return '';
        }

        return ' COLLATE ' . $this->utf8mb4Collation;
    }

    private function migrateLegacyConfig(): void
    {
        $admPath = JPATH_ADMINISTRATOR . '/components/com_breezingformsng';

        require_once $admPath . '/src/Model/ConfigModel.php';

        try {
            $migrated = (new \Vcmb\Component\BreezingformsNG\Administrator\Model\ConfigModel())->migrateFromLegacy();
        } catch (\Throwable $e) {
            $this->log('Legacy config migration failed: ' . $e->getMessage(), Log::WARNING);
            return;
        }

        if ($migrated !== []) {
            $this->log('Legacy config migrated to component params: ' . implode(', ', $migrated));
        }
    }

    private function importStandardLibrary(): void
    {
        $admPath = JPATH_ADMINISTRATOR . '/components/com_breezingformsng';
        $xmlFile = $admPath . '/packages/stdlib.english.xml';

        if (!File::exists($xmlFile)) {
            $this->log("Standard library package not found: {$xmlFile}", Log::WARNING);
            return;
        }

        require_once $admPath . '/src/Model/ImportModel.php';

        $importer = new \Vcmb\Component\BreezingformsNG\Administrator\Model\ImportModel();
        $importer->reinstallOnlyIfChanged = true;

        try {
            $importer->import($xmlFile, $this->packageItemModel('Script'), $this->packageItemModel('Piece'));
        } catch (\Throwable $e) {
            $this->log('Standard library import failed. ' . $e->getMessage(), Log::ERROR);
            $this->announce('BreezingForms standard pieces import failed: ' . $e->getMessage(), 'error', Log::ERROR);
            return;
        }

        $this->log(
            'Standard library imported successfully: ' .
            count($importer->scripts) . ' script(s), ' .
            count($importer->pieces) . ' piece(s).'
        );

        $this->announceImportedLibraryChanges($importer);
    }

    private function announceImportedLibraryChanges(object $importer): void
    {
        $createdScripts = $this->normalizeImportedItemNames($importer->createdScripts ?? []);
        $createdPieces = $this->normalizeImportedItemNames($importer->createdPieces ?? []);
        $updatedScripts = $this->normalizeImportedItemNames($importer->updatedScripts ?? []);
        $updatedPieces = $this->normalizeImportedItemNames($importer->updatedPieces ?? []);

        if (empty($createdScripts) && empty($createdPieces) && empty($updatedScripts) && empty($updatedPieces)) {
            return;
        }

        $parts = [];

        if (!empty($createdScripts)) {
            $parts[] = Text::sprintf(
                'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CREATED_SCRIPTS',
                count($createdScripts),
                implode(', ', $createdScripts)
            );
        }

        if (!empty($createdPieces)) {
            $parts[] = Text::sprintf(
                'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CREATED_PIECES',
                count($createdPieces),
                implode(', ', $createdPieces)
            );
        }

        if (!empty($updatedScripts)) {
            $parts[] = Text::sprintf(
                'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_UPDATED_SCRIPTS',
                count($updatedScripts),
                implode(', ', $updatedScripts)
            );
        }

        if (!empty($updatedPieces)) {
            $parts[] = Text::sprintf(
                'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_UPDATED_PIECES',
                count($updatedPieces),
                implode(', ', $updatedPieces)
            );
        }

        $this->announce(
            Text::sprintf(
                'COM_BREEZINGFORMSNG_INSTALL_STANDARD_LIBRARY_CHANGES',
                implode(' | ', $parts)
            ),
            'warning',
            Log::INFO
        );
    }

    /**
     * @param mixed $items
     * @return list<string>
     */
    private function normalizeImportedItemNames($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $items = array_map(
            static fn($item): string => trim((string) $item),
            $items
        );
        $items = array_values(array_filter($items, static fn(string $item): bool => $item !== ''));
        $items = array_values(array_unique($items));
        sort($items, SORT_NATURAL | SORT_FLAG_CASE);

        return $items;
    }

    /**
     * @param mixed $parent
     */
    private function getIncomingVersion($parent): string
    {
        if (is_object($parent) && method_exists($parent, 'getManifest')) {
            $manifest = $parent->getManifest();

            if ($manifest instanceof \SimpleXMLElement) {
                $version = trim((string) ($manifest->version ?? ''));

                if ($version !== '') {
                    return $version;
                }
            }
        }

        $installer = is_object($parent) && method_exists($parent, 'getParent')
            ? $parent->getParent()
            : null;
        $manifest = $installer instanceof Installer
            ? $installer->getManifest()
            : null;

        return $manifest && isset($manifest->version) ? trim((string) $manifest->version) : '';
    }

    private function getLogTimestamp(): string
    {
        try {
            $offset = (string) Factory::getApplication()->get('offset', 'UTC');
            $date   = new \DateTime('now', new \DateTimeZone($offset !== '' ? $offset : 'UTC'));

            return $date->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return date('Y-m-d H:i:s');
        }
    }

    private function rotateLogIfNeeded(string $logPath): void
    {
        // Checked once per installer run (mirrors cbng's boot-time check),
        // rather than re-stat'ing the log file on every single log() call.
        if ($this->logRotationChecked) {
            return;
        }
        $this->logRotationChecked = true;

        $maxBytes = 1024 * 1024;

        try {
            if (!is_file($logPath) || filesize($logPath) < $maxBytes) {
                return;
            }

            $rotatedPath = $logPath . '.' . date('Y-m-d_His') . '.bak';
            @rename($logPath, $rotatedPath);

            $keepFiles = 10;
            $rotatedFiles = glob($logPath . '.*.bak') ?: [];
            sort($rotatedFiles);

            $excess = count($rotatedFiles) - $keepFiles;

            for ($i = 0; $i < $excess; $i++) {
                @unlink($rotatedFiles[$i]);
            }
        } catch (\Throwable) {
            // Best-effort log rotation only.
        }
    }

    private function log(string $message, int $priority = Log::INFO): void
    {
        if ($priority === Log::ERROR) {
            $this->criticalFailureDetected = true;
            $normalized = trim(strip_tags($message));

            if ($normalized !== '' && !in_array($normalized, $this->criticalFailureMessages, true)) {
                $this->criticalFailureMessages[] = $normalized;
            }
        }

        $message = $this->prefixMessage($message, $priority);
        Log::add($message, $priority, 'com_breezingformsng.install');

        $logPath = JPATH_ADMINISTRATOR . '/logs/breezingforms_install2.log';

        if (!Folder::exists(dirname($logPath))) {
            Folder::create(dirname($logPath));
        }

        $this->rotateLogIfNeeded($logPath);

        $timestamp = $this->getLogTimestamp();
        $line = "[{$timestamp}] [] {$message}" . PHP_EOL;

        file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);

        try {
            $type = match ($priority) {
                Log::ERROR => 'error',
                Log::WARNING => 'warning',
                default => 'message',
            };

            Factory::getApplication()->enqueueMessage($this->formatInstallMessageForDisplay($message), $type);
        } catch (\Throwable) {
        }
    }

    private function announce(string $message, string $type = 'message', int $priority = Log::INFO): void
    {
        $this->log($message, $priority);
    }

    private function resetCriticalFailures(): void
    {
        $this->criticalFailureDetected = false;
        $this->criticalFailureMessages = [];
    }

    private function hasCriticalFailure(): bool
    {
        return $this->criticalFailureDetected;
    }

    private function getCriticalFailureSummary(int $max = 3): string
    {
        if ($this->criticalFailureMessages === []) {
            return '';
        }

        $messages = array_slice($this->criticalFailureMessages, 0, $max);
        $summary = implode(' | ', $messages);
        $remaining = count($this->criticalFailureMessages) - count($messages);

        if ($remaining > 0) {
            $summary .= ' | +' . $remaining . ' more';
        }

        return $summary;
    }

    private function prefixMessage(string $message, int $priority = Log::INFO): string
    {
        if (preg_match('/^\[(OK|INFO|WARNING|ERROR)\]\s/u', $message)) {
            return $message;
        }

        return match ($priority) {
            Log::ERROR => '[ERROR] ' . $message,
            Log::WARNING => '[WARNING] ' . $message,
            default => '[INFO] ' . $message,
        };
    }

    private function formatInstallMessageForDisplay(string $message): string
    {
        return str_replace(
            ['[OK]', '[INFO]', '[WARNING]', '[ERROR]'],
            [
                '<span style="color:#198754;font-weight:700;" aria-hidden="true">&#10003;</span>',
                '<span style="color:#0d6efd;font-weight:700;" aria-hidden="true">&#9432;</span>',
                '<span style="color:#fd7e14;font-weight:700;" aria-hidden="true">&#9888;</span>',
                '<span style="color:#dc3545;font-weight:700;" aria-hidden="true">&#10060;</span>',
            ],
            $message
        );
    }

    private function getDatabaseRuntimeLabel(): string
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $serverType = $db instanceof DatabaseInterface ? (string) $db->getServerType() : 'database';
            $version = $db instanceof DatabaseInterface ? trim((string) $db->getVersion()) : '';
            $prefix = $db instanceof DatabaseInterface ? (string) $db->getPrefix() : '';

            $this->checkDatabaseSessionCharset($db);

            return trim($serverType . ' ' . $version) . ($prefix !== '' ? ' (prefix: ' . $prefix . ')' : '');
        } catch (\Throwable $e) {
            $this->log('Unable to detect database runtime information: ' . $e->getMessage(), Log::WARNING);
            return 'unknown';
        }
    }

    /**
     * Complements the existing table/column utf8mb4 conversion logic: even
     * with every table and column correctly converted, data can still be
     * mangled on write/read if the DB *connection itself* isn't negotiating
     * utf8mb4 (e.g. a driver/config defaulting the session to latin1).
     */
    private function checkDatabaseSessionCharset(DatabaseInterface $db): void
    {
        try {
            $db->setQuery('SELECT @@character_set_connection, @@collation_connection');
            $row = $db->loadAssoc() ?: [];
            $charset = strtolower((string) ($row['@@character_set_connection'] ?? ''));
            $collation = (string) ($row['@@collation_connection'] ?? '');

            if ($charset !== '' && $charset !== 'utf8mb4') {
                $this->log(
                    "Database session charset is '{$charset}' (collation '{$collation}'), not utf8mb4 - "
                    . 'the connection may not be negotiating utf8mb4 even if tables/columns are converted.',
                    Log::WARNING
                );
            }
        } catch (\Throwable) {
            // Best-effort diagnostic only; not every driver exposes these session variables.
        }
    }

    private function purgeCaches(string $context = 'install'): void
    {
        $autoloadFiles = [
            JPATH_ADMINISTRATOR . '/cache/autoload_psr4.php',
            JPATH_ADMINISTRATOR . '/cache/autoload_classmap.php',
            JPATH_ADMINISTRATOR . '/cache/autoload_namespaces.php',
        ];

        $deleted = 0;

        foreach ($autoloadFiles as $file) {
            try {
                if (is_file($file) && @unlink($file)) {
                    $deleted++;
                }
            } catch (\Throwable) {
                // Best-effort cache cleanup.
            }
        }

        if ($deleted > 0) {
            $this->log("Autoload cache cleared ({$deleted} file(s)) during {$context}.");
        }

        try {
            $app = Factory::getApplication();
            if (method_exists($app, 'createExtensionNamespaceMap')) {
                $app->createExtensionNamespaceMap();
                $this->log("Extension namespace map rebuilt during {$context}.");
            }
        } catch (\Throwable) {
            // Without this, a stale in-memory namespace map from earlier in
            // the same installer request could fail to autoload classes
            // that were just added/renamed by this same install/update.
        }

        $cacheGroups = ['_system', 'com_installer', 'com_plugins', 'com_breezingformsng'];

        try {
            $cacheFactory = Factory::getContainer()->get(CacheControllerFactoryInterface::class);

            if ($cacheFactory instanceof CacheControllerFactoryInterface) {
                foreach ($cacheGroups as $group) {
                    try {
                        $cacheFactory->createCacheController('callback', [
                            'defaultgroup' => $group,
                            'cachebase' => JPATH_ROOT . '/cache',
                        ])->clean();
                    } catch (\Throwable) {
                        // Continue with the remaining cache groups.
                    }
                }
            }

            $this->log("Joomla cache cleaned (best-effort) during {$context}.");
        } catch (\Throwable $e) {
            $this->log("Joomla cache cleanup failed during {$context}: " . $e->getMessage(), Log::WARNING);
        }

        try {
            if (function_exists('opcache_reset')) {
                @opcache_reset();
                $this->log("OPcache reset attempted during {$context}.");
            }
        } catch (\Throwable) {
            // Best-effort OPcache reset.
        }
    }

    /**
     * @param mixed $parent
     */
    private function verifyInstalledExtensionConsistency($parent): void
    {
        $requiredFiles = [
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/services/provider.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/Extension/BreezingFormsNGComponent.php',
            JPATH_SITE . '/components/com_breezingformsng/breezingformsng.php',
        ];

        foreach ($requiredFiles as $requiredFile) {
            if (!is_file($requiredFile)) {
                $this->log(
                    'Installed files inconsistent: required file missing after copy: ' . $requiredFile
                    . '. The package files were not deployed correctly - reinstall the package.',
                    Log::ERROR
                );

                return;
            }
        }

        $expectedVersion = $this->getIncomingVersion($parent);

        if ($expectedVersion === '') {
            $this->log('Installed files consistency: package version unknown, version check skipped.');

            return;
        }

        $installedVersion = $this->getCurrentInstalledVersion();

        if ($installedVersion !== '0.0.0' && $installedVersion !== $expectedVersion) {
            $this->log(
                'Installed files inconsistent: extension manifest cache reports version "' . $installedVersion
                . '" but the installed package is "' . $expectedVersion
                . '". The package files may not have been deployed correctly.',
                Log::ERROR
            );

            return;
        }

        $this->log('Installed files consistency verified.');
    }

    /**
     * @return string
     */
    private function getCurrentInstalledVersion()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select($db->quoteName('manifest_cache'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_breezingformsng'));

        $db->setQuery($query);
        $manifest = $db->loadResult();

        if ($manifest) {
            $manifest = json_decode($manifest, true);
            $version = $manifest['version'] ?? '0.0.0';
        } else {
            $version = '0.0.0';
        }

        $this->log('Detected current version : ' . $version . '.');
        return $version;
    }

    private function installModules(): void
    {
        $path = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/modules/mod_breezingforms';
        $installer = new Installer();
        $installer->setDatabase(Factory::getContainer()->get(DatabaseInterface::class));

        if (!$installer->install($path)) {
            $this->announce('Failed to install BreezingForms NG module.', 'error', Log::ERROR);
        }
    }

    private function installPlugins(): void
    {
        $basePath = JPATH_ADMINISTRATOR . '/components/com_breezingformsng/plugins';

        if (!Folder::exists($basePath)) {
            $this->log('Plugins directory not found – skipping plugin installation.', Log::WARNING);
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select(['folder', 'element', 'enabled'])
            ->from($db->quoteName('#__extensions'))
            ->where('type = ' . $db->quote('plugin'));
        $existingPlugins = [];
        foreach ($db->setQuery($query)->loadObjectList() as $plugin) {
            $existingPlugins[$plugin->folder][$plugin->element] = (int) $plugin->enabled;
        }

        $folders = Folder::folders($basePath);
        if (empty($folders)) {
            return;
        }

        foreach ($folders as $folder) {
            $this->log("Installing plugin from folder: {$folder}");

            // Installer keeps manifest state; use a fresh instance for every
            // bundled extension so each plugin receives its own database row.
            $installer = new Installer();
            $installer->setDatabase(Factory::getContainer()->get(DatabaseInterface::class));

            if ($installer->install($basePath . '/' . $folder)) {
                $this->log("Plugin {$folder} installed successfully.", Log::INFO);
            } else {
                $this->announce("Failed to install BreezingForms plugin: {$folder}", 'error', Log::ERROR);
            }
        }

        // Preserve existing plugin states; enable newly installed plugins only.
        $standardDb = Factory::getContainer()->get(DatabaseInterface::class);

        foreach ($this->getPlugins() as $folder => $plugins) {
            foreach ($plugins as $plugin) {
                $query = $standardDb->createQuery()
                    ->update('#__extensions')
                    ->set('enabled = ' . ($existingPlugins[$folder][$plugin] ?? 1))
                    ->where('type = ' . $standardDb->quote('plugin'))
                    ->where('element = ' . $standardDb->quote($plugin))
                    ->where('folder = ' . $standardDb->quote($folder));

                $standardDb->setQuery($query)->execute();
                if (!isset($existingPlugins[$folder][$plugin])) {
                    $this->log("Plugin {$plugin} enabled.");
                }
            }
        }
    }

    private function removeOldUpdateSite(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->createQuery()
            ->select('update_site_id')
            ->from('#__update_sites')
            ->where('name = ' . $db->quote('BreezingForms Free'))
            ->where('type = ' . $db->quote('extension'));

        $db->setQuery($query);
        $siteId = $db->loadResult();

        if ($siteId) {
            foreach (['#__update_sites', '#__update_sites_extensions', '#__updates'] as $table) {
                $query = $db->createQuery()
                    ->delete($db->quoteName($table))
                    ->where($db->quoteName('update_site_id') . ' = ' . (int) $siteId);
                $db->setQuery($query)->execute();
            }

            $this->log('Old BreezingForms Free update site removed.');
        }
    }

    private function cleanupOldConfig(): void
    {
        $oldConfig = JPATH_SITE . '/media/breezingforms/facileforms.config.php';
        if (File::exists($oldConfig) && File::delete($oldConfig)) {
            $this->log('Old config file removed: facileforms.config.php');
        }
    }

    private function cleanupComponentLogFile(): void
    {
        $logFile = JPATH_ADMINISTRATOR . '/logs/com_breezingformsng.log';

        if (!is_file($logFile)) {
            return;
        }

        try {
            if (File::delete($logFile)) {
                $this->log('Component log file removed: com_breezingformsng.log');
            }
        } catch (\Throwable $e) {
            $this->log('Unable to remove component log file during uninstall: ' . $e->getMessage(), Log::WARNING);
        }
    }

    private function copyComponentImageAssets(): void
    {
        $sourceImages = JPATH_SITE . '/media/com_breezingformsng/images/site';
        $targetRoot = JPATH_SITE . '/images';
        $directories = ['icons', 'galerie/breezingforms'];

        if (!Folder::exists($sourceImages)) {
            $this->log("Component image directory not found: {$sourceImages}. Skipping copy.");
            return;
        }

        foreach ($directories as $directory) {
            $sourceDir = $sourceImages . '/' . $directory;
            if (!Folder::exists($sourceDir)) {
                continue;
            }

            $relativeCounter = 0;
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $relativePath = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($sourceImages))), '/');
                if ($relativePath === '') {
                    continue;
                }

                $targetPath = $targetRoot . '/' . $relativePath;
                $targetDir = dirname($targetPath);

                if (!Folder::exists($targetDir)) {
                    Folder::create($targetDir);
                }

                if (!@copy($file->getPathname(), $targetPath)) {
                    $this->log("Failed to copy component image '{$relativePath}' into /images.", Log::WARNING);
                    continue;
                }

                $relativeCounter++;
            }

            if ($relativeCounter > 0) {
                $this->log("Copied {$relativeCounter} file(s) from '{$directory}' into /images.");
            }
        }
    }

    /**
     * @return list<string>
     */
    private function buildMenuLinkOptionWhereClauses(DatabaseInterface $db, string $option): array
    {
        $quotedOption = $db->quote('%option=' . $option . '%');

        return [
            $db->quoteName('link') . ' LIKE ' . $quotedOption,
            $db->quoteName('params') . ' LIKE ' . $db->quote('%"option":"' . $option . '"%'),
        ];
    }

    /**
     * Lists the component in Joomla's User Actions Log. On first registration
     * it is also added to the logged extensions; afterwards that setting
     * belongs to the administrator.
     */
    private function registerActionLogExtension(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $extension = 'com_breezingformsng';

        $query = $db->createQuery()
            ->select('COUNT(*)')
            ->from($db->quoteName('#__action_logs_extensions'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote($extension));

        if ((int) $db->setQuery($query)->loadResult() > 0) {
            return;
        }

        $query = $db->createQuery()
            ->insert($db->quoteName('#__action_logs_extensions'))
            ->columns($db->quoteName('extension'))
            ->values($db->quote($extension));
        $db->setQuery($query)->execute();

        $query = $db->createQuery()
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_actionlogs'));
        $params = json_decode((string) $db->setQuery($query)->loadResult(), true);
        $params = is_array($params) ? $params : [];
        $loggable = (array) ($params['loggable_extensions'] ?? []);

        if (!in_array($extension, $loggable, true)) {
            $loggable[] = $extension;
            $params['loggable_extensions'] = array_values($loggable);

            $query = $db->createQuery()
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_actionlogs'));
            $db->setQuery($query)->execute();
        }

        $this->log('Component registered in the User Actions Log.');
    }

    private function unregisterActionLogExtension(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->createQuery()
            ->delete($db->quoteName('#__action_logs_extensions'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_breezingformsng'));
        $db->setQuery($query)->execute();
    }

    /**
     * Declares the forms, scripts and pieces to the Joomla content history,
     * which keeps their versions (Versions button of their editors).
     */
    private function registerContentTypes(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $common = [
            'dbtable' => '#__ucm_content',
            'key' => 'ucm_id',
            'type' => 'Corecontent',
            'prefix' => 'Joomla\\CMS\\Table\\',
            'config' => 'array()',
        ];
        $mappings = [
            'core_content_item_id' => 'id',
            'core_title' => 'title',
            'core_state' => 'published',
            'core_alias' => 'name',
            'core_body' => 'description',
            'core_created_time' => 'created',
            'core_modified_time' => 'modified',
        ];
        $codeItemHistory = [
            'hideFields' => ['checked_out', 'checked_out_time'],
            'ignoreChanges' => ['modified', 'modified_by', 'checked_out', 'checked_out_time'],
            'convertToInt' => [],
            'displayLookup' => [],
        ];
        $types = [
            'com_breezingformsng.form' => [
                'BreezingForms NG Form', '#__facileforms_forms', 'FormTable', [
                    'formFile' => '',
                    'hideFields' => ['checked_out', 'checked_out_time', 'version', 'template_code', 'template_areas'],
                    // As for articles, the history compares the content, version left out.
                    // template_areas is rebuilt by QuickMode at each save.
                    'ignoreChanges' => [
                        'modified',
                        'modified_by',
                        'checked_out',
                        'checked_out_time',
                        'version',
                        'ordering',
                        'template_areas',
                    ],
                    'convertToInt' => [],
                    'displayLookup' => [],
                ],
            ],
            'com_breezingformsng.script' => [
                'BreezingForms NG Script', '#__facileforms_scripts', 'ScriptTable',
                ['formFile' => 'administrator/components/com_breezingformsng/forms/script.xml'] + $codeItemHistory,
            ],
            'com_breezingformsng.piece' => [
                'BreezingForms NG Piece', '#__facileforms_pieces', 'PieceTable',
                ['formFile' => 'administrator/components/com_breezingformsng/forms/piece.xml'] + $codeItemHistory,
            ],
        ];

        foreach ($types as $alias => [$title, $dbTable, $tableClass, $historyOptions]) {
            $values = [
                'type_title' => $title,
                'rules' => '',
                'router' => '',
                'table' => json_encode([
                    'special' => [
                        'dbtable' => $dbTable,
                        'key' => 'id',
                        'type' => $tableClass,
                        'prefix' => 'Vcmb\\Component\\BreezingformsNG\\Site\\Table\\',
                        'config' => 'array()',
                    ],
                    'common' => $common,
                ]),
                'field_mappings' => json_encode(['common' => $mappings, 'special' => []]),
                'content_history_options' => json_encode($historyOptions),
            ];

            $query = $db->createQuery()
                ->select($db->quoteName('type_id'))
                ->from($db->quoteName('#__content_types'))
                ->where($db->quoteName('type_alias') . ' = ' . $db->quote($alias));
            $typeId = (int) $db->setQuery($query)->loadResult();

            if ($typeId > 0) {
                $query = $db->createQuery()
                    ->update($db->quoteName('#__content_types'))
                    ->where($db->quoteName('type_id') . ' = ' . $typeId);

                foreach ($values as $column => $value) {
                    $query->set($db->quoteName($column) . ' = ' . $db->quote((string) $value));
                }
            } else {
                $query = $db->createQuery()
                    ->insert($db->quoteName('#__content_types'))
                    ->columns($db->quoteName(['type_alias', ...array_keys($values)]))
                    ->values(implode(',', array_map(
                        static fn ($value): string => $db->quote((string) $value),
                        [$alias, ...array_values($values)]
                    )));
            }

            $db->setQuery($query)->execute();
        }

        $this->log('Form, script and piece content types registered for the version history.');
    }

    /**
     * The Versionable behaviour plugin reads Enable Versions from the stored
     * options, where a missing value means off: store the defaults of
     * config.xml until an administrator saves the options.
     */
    private function setVersionHistoryDefaults(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select($db->quoteName('params'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('com_breezingformsng'));
        $params = json_decode((string) $db->setQuery($query)->loadResult(), true);
        $params = is_array($params) ? $params : [];

        if (!array_key_exists('save_history', $params)) {
            $params['save_history'] = 1;
            $params['history_limit'] = $params['history_limit'] ?? 10;

            $query = $db->createQuery()
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('params') . ' = ' . $db->quote((string) json_encode($params)))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_breezingformsng'));
            $db->setQuery($query)->execute();
            $this->log('Version history enabled with its default options.');
        }

        // The components were loaded when this request started, before a
        // first installation of this one, and Joomla keeps them in its
        // _system cache: the versions stored by the installation read its
        // options from a fresh load.
        Factory::getContainer()->get(CacheControllerFactoryInterface::class)
            ->createCacheController('callback', ['defaultgroup' => '_system'])
            ->clean();
        (new class () extends ComponentHelper {
            public static function reset(): void
            {
                static::$components = [];
            }
        })::reset();
    }

    /**
     * Gives each form, script and piece without versions its version of
     * origin, kept forever: a form at version 0 goes to 1.
     */
    private function keepVersionOrigins(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $tables = [
                'Form' => '#__facileforms_forms',
                'Script' => '#__facileforms_scripts',
                'Piece' => '#__facileforms_pieces',
            ];

            foreach ($tables as $name => $table) {
                $model = $this->componentModel($name);
                $ids = \Vcmb\Component\BreezingformsNG\Administrator\Service\VersionOrigin::missing(
                    $db,
                    'com_breezingformsng.' . strtolower($name),
                    array_values(array_map('intval', $db->setQuery(
                        $db->createQuery()->select($db->quoteName('id'))->from($db->quoteName($table))
                    )->loadColumn()))
                );

                foreach ($ids as $id) {
                    if ($model instanceof \Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel) {
                        (new \Vcmb\Component\BreezingformsNG\Administrator\Service\FormVersioning($db, $model))
                            ->keepOrigin($id);
                    } elseif ($model instanceof \Vcmb\Component\BreezingformsNG\Administrator\Model\PackageItemModel) {
                        $model->keepOrigin($id);
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->log('Versions of origin not kept in the history: ' . $e->getMessage(), Log::WARNING);

            return;
        }

        $this->log('Versions of origin kept in the history.');
    }

    /**
     * The model of the scripts or of the pieces, which keeps their versions.
     */
    private function packageItemModel(
        string $name
    ): \Vcmb\Component\BreezingformsNG\Administrator\Model\PackageItemModel {
        $model = $this->componentModel($name);

        if (!$model instanceof \Vcmb\Component\BreezingformsNG\Administrator\Model\PackageItemModel) {
            throw new \RuntimeException('No ' . $name . ' model.');
        }

        return $model;
    }

    /**
     * A model of the component being installed. A first installation runs
     * before Joomla knows the namespace of the component: it is declared here.
     */
    private function componentModel(string $name): object
    {
        \JLoader::registerNamespace(
            'Vcmb\\Component\\BreezingformsNG\\Administrator',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src'
        );
        \JLoader::registerNamespace(
            'Vcmb\\Component\\BreezingformsNG\\Site',
            JPATH_SITE . '/components/com_breezingformsng/src'
        );

        $component = Factory::getApplication()->bootComponent('com_breezingformsng');

        if (!$component instanceof \Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface) {
            throw new \RuntimeException('The component has no MVC factory.');
        }

        $model = $component->getMVCFactory()->createModel($name, 'Administrator', ['ignore_request' => true]);

        if (!\is_object($model)) {
            throw new \RuntimeException('No ' . $name . ' model.');
        }

        return $model;
    }

    private function unregisterContentTypes(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $aliases = ['com_breezingformsng.form', 'com_breezingformsng.script', 'com_breezingformsng.piece'];

        foreach ($aliases as $alias) {
            $db->setQuery(
                $db->createQuery()
                    ->delete($db->quoteName('#__history'))
                    ->where($db->quoteName('item_id') . ' LIKE ' . $db->quote($alias . '.%'))
            )->execute();
        }

        $db->setQuery(
            $db->createQuery()
                ->delete($db->quoteName('#__content_types'))
                ->whereIn($db->quoteName('type_alias'), $aliases, ParameterType::STRING)
        )->execute();
    }

    private function removeBreezingFormsMenuEntries(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $conditions = [];

        foreach (self::LEGACY_COMPONENT_ALIASES as $alias) {
            $conditions = array_merge($conditions, $this->buildMenuLinkOptionWhereClauses($db, $alias));
        }

        if ($conditions === []) {
            return;
        }

        try {
            $db->setQuery(
                $db->createQuery()
                    ->delete($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where('(' . implode(' OR ', $conditions) . ')')
            );
            $db->execute();
            $this->log('BreezingForms admin menu entries removed (best-effort).');
        } catch (\Throwable $e) {
            $this->log('Unable to remove BreezingForms admin menu entries: ' . $e->getMessage(), Log::WARNING);
        }
    }

    /**
     * @param array<int|string> $extensionIds
     */
    private function cleanupExtensionReferences(array $extensionIds): void
    {
        $extensionIds = array_values(
            array_filter(array_map('intval', $extensionIds), static fn(int $id): bool => $id > 0)
        );

        if ($extensionIds === []) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $ids = implode(',', $extensionIds);

        foreach (['#__schemas', '#__update_sites_extensions'] as $table) {
            try {
                $db->setQuery(
                    $db->createQuery()
                        ->delete($db->quoteName($table))
                        ->where($db->quoteName('extension_id') . ' IN (' . $ids . ')')
                );
                $db->execute();
            } catch (\Throwable $e) {
                $this->log(
                    "Unable to clean {$table} for BreezingForms legacy extension rows: " . $e->getMessage(),
                    Log::WARNING
                );
            }
        }
    }

    private function getComponentExtensionId(string $element): int
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $db->setQuery(
                $db->createQuery()
                    ->select($db->quoteName('extension_id'))
                    ->from($db->quoteName('#__extensions'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                    ->where($db->quoteName('element') . ' = ' . $db->quote($element))
                    ->where($db->quoteName('client_id') . ' = 1')
            );

            return (int) $db->loadResult();
        } catch (\Throwable $e) {
            $this->log("Unable to inspect component extension row {$element}: " . $e->getMessage(), Log::WARNING);
            return 0;
        }
    }

    /**
     * An earlier migration renamed com_breezingforms to com_breezingformsng
     * in menu links on every update, leaving com_breezingformsngngng... and
     * menu items that lead to "Component not found".
     */
    private function repairRepeatedMenuComponentSuffix(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $pattern = 'option=' . self::TARGET_COMPONENT . '(ng)+';
        $replacement = 'option=' . self::TARGET_COMPONENT;

        try {
            $db->setQuery(
                $db->createQuery()
                    ->update($db->quoteName('#__menu'))
                    ->set(
                        $db->quoteName('link')
                        . ' = REGEXP_REPLACE(' . $db->quoteName('link') . ', :pattern, :replacement)'
                    )
                    ->where($db->quoteName('link') . ' REGEXP :match')
                    ->bind(':pattern', $pattern)
                    ->bind(':replacement', $replacement)
                    ->bind(':match', $pattern)
            );
            $db->execute();

            $repaired = (int) $db->getAffectedRows();

            if ($repaired > 0) {
                $this->log("Repaired the component name in {$repaired} Joomla menu link(s).");
            }
        } catch (\Throwable $e) {
            $this->log('Unable to repair the component name in Joomla menu links: ' . $e->getMessage(), Log::WARNING);
        }
    }

    private function normalizeJoomlaComponentReferences(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $targetElement = self::TARGET_COMPONENT;
        $this->repairRepeatedMenuComponentSuffix();
        $targetId = $this->getComponentExtensionId($targetElement);

        if ($targetId <= 0) {
            $this->log('BFNG component row not found; Joomla component reference normalization skipped.', Log::WARNING);
            return;
        }

        foreach (['breezingforms', 'com_breezingforms'] as $legacyElement) {
            $legacyId = $this->getComponentExtensionId($legacyElement);

            try {
                $query = $db->createQuery()
                    ->update($db->quoteName('#__menu'))
                    ->set($db->quoteName('component_id') . ' = ' . (int) $targetId)
                    ->set(
                        $db->quoteName('link') . ' = REPLACE(' . $db->quoteName('link')
                        . ', ' . $db->quote('option=' . $legacyElement)
                        . ', ' . $db->quote('option=' . $targetElement) . ')'
                    )
                    ->set(
                        $db->quoteName('params') . ' = REPLACE(' . $db->quoteName('params')
                        . ', ' . $db->quote('"option":"' . $legacyElement . '"')
                        . ', ' . $db->quote('"option":"' . $targetElement . '"') . ')'
                    )
                    ->where(
                        '('
                        . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=' . $legacyElement . '%')
                        . ' OR ' . $db->quoteName('params')
                        . ' LIKE ' . $db->quote('%"option":"' . $legacyElement . '"%')
                        . ')'
                    );

                if ($legacyElement === 'com_breezingforms') {
                    $query->where(
                        $db->quoteName('link') . ' NOT LIKE ' . $db->quote('%option=' . $targetElement . '%')
                    );
                    $query->where(
                        $db->quoteName('params') . ' NOT LIKE ' . $db->quote('%"option":"' . $targetElement . '"%')
                    );
                }

                $db->setQuery($query);
                $db->execute();

                $updated = (int) $db->getAffectedRows();
                if ($updated > 0) {
                    $this->log("Normalized {$updated} Joomla menu row(s) from {$legacyElement} to {$targetElement}.");
                }
            } catch (\Throwable $e) {
                $this->log(
                    "Unable to normalize Joomla menu links for {$legacyElement}: " . $e->getMessage(),
                    Log::WARNING
                );
            }

            if ($legacyId > 0) {
                try {
                    $db->setQuery(
                        $db->createQuery()
                            ->update($db->quoteName('#__menu'))
                            ->set($db->quoteName('component_id') . ' = ' . (int) $targetId)
                            ->where($db->quoteName('component_id') . ' = ' . (int) $legacyId)
                    );
                    $db->execute();
                } catch (\Throwable $e) {
                    $this->log(
                        "Unable to remap Joomla menu component_id for {$legacyElement}: " . $e->getMessage(),
                        Log::WARNING
                    );
                }
            }

            $this->normalizeComponentAssetName($legacyElement, $targetElement);
        }

        $this->normalizeJoomlaMenuIdentity($targetId);
    }

    private function normalizeJoomlaMenuIdentity(int $targetId): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $oldTitleBase = 'COM_' . 'BREEZINGFORMS';
        $newTitleBase = 'COM_BREEZINGFORMSNG';
        $oldAliasBase = 'com-breezingforms';
        $newAliasBase = 'com-breezingformsng';

        try {
            $db->setQuery(
                $db->createQuery()
                    ->select($db->quoteName([
                        'id', 'parent_id', 'language', 'title', 'alias', 'path', 'link', 'params',
                    ]))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where(
                        '('
                        . $db->quoteName('title') . ' LIKE ' . $db->quote($oldTitleBase . '%')
                        . ' OR ' . $db->quoteName('alias') . ' LIKE ' . $db->quote('%' . $oldAliasBase . '%')
                        . ' OR ' . $db->quoteName('path') . ' LIKE ' . $db->quote('%' . $oldAliasBase . '%')
                        . ' OR ' . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=breezingforms%')
                        . ' OR ' . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_breezingforms%')
                        . ' OR ' . $db->quoteName('link')
                        . ' LIKE ' . $db->quote('%option=' . self::TARGET_COMPONENT . '%')
                        . ')'
                    )
            );

            $rows = $db->loadObjectList() ?: [];
            $updated = 0;
            $removedIds = [];

            foreach ($rows as $row) {
                if (in_array((int) $row->id, $removedIds, true)) {
                    continue;
                }

                $title = (string) ($row->title ?? '');
                $alias = (string) ($row->alias ?? '');
                $path = (string) ($row->path ?? '');
                $link = (string) ($row->link ?? '');
                $params = (string) ($row->params ?? '');

                if ($title === $oldTitleBase) {
                    $title = $newTitleBase;
                } elseif (str_starts_with($title, $oldTitleBase . '_')) {
                    $title = $newTitleBase . substr($title, strlen($oldTitleBase));
                }

                $alias = preg_replace('/com-breezingforms(?:ng)*/', $newAliasBase, $alias) ?? $alias;
                $path = preg_replace('/com-breezingforms(?:ng)*/', $newAliasBase, $path) ?? $path;
                $link = preg_replace(
                    '/option=com_breezingforms(?:ng)*/',
                    'option=' . self::TARGET_COMPONENT,
                    $link
                ) ?? $link;
                $link = str_replace('option=breezingforms', 'option=' . self::TARGET_COMPONENT, $link);
                $params = preg_replace(
                    '/"option":"com_breezingforms(?:ng)*"/',
                    '"option":"' . self::TARGET_COMPONENT . '"',
                    $params
                ) ?? $params;
                $params = str_replace('"option":"breezingforms"', '"option":"' . self::TARGET_COMPONENT . '"', $params);

                // Remove a legacy entry only when the conflicting sibling is
                // the corresponding BFNG entry and the legacy branch contains
                // no custom menu items.
                $collision = $alias !== (string) $row->alias
                    ? $this->menuAliasCollision($db, $row, $alias)
                    : null;

                if ($collision !== null) {
                    $branchIds = $this->menuBranchIds($db, (int) $row->id);

                    if (
                        (int) $collision->component_id !== $targetId
                        || (string) $collision->title !== $title
                        || (string) $collision->link !== $link
                        || !$this->isLegacyBreezingFormsMenuBranch($db, $branchIds)
                    ) {
                        $this->log(
                            'Preserved administration menu entry ' . (int) $row->id
                            . " ({$row->alias}): {$alias} is already used by another entry"
                            . ' or the branch contains custom items.',
                            Log::WARNING
                        );

                        continue;
                    }

                    $menu = new Menu($db);

                    if (!$menu->delete((int) $row->id, true)) {
                        throw new \RuntimeException('Legacy menu entry ' . (int) $row->id . ' could not be removed.');
                    }

                    $removedIds = array_merge($removedIds, $branchIds);
                    $this->log(
                        'Removed legacy administration menu entry ' . (int) $row->id
                        . " ({$row->alias}) duplicating {$alias}."
                    );

                    continue;
                }

                $db->setQuery(
                    $db->createQuery()
                        ->update($db->quoteName('#__menu'))
                        ->set($db->quoteName('component_id') . ' = ' . (int) $targetId)
                        ->set($db->quoteName('title') . ' = ' . $db->quote($title))
                        ->set($db->quoteName('alias') . ' = ' . $db->quote($alias))
                        ->set($db->quoteName('path') . ' = ' . $db->quote($path))
                        ->set($db->quoteName('link') . ' = ' . $db->quote($link))
                        ->set($db->quoteName('params') . ' = ' . $db->quote($params))
                        ->where($db->quoteName('id') . ' = ' . (int) $row->id)
                );
                $db->execute();
                $updated += (int) $db->getAffectedRows();
            }

            if ($updated > 0) {
                $this->log(
                    "Normalized {$updated} Joomla administration menu identity row(s) for "
                    . self::TARGET_COMPONENT . '.'
                );
            }
        } catch (\Throwable $e) {
            $this->log('Unable to normalize Joomla administration menu identity: ' . $e->getMessage(), Log::WARNING);
        }
    }

    /**
     * @param \stdClass $row
     * @return ?\stdClass
     */
    private function menuAliasCollision(DatabaseInterface $db, object $row, string $alias): ?object
    {
        $id = (int) $row->id;
        $parentId = (int) $row->parent_id;
        $language = (string) $row->language;

        return $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['component_id', 'title', 'link']))
                ->from($db->quoteName('#__menu'))
                ->where($db->quoteName('client_id') . ' = 1')
                ->where($db->quoteName('parent_id') . ' = :parentId')
                ->where($db->quoteName('alias') . ' = :alias')
                ->where($db->quoteName('language') . ' = :language')
                ->where($db->quoteName('id') . ' <> :id')
                ->bind(':parentId', $parentId, ParameterType::INTEGER)
                ->bind(':alias', $alias)
                ->bind(':language', $language)
                ->bind(':id', $id, ParameterType::INTEGER)
        )->loadObject() ?: null;
    }

    /**
     * A custom child must not be removed with a legacy component menu branch.
     *
     * @param int[] $branchIds
     */
    private function isLegacyBreezingFormsMenuBranch(DatabaseInterface $db, array $branchIds): bool
    {
        $rows = $db->setQuery(
            $db->createQuery()
                ->select($db->quoteName(['title', 'link']))
                ->from($db->quoteName('#__menu'))
                ->whereIn($db->quoteName('id'), $branchIds)
        )->loadObjectList() ?: [];

        foreach ($rows as $row) {
            if (
                !str_starts_with((string) $row->title, 'COM_BREEZINGFORMS')
                || !preg_match(
                    '/(?:[?&]|^)option=(?:breezingforms|com_breezingforms(?:ng)?)(?:&|$)/',
                    (string) $row->link
                )
            ) {
                return false;
            }
        }

        return count($rows) === count($branchIds);
    }

    /**
     * @return array<int, int>
     */
    private function menuBranchIds(DatabaseInterface $db, int $rootId): array
    {
        $ids = [$rootId];

        for ($parents = [$rootId]; $parents !== [];) {
            $parents = array_map('intval', $db->setQuery(
                $db->createQuery()
                    ->select($db->quoteName('id'))
                    ->from($db->quoteName('#__menu'))
                    ->whereIn($db->quoteName('parent_id'), $parents)
            )->loadColumn());
            $ids = array_merge($ids, $parents);
        }

        return $ids;
    }

    private function normalizeComponentAssetName(string $legacyElement, string $targetElement): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $db->setQuery(
                $db->createQuery()
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__assets'))
                    ->where($db->quoteName('name') . ' = ' . $db->quote($targetElement))
            );
            $targetExists = (int) $db->loadResult() > 0;

            if ($targetExists) {
                return;
            }

            $db->setQuery(
                $db->createQuery()
                    ->update($db->quoteName('#__assets'))
                    ->set($db->quoteName('name') . ' = ' . $db->quote($targetElement))
                    ->set($db->quoteName('title') . ' = ' . $db->quote('COM_BREEZINGFORMSNG'))
                    ->where($db->quoteName('name') . ' = ' . $db->quote($legacyElement))
            );
            $db->execute();

            if ((int) $db->getAffectedRows() > 0) {
                $this->log("Normalized Joomla asset from {$legacyElement} to {$targetElement}.");
            }
        } catch (\Throwable $e) {
            $this->log("Unable to normalize Joomla asset {$legacyElement}: " . $e->getMessage(), Log::WARNING);
        }
    }

    private function resolveMenuAlias(int $parentId, string $preferredAlias): string
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $alias = $preferredAlias;
        $suffix = 2;

        while (true) {
            $db->setQuery(
                $db->createQuery()
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where($db->quoteName('parent_id') . ' = ' . (int) $parentId)
                    ->where($db->quoteName('alias') . ' = ' . $db->quote($alias))
            );

            if ((int) $db->loadResult() === 0) {
                return $alias;
            }

            $alias = $preferredAlias . '-' . $suffix;
            $suffix++;
        }
    }

    /**
     * The #__menu root row (id=1) is the anchor every nested-set menu
     * computation below relies on (reading its rgt to compute new lft/rgt
     * values). Rather than guessing at a repair insert for a core Joomla
     * table, just detect a missing/corrupt root and skip the menu-repair
     * routines entirely when it can't be trusted.
     */
    private function ensureAdminMenuRootNodeExists(): bool
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $db->setQuery(
                $db->createQuery()
                    ->select($db->quoteName(['id', 'lft', 'rgt']))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('id') . ' = 1')
            );
            $root = $db->loadAssoc();

            if ($root === null) {
                $this->log(
                    '#__menu root node (id=1) is missing;'
                    . ' skipping admin menu repair to avoid computing invalid nested-set values.',
                    Log::WARNING
                );
                return false;
            }

            if ((int) $root['rgt'] <= (int) $root['lft']) {
                $this->log(
                    '#__menu root node (id=1) has invalid lft/rgt values; skipping admin menu repair.',
                    Log::WARNING
                );
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->log('Unable to verify #__menu root node: ' . $e->getMessage(), Log::WARNING);
            return false;
        }
    }

    private function ensureAdministrationMainMenuEntry(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $componentId = $this->getComponentExtensionId(self::TARGET_COMPONENT);

        if ($componentId <= 0) {
            $this->log('Cannot ensure BFNG admin menu entry: component row is missing.', Log::WARNING);
            return;
        }

        try {
            $db->setQuery(
                $db->createQuery()
                    ->select($db->quoteName(['id', 'alias', 'path']))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where($db->quoteName('parent_id') . ' = 1')
                    ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                    ->where(
                        '('
                        . $db->quoteName('component_id') . ' = ' . (int) $componentId
                        . ' OR ' . $db->quoteName('link')
                        . ' LIKE ' . $db->quote('index.php?option=' . self::TARGET_COMPONENT . '%')
                        . ')'
                    )
                    ->order($db->quoteName('id') . ' ASC')
            );
            $mainRows = $db->loadAssocList() ?: [];

            if ($mainRows !== []) {
                $mainId = (int) $mainRows[0]['id'];
                $alias = trim((string) ($mainRows[0]['alias'] ?? ''));
                $path = trim((string) ($mainRows[0]['path'] ?? ''));

                if ($alias === '') {
                    $alias = $this->resolveMenuAlias(1, 'breezingformsng');
                }

                if ($path === '') {
                    $path = $alias;
                }

                $db->setQuery(
                    $db->createQuery()
                        ->update($db->quoteName('#__menu'))
                        ->set($db->quoteName('title') . ' = ' . $db->quote('COM_BREEZINGFORMSNG'))
                        ->set($db->quoteName('alias') . ' = ' . $db->quote($alias))
                        ->set($db->quoteName('path') . ' = ' . $db->quote($path))
                        ->set($db->quoteName('link') . ' = ' . $db->quote('index.php?option=' . self::TARGET_COMPONENT))
                        ->set($db->quoteName('type') . ' = ' . $db->quote('component'))
                        ->set($db->quoteName('published') . ' = 1')
                        ->set($db->quoteName('component_id') . ' = ' . (int) $componentId)
                        ->set($db->quoteName('client_id') . ' = 1')
                        ->where($db->quoteName('id') . ' = ' . (int) $mainId)
                );
                $db->execute();
                $this->log('BFNG administration component menu entry checked and updated.');
                return;
            }

            $db->setQuery(
                $db->createQuery()
                    ->select($db->quoteName(['id', 'rgt']))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('id') . ' = 1')
            );
            $root = $db->loadAssoc() ?: [];
            $rootId = (int) ($root['id'] ?? 1);
            $rootRgt = (int) ($root['rgt'] ?? 0);

            if ($rootRgt <= 0) {
                $this->log('Cannot recreate BFNG admin menu entry: invalid root menu boundary.', Log::WARNING);
                return;
            }

            $alias = $this->resolveMenuAlias($rootId, 'breezingformsng');

            $db->setQuery(
                $db->createQuery()
                    ->update($db->quoteName('#__menu'))
                    ->set($db->quoteName('rgt') . ' = ' . $db->quoteName('rgt') . ' + 2')
                    ->where($db->quoteName('rgt') . ' >= ' . (int) $rootRgt)
            );
            $db->execute();

            $db->setQuery(
                $db->createQuery()
                    ->update($db->quoteName('#__menu'))
                    ->set($db->quoteName('lft') . ' = ' . $db->quoteName('lft') . ' + 2')
                    ->where($db->quoteName('lft') . ' > ' . (int) $rootRgt)
            );
            $db->execute();

            $columns = [
                'menutype',
                'title',
                'alias',
                'note',
                'path',
                'link',
                'type',
                'published',
                'parent_id',
                'level',
                'component_id',
                'checked_out',
                'checked_out_time',
                'browserNav',
                'access',
                'img',
                'template_style_id',
                'params',
                'lft',
                'rgt',
                'home',
                'language',
                'client_id',
            ];

            $values = [
                $db->quote('main'),
                $db->quote('COM_BREEZINGFORMSNG'),
                $db->quote($alias),
                $db->quote(''),
                $db->quote($alias),
                $db->quote('index.php?option=' . self::TARGET_COMPONENT),
                $db->quote('component'),
                1,
                $rootId,
                1,
                $componentId,
                0,
                'NULL',
                0,
                1,
                $db->quote('class:component'),
                0,
                $db->quote(''),
                $rootRgt,
                $rootRgt + 1,
                0,
                $db->quote('*'),
                1,
            ];

            $db->setQuery(
                $db->createQuery()
                    ->insert($db->quoteName('#__menu'))
                    ->columns($db->quoteName($columns))
                    ->values(implode(', ', $values))
            );
            $db->execute();

            $this->log('BFNG administration component menu entry recreated.');
        } catch (\Throwable $e) {
            $this->log('Unable to ensure BFNG administration menu entry: ' . $e->getMessage(), Log::WARNING);
        }
    }

    private function ensureAdministrationSubmenuEntries(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $componentId = $this->getComponentExtensionId(self::TARGET_COMPONENT);

        if ($componentId <= 0) {
            $this->log('Cannot ensure BFNG admin submenu entries: component row is missing.', Log::WARNING);
            return;
        }

        try {
            $db->setQuery(
                $db->createQuery()
                    ->select($db->quoteName(['id', 'alias', 'path']))
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where($db->quoteName('parent_id') . ' = 1')
                    ->where($db->quoteName('link') . ' = ' . $db->quote('index.php?option=' . self::TARGET_COMPONENT))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                    ->order($db->quoteName('id') . ' ASC')
            );
            $parent = $db->loadAssoc() ?: [];
            $parentId = (int) ($parent['id'] ?? 0);

            if ($parentId <= 0) {
                $this->log('Cannot ensure BFNG admin submenu entries: parent menu entry is missing.', Log::WARNING);
                return;
            }

            $parentPath = trim((string) ($parent['path'] ?? ''));

            if ($parentPath === '') {
                $parentPath = trim((string) ($parent['alias'] ?? 'breezingformsng'));
            }

            $items = [
                ['COM_BREEZINGFORMSNG_MANAGE_RECORDS', 'breezingformsng-records', 'view=records', []],
                [
                    'COM_BREEZINGFORMSNG_MANAGE_FORMS',
                    'breezingformsng-forms',
                    'view=forms',
                    [
                        'menu-quicktask' => 'index.php?option=' . self::TARGET_COMPONENT . '&task=forms.edit',
                        'menu-quicktask-title' => 'COM_BREEZINGFORMSNG_MENUS_NEW_FORM',
                        'menu-quicktask-icon' => 'plus',
                    ],
                ],
                [
                    'COM_BREEZINGFORMSNG_MANAGE_SCRIPTS',
                    'breezingformsng-scripts',
                    'view=scripts',
                    [
                        'menu-quicktask' => 'index.php?option=' . self::TARGET_COMPONENT
                            . '&view=scripts&task=scripts.add',
                        'menu-quicktask-title' => 'COM_BREEZINGFORMSNG_MENUS_NEW_SCRIPT',
                        'menu-quicktask-icon' => 'plus',
                    ],
                ],
                [
                    'COM_BREEZINGFORMSNG_MANAGE_PIECES',
                    'breezingformsng-pieces',
                    'view=pieces',
                    [
                        'menu-quicktask' => 'index.php?option=' . self::TARGET_COMPONENT
                            . '&view=pieces&task=pieces.add',
                        'menu-quicktask-title' => 'COM_BREEZINGFORMSNG_MENUS_NEW_PIECE',
                        'menu-quicktask-icon' => 'plus',
                    ],
                ],
                [
                    'COM_BREEZINGFORMSNG_INTEGRATOR',
                    'breezingformsng-integrator',
                    'view=integrator',
                    [
                        'menu-quicktask' => 'index.php?option=' . self::TARGET_COMPONENT . '&task=integrator.edit',
                        'menu-quicktask-title' => 'COM_BREEZINGFORMSNG_MENUS_NEW_INTEGRATION',
                        'menu-quicktask-icon' => 'plus',
                    ],
                ],
                [
                    'COM_BREEZINGFORMSNG_CONFIGURATION',
                    'breezingformsng-configuration',
                    'view=packages',
                    [],
                ],
                ['COM_BREEZINGFORMSNG_ABOUT', 'breezingformsng-about', 'task=about.display&view=about', []],
            ];

            $checked = 0;

            foreach ($items as [$title, $alias, $route, $params]) {
                $link = str_starts_with($route, 'index.php?')
                    ? $route
                    : 'index.php?option=' . self::TARGET_COMPONENT . '&' . $route;

                $db->setQuery(
                    $db->createQuery()
                        ->select($db->quoteName('id'))
                        ->from($db->quoteName('#__menu'))
                        ->where($db->quoteName('client_id') . ' = 1')
                        ->where(
                            '('
                            . $db->quoteName('link') . ' = ' . $db->quote($link)
                            . ' OR ' . $db->quoteName('title') . ' = ' . $db->quote($title)
                            . ')'
                        )
                        ->order($db->quoteName('id') . ' ASC')
                );
                $menuId = (int) $db->loadResult();

                $menu = new Menu($db);

                if ($menuId > 0) {
                    $menu->load($menuId);
                }

                $menu->setLocation($parentId, 'last-child');

                $data = [
                    'menutype' => 'main',
                    'title' => $title,
                    'alias' => $alias,
                    'note' => '',
                    'path' => $parentPath . '/' . $alias,
                    'link' => $link,
                    'type' => 'component',
                    'published' => 1,
                    'parent_id' => $parentId,
                    'level' => 2,
                    'component_id' => $componentId,
                    'checked_out' => 0,
                    'checked_out_time' => null,
                    'browserNav' => 0,
                    'access' => 1,
                    'img' => 'class:component',
                    'template_style_id' => 0,
                    'params' => $params,
                    'home' => 0,
                    'language' => '*',
                    'client_id' => 1,
                ];

                if (!$menu->bind($data) || !$menu->check() || !$menu->store()) {
                    $this->log('Unable to ensure BFNG admin submenu entry ' . $title . '.', Log::WARNING);
                    continue;
                }

                $checked++;
            }

            $query = $db->createQuery()
                ->delete($db->quoteName('#__menu'))
                ->where($db->quoteName('client_id') . ' = 1')
                ->where(
                    $db->quoteName('alias')
                    . ' IN ('
                    . $db->quote('breezingformsng-import-export')
                    . ', '
                    . $db->quote('breezingformsng-packages')
                    . ')'
                );
            $db->setQuery($query)->execute();

            $this->log('BFNG administration submenu entries checked: ' . $checked . ' item(s).');
        } catch (\Throwable $e) {
            $this->log('Unable to ensure BFNG administration submenu entries: ' . $e->getMessage(), Log::WARNING);
        }
    }

    private function migrateLegacyBreezingFormsName(): void
    {
        if ($this->getComponentExtensionId(self::TARGET_COMPONENT) > 0) {
            $this->normalizeJoomlaComponentReferences();
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach (['com_breezingforms', 'breezingforms'] as $legacyElement) {
            $legacyId = $this->getComponentExtensionId($legacyElement);

            if ($legacyId <= 0) {
                continue;
            }

            try {
                $db->setQuery(
                    $db->createQuery()
                        ->update($db->quoteName('#__extensions'))
                        ->set($db->quoteName('element') . ' = ' . $db->quote(self::TARGET_COMPONENT))
                        ->set($db->quoteName('name') . ' = ' . $db->quote(self::TARGET_COMPONENT))
                        ->where($db->quoteName('extension_id') . ' = ' . (int) $legacyId)
                );
                $db->execute();
                $this->log(
                    "Migrated legacy BreezingForms extension row from {$legacyElement} to "
                    . self::TARGET_COMPONENT . '.'
                );
            } catch (\Throwable $e) {
                $this->log(
                    "Unable to migrate legacy BreezingForms extension row {$legacyElement}: " . $e->getMessage(),
                    Log::WARNING
                );
                continue;
            }

            $this->normalizeComponentAssetName($legacyElement, self::TARGET_COMPONENT);
            $this->normalizeJoomlaComponentReferences();
            return;
        }
    }

    private function deduplicateBreezingFormsComponentRows(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $db->setQuery(
                $db->createQuery()
                    ->select($db->quoteName(['extension_id', 'enabled', 'manifest_cache']))
                    ->from($db->quoteName('#__extensions'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                    ->where($db->quoteName('element') . ' = ' . $db->quote(self::TARGET_COMPONENT))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->order($db->quoteName('extension_id') . ' DESC')
            );
            $rows = $db->loadAssocList() ?: [];
        } catch (\Throwable $e) {
            $this->log('Unable to inspect BreezingForms component extension rows: ' . $e->getMessage(), Log::WARNING);
            return;
        }

        if (count($rows) <= 1) {
            return;
        }

        $ids = array_values(array_filter(
            array_map(static fn(array $row): int => (int) ($row['extension_id'] ?? 0), $rows),
            static fn(int $id): bool => $id > 0
        ));

        if (count($ids) <= 1) {
            return;
        }

        $menuRefs = array_fill_keys($ids, 0);

        try {
            $db->setQuery(
                $db->createQuery()
                    ->select([$db->quoteName('component_id'), 'COUNT(1) AS refs'])
                    ->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('client_id') . ' = 1')
                    ->where($db->quoteName('component_id') . ' IN (' . implode(',', $ids) . ')')
                    ->group($db->quoteName('component_id'))
            );
            foreach (($db->loadAssocList() ?: []) as $row) {
                $componentId = (int) ($row['component_id'] ?? 0);
                if ($componentId > 0) {
                    $menuRefs[$componentId] = (int) ($row['refs'] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            $this->log('Unable to inspect BreezingForms menu references: ' . $e->getMessage(), Log::WARNING);
        }

        $keepId = 0;
        $bestScore = [-1, -1, -1, -1];

        foreach ($rows as $row) {
            $id = (int) ($row['extension_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $manifest = json_decode((string) ($row['manifest_cache'] ?? ''), true);
            $isNg = is_array($manifest)
                && stripos(
                    (string) ($manifest['authorUrl'] ?? '') . ' ' . (string) ($manifest['name'] ?? ''),
                    'breezingforms-ng'
                ) !== false ? 1 : 0;
            $enabled = (int) ($row['enabled'] ?? 0);
            $refs = (int) ($menuRefs[$id] ?? 0);
            $score = [$isNg, $enabled, $refs, $id];

            if ($keepId === 0 || $score > $bestScore) {
                $keepId = $id;
                $bestScore = $score;
            }
        }

        if ($keepId <= 0) {
            return;
        }

        $removeIds = array_values(array_filter($ids, static fn(int $id): bool => $id !== $keepId));
        if ($removeIds === []) {
            return;
        }

        try {
            $db->setQuery(
                $db->createQuery()
                    ->update($db->quoteName('#__menu'))
                    ->set($db->quoteName('component_id') . ' = ' . (int) $keepId)
                    ->where($db->quoteName('component_id') . ' IN (' . implode(',', $removeIds) . ')')
            );
            $db->execute();
        } catch (\Throwable $e) {
            $this->log(
                'Unable to remap BreezingForms menu entries to BFNG component row: ' . $e->getMessage(),
                Log::WARNING
            );
        }

        $this->cleanupExtensionReferences($removeIds);

        try {
            $db->setQuery(
                $db->createQuery()
                    ->delete($db->quoteName('#__extensions'))
                    ->where($db->quoteName('extension_id') . ' IN (' . implode(',', $removeIds) . ')')
            );
            $db->execute();
            $this->log(
                'Deduplicated BreezingForms component rows: kept extension_id ' . $keepId
                . ', removed ' . count($removeIds) . ' stale row(s).'
            );
        } catch (\Throwable $e) {
            $this->log(
                'Unable to remove stale BreezingForms component extension rows: ' . $e->getMessage(),
                Log::WARNING
            );
        }
    }

    private function removeLegacyBreezingFormsComponentRows(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $targetId = $this->getComponentExtensionId(self::TARGET_COMPONENT);

        if ($targetId <= 0) {
            $this->log('BFNG component row not found; legacy BreezingForms cleanup skipped.', Log::WARNING);
            return;
        }

        foreach (['breezingforms', 'com_breezingforms'] as $legacyElement) {
            $legacyId = $this->getComponentExtensionId($legacyElement);

            if ($legacyId <= 0) {
                continue;
            }

            try {
                $db->setQuery(
                    $db->createQuery()
                        ->update($db->quoteName('#__menu'))
                        ->set($db->quoteName('component_id') . ' = ' . (int) $targetId)
                        ->set(
                            $db->quoteName('link') . ' = REPLACE(' . $db->quoteName('link')
                            . ', ' . $db->quote('option=' . $legacyElement)
                            . ', ' . $db->quote('option=' . self::TARGET_COMPONENT) . ')'
                        )
                        ->where($db->quoteName('component_id') . ' = ' . (int) $legacyId)
                );
                $db->execute();
            } catch (\Throwable $e) {
                $this->log(
                    "Unable to remap legacy BreezingForms menus for {$legacyElement}: " . $e->getMessage(),
                    Log::WARNING
                );
            }

            $this->cleanupExtensionReferences([$legacyId]);

            try {
                $db->setQuery(
                    $db->createQuery()
                        ->delete($db->quoteName('#__extensions'))
                        ->where($db->quoteName('extension_id') . ' = ' . (int) $legacyId)
                );
                $db->execute();
                $this->log("Legacy BreezingForms component row removed safely: {$legacyElement} (id {$legacyId}).");
            } catch (\Throwable $e) {
                $this->log(
                    "Unable to remove legacy BreezingForms component row {$legacyElement}: " . $e->getMessage(),
                    Log::WARNING
                );
            }
        }
    }

    private function removeLegacyBreezingFormsComponentDirectories(): void
    {
        $directories = [
            JPATH_ADMINISTRATOR . '/components/com_breezingforms',
            JPATH_SITE . '/components/com_breezingforms',
            JPATH_SITE . '/media/com_breezingforms',
            JPATH_ROOT . '/api/components/com_breezingforms',
        ];

        foreach ($directories as $directory) {
            if (!Folder::exists($directory)) {
                continue;
            }

            try {
                if (Folder::delete($directory)) {
                    $this->log('Removed legacy BreezingForms directory: ' . $directory);
                }
            } catch (\Throwable $e) {
                $this->log(
                    'Unable to remove legacy BreezingForms directory ' . $directory . ': ' . $e->getMessage(),
                    Log::WARNING
                );
            }
        }
    }

    private function migrateStaleMenuLinks(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $replacements = [
            'index.php?option=' . self::TARGET_COMPONENT . '&act=about'
                => 'index.php?option=' . self::TARGET_COMPONENT . '&task=about.display&view=about',
            'index.php?option=' . self::TARGET_COMPONENT . '&task=integrate.display&view=integrate'
                => 'index.php?option=' . self::TARGET_COMPONENT . '&view=integrator',
            'index.php?option=' . self::TARGET_COMPONENT . '&act=integrate'
                => 'index.php?option=' . self::TARGET_COMPONENT . '&view=integrator',
            'index.php?option=' . self::TARGET_COMPONENT . '&act=managerecs'
                => 'index.php?option=' . self::TARGET_COMPONENT . '&view=records',
            'index.php?option=' . self::TARGET_COMPONENT . '&act=manageforms'
                => 'index.php?option=' . self::TARGET_COMPONENT . '&view=forms',
        ];

        foreach ($replacements as $oldLink => $newLink) {
            try {
                $db->setQuery(
                    $db->createQuery()
                        ->update($db->quoteName('#__menu'))
                        ->set($db->quoteName('link') . ' = ' . $db->quote($newLink))
                        ->where($db->quoteName('client_id') . ' = 1')
                        ->where($db->quoteName('link') . ' = ' . $db->quote($oldLink))
                );
                $db->execute();
                $updated = (int) $db->getAffectedRows();

                if ($updated > 0) {
                    $this->log("[OK] Migrated {$updated} menu link(s): {$oldLink} → {$newLink}");
                }
            } catch (\Throwable $e) {
                $this->log("Unable to migrate menu link {$oldLink}: " . $e->getMessage(), Log::WARNING);
            }
        }
    }

    /**
     * PDF generation now uses the native tc-lib-pdf API. Customisations
     * written for the former TCPDF API are not executed safely anymore, so
     * list them for the administrator instead of letting the next form
     * submission fail.
     */
    private function reportLegacyPdfCustomisations(): void
    {
        $templateDirectory = JPATH_SITE . '/media/breezingforms/pdftpl';
        $legacyTemplates = [];
        $ignoredTemplates = [];

        foreach (glob($templateDirectory . '/*.php') ?: [] as $path) {
            $name = basename($path);

            if (isset(self::STOCK_GENERIC_PDF_TEMPLATES[$name])) {
                // Unmodified copies are deleted; customised ones are kept for
                // the administrator to move to a supported template.
                $source = rtrim(str_replace("\r\n", "\n", (string) file_get_contents($path)));
                if (in_array(hash('sha256', $source), self::STOCK_GENERIC_PDF_TEMPLATES[$name], true)) {
                    if (File::delete($path)) {
                        $this->log('Unmodified generic PDF template removed: ' . $name);
                        continue;
                    }
                }

                $ignoredTemplates[] = $name;
                continue;
            }

            $source = (string) file_get_contents($path);
            $legacyCall = '/\$(?:this|pdf)\s*->\s*'
                . '(?:[A-Z]\w*|writeHTML\w*|lastPage|getAlias\w+|getPage(?:Height|Width))\s*\(/';
            if (preg_match($legacyCall, $source) === 1) {
                $legacyTemplates[] = $name;
            }
        }

        $legacyFonts = array_map('basename', glob($templateDirectory . '/fonts/*.php') ?: []);

        $warnings = [
            [
                $legacyTemplates,
                'COM_BREEZINGFORMSNG_INSTALLER_WARNING_PDF_LEGACY_TEMPLATES',
                'These PDF templates still call the former TCPDF API and will fail when a PDF is generated. '
                    . 'Adapt them to tc-lib-pdf (page numbers are available in the pdfPageNumber and '
                    . 'pdfPageCount variables): %s',
            ],
            [
                $ignoredTemplates,
                'COM_BREEZINGFORMSNG_INSTALLER_WARNING_PDF_IGNORED_TEMPLATES',
                'These generic PDF templates in media/breezingforms/pdftpl are no longer used and can be '
                    . 'deleted. Move any customisation to a form-specific template, or to '
                    . 'export_custom_header_pdf.php or export_custom_footer_pdf.php for exports: %s',
            ],
            [
                $legacyFonts,
                'COM_BREEZINGFORMSNG_INSTALLER_WARNING_PDF_LEGACY_FONTS',
                'These TCPDF font definitions in media/breezingforms/pdftpl/fonts are no longer loaded and '
                    . 'Verdana is used instead. Provide the fonts as TTF files: %s',
            ],
            [
                $this->findStoredCodeUsingBfpdf(),
                'COM_BREEZINGFORMSNG_INSTALLER_WARNING_PDF_BFPDF_CODE',
                'This stored code uses BFPDF, which now relies on tc-lib-pdf. BFPDF keeps AddPage(), '
                    . 'SetFont(), writeHTML(), writeHTMLCell(), lastPage(), Output(); calls to the other '
                    . 'former TCPDF methods must be adapted: %s',
            ],
        ];

        foreach ($warnings as [$items, $key, $fallback]) {
            if ($items === []) {
                continue;
            }

            sort($items);
            $list = implode(', ', array_map(
                static fn (string $item): string => htmlspecialchars($item, ENT_QUOTES, 'UTF-8'),
                $items
            ));
            $this->log($this->installerText($key, $fallback, $list), Log::WARNING);
        }
    }

    /** @return list<string> */
    private function findStoredCodeUsingBfpdf(): array
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $tables = $db->getTableList();
        $prefix = $db->getPrefix();
        $sources = [
            'facileforms_forms' => [
                'script1code',
                'script2code',
                'piece1code',
                'piece2code',
                'piece3code',
                'piece4code',
            ],
            'facileforms_elements' => ['script1code', 'script2code', 'script3code'],
            'facileforms_scripts' => ['code'],
            'facileforms_pieces' => ['code'],
        ];
        $found = [];

        foreach ($sources as $table => $columns) {
            if (!in_array($prefix . $table, $tables, true)) {
                continue;
            }

            $query = $db->createQuery()
                ->select($db->quoteName(['id', 'name']))
                ->from($db->quoteName('#__' . $table))
                ->where(
                    array_map(
                        static fn (string $column): string => $db->quoteName($column) . ' LIKE '
                            . $db->quote('%BFPDF%'),
                        $columns
                    ),
                    'OR'
                );

            foreach ($db->setQuery($query)->loadObjectList() as $row) {
                $found[] = $table . ' #' . (int) $row->id . ' (' . $row->name . ')';
            }
        }

        return $found;
    }

    private function cleanupLegacyBreezingFormsAfterInstall(): void
    {
        $this->normalizeJoomlaComponentReferences();

        if ($this->ensureAdminMenuRootNodeExists()) {
            $this->ensureAdministrationMainMenuEntry();
            $this->migrateStaleMenuLinks();
            $this->ensureAdministrationSubmenuEntries();
        }
        $this->deduplicateBreezingFormsComponentRows();
        $this->removeLegacyBreezingFormsComponentRows();
        $this->removeLegacyBreezingFormsComponentDirectories();
        $this->removeObsoleteSystemPlugin();
        $this->removeObsoleteComponentFiles();
        $this->log(
            'Legacy BreezingForms component cleanup completed in safe mode; uninstall hooks intentionally skipped.'
        );
    }

    private function removeObsoleteSystemPlugin(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('sysbreezingforms'));
        $extensionId = (int) $db->setQuery($query)->loadResult();

        if ($extensionId > 0) {
            $installer = new Installer();
            $installer->setDatabase($db);
            $installer->uninstall('plugin', $extensionId);
            $this->log('Obsolete sysbreezingforms plugin removed.');
        }
    }

    /**
     * Files shipped by earlier NG releases and replaced since; Joomla does
     * not delete removed package files on update.
     */
    private function removeObsoleteComponentFiles(): void
    {
        $obsolete = [
            JPATH_SITE . '/components/com_breezingformsng/router.php',
            JPATH_SITE . '/components/com_breezingformsng/facileforms.xml.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorUploads.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorCodeTools.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorScripting.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorExports.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorNotifications.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorSubmission.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/processor/bfProcessorRendering.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/Conf.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/tables.php',
            JPATH_SITE . '/components/com_breezingformsng/legacy/functions.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Table/RuntimeTables.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFFactory.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFIntegrate.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFJoomlaConfig.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFPDF.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFRequest.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFText.php',
            JPATH_SITE . '/media/com_breezingformsng/images/site/captcha/securimage_show.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/images/captcha/securimage_show.php',
            // This generic Joomla 3 PDF template is superseded by the
            // packaged administrator template. Keep user-specific templates.
            JPATH_SITE . '/media/breezingforms/pdftpl/export_pdf.php',
            JPATH_SITE . '/media/breezingforms/pdftpl/pdf_attachment.php',
            JPATH_SITE . '/media/breezingforms/downloadtpl/download.php',
            JPATH_SITE . '/media/breezingforms/downloadtpl/sofort_download.php',
            JPATH_SITE . '/media/breezingforms/downloadtpl/sofort_success.php',
            // Sofort was discontinued by Klarna and removed from the component.
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Callback/SofortCallback.php',
            JPATH_SITE . '/components/com_breezingformsng/downloadtpl/sofort_download.php',
            JPATH_SITE . '/components/com_breezingformsng/downloadtpl/sofort_success.php',
            JPATH_SITE . '/media/com_breezingformsng/images/quickmode/icon_sofort.png',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/themes/quickmode/i/icon_sofort.png',
            JPATH_SITE . '/media/breezingforms/downloadtpl/stripe_download.php',
            JPATH_SITE . '/media/breezingforms/downloadtpl/error.php',
            JPATH_SITE . '/language/fr-FR/fr-FR.com_breezingforms.ini',
            JPATH_SITE . '/language/en-GB/en-GB.com_breezingforms.ini',
            JPATH_ADMINISTRATOR . '/language/fr-FR/fr-FR.com_breezingforms.ini',
            JPATH_ADMINISTRATOR . '/language/fr-FR/fr-FR.com_breezingforms.sys.ini',
            JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_breezingforms.sys.ini',
            JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_breezingforms.ini',
            JPATH_SITE . '/components/com_breezingformsng/facileforms.class.php',
            JPATH_SITE . '/components/com_breezingformsng/facileforms.process.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/sql/create_sql.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/Helper/LegacyClassLoader.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/jq.js',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/jq.min.legacy.js',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/jq.min.js',
            JPATH_SITE . '/components/com_breezingformsng/src/Helper/legacy/route.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/Model/LegacyPackageModel.php',
            // Manifest renamed to breezingformsng.xml, the name Joomla resolves
            // for a component (element without its com_ prefix).
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/com_breezingformsng.xml',
            // Files a release shipped and a later one dropped. Joomla leaves them
            // in place on update, since their folder is still in the manifest.
            // Administration classes and layouts replaced by the native MVC.
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/Model/PackageModel.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/Service/PieceManager.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/Service/ScriptManager.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/View/Pieces/Renderer.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/View/Scripts/Renderer.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/tmpl/menus/edit.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/tmpl/records/csvimport.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/tmpl/records/edit.php',
            // Libraries removed from the administration.
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/classes/BFQuickModeMobile.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/crosstec/constants.php',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/jquery.validationEngine-en.js',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/jquery.validationEngine.js',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/jq-ui.min.js',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/plugins/json.js',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/plugins/md5.js',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/tooltip.js',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/validationEngine.jquery.css',
            // Site classes merged or replaced.
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/'
                . 'ContentBuilderChoiceHydrationScriptBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/'
                . 'ContentBuilderEditableRecordScriptBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/'
                . 'ContentBuilderFileHydrationScriptBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/'
                . 'ContentBuilderFileUploadScriptBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/'
                . 'ContentBuilderFlashUploadValidationBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/'
                . 'ContentBuilderFormAssociationLoader.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/ContentBuilderFormDataLoader.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/'
                . 'ContentBuilderReadonlyScriptBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/'
                . 'ContentBuilderSelectHydrationScriptBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/'
                . 'ContentBuilderSignatureScriptBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/'
                . 'ContentBuilderTechnicalFieldsBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/'
                . 'ContentBuilderValueHydrationScriptBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/FormClosingMarkupBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/FormModeFinalizationBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/FormOpeningMarkupBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/FormOptionalContextFieldsBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/QueryListNavigationBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/QueryListPaginationTailBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/QueryListRowsRefreshBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/QuickMode/MobileRenderer.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/QuickMode/QuickModeCheckboxBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/QuickMode/'
                . 'QuickModePaymentImageBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/QuickMode/'
                . 'QuickModeUploadFileAddedHandlerBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Rendering/QuickMode/'
                . 'QuickModeUploadQueueEntryScriptBuilder.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Upload/PaymentCacheCleaner.php',
            JPATH_SITE . '/components/com_breezingformsng/src/Service/Upload/TemporaryUploadFileCleaner.php',
            // Libraries removed from the site.
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/jq.mobile.min.js',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/jquery.validationEngine-en.js',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/jquery.validationEngine.js',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/jq-ui.min.js',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/plupload/Moxie.swf',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/tooltip.css',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/tooltip.js',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/validationEngine.jquery.css',
            JPATH_SITE . '/components/com_breezingformsng/libraries/js/overlib_mini.js',
            JPATH_SITE . '/components/com_breezingformsng/libraries/js/sweetalert.min.js',
            // Theme files no longer shipped.
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/ajax-loader.gif',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/ajax-loader.png',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/cancel.png',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/img/icon_info_old.png',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/jq.mobile.1.4.5.icons.min.css',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/jq.mobile.1.4.5.min.css',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/jq.mobile.min.css',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/mobile-system.css',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/system.ie6.css',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/system.ie7.css',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/system.ie.css',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/upload.png',
            // Media files no longer shipped.
            JPATH_SITE . '/media/com_breezingformsng/images/site/captcha/AHGBold.ttf',
            JPATH_SITE . '/media/com_breezingformsng/images/site/captcha/LICENSE.txt',
            JPATH_SITE . '/media/com_breezingformsng/images/site/captcha/README.FONT.txt',
            JPATH_SITE . '/media/com_breezingformsng/images/site/captcha/securimage.php',
            JPATH_SITE . '/media/com_breezingformsng/images/site/captcha/words/words.txt',
            JPATH_SITE . '/media/com_breezingformsng/js/admin/admin-sort.js',
            JPATH_SITE . '/media/com_breezingformsng/js/admin/pieces-list.js',
            JPATH_SITE . '/media/com_breezingformsng/js/admin/scripts-list.js',
            JPATH_SITE . '/media/com_breezingformsng/js/site/quickmode-calendar-mobile.js',
            JPATH_SITE . '/media/com_breezingformsng/js/site/quickmode-core-helpers-mobile.js',
            JPATH_SITE . '/media/com_breezingformsng/js/site/quickmode-flash-upload-mobile.js',
            JPATH_SITE . '/media/com_breezingformsng/js/site/quickmode-post-init-mobile.js',
            JPATH_SITE . '/media/com_breezingformsng/js/site/quickmode-recaptcha-invisible-mobile.js',
        ];

        foreach ($obsolete as $file) {
            if (File::exists($file) && File::delete($file)) {
                $this->log('Obsolete file removed: ' . basename($file));
            }
        }

        $obsoleteDirectories = [
            // Composer no longer installs the TCPDF compatibility package.
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/vendor/tecnickcom/tcpdf',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/dropbox',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/mailchimp',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/recaptcha',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/salesforce',
            // jsTree 0.9.8 + its bundled jQuery 1.3.2 clone, replaced by the
            // native jsTree 3.x vendored under libraries/jquery/jstree3.
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/jtree',
            // Unused CodeMirror 5 modes; the forms use Joomla's CodeMirror editor.
            JPATH_SITE . '/components/com_breezingformsng/libraries/codemirror',
            // Chunked uploads are staged in Joomla's temporary folder and the
            // QuickMode save cache lives under media/breezingforms.
            JPATH_SITE . '/components/com_breezingformsng/uploads',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/ajax_cache',
            // Folders a release shipped and a later one dropped. The six classic
            // skins formerly under themes/quickmode are left alone: a site may
            // have customised them before they moved to media/breezingforms/themes.
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/jquery/themes/easymode',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/securimage',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/libraries/wz_dragdrop',
            JPATH_ADMINISTRATOR . '/components/com_breezingformsng/plugins/sysbreezingforms',
            JPATH_SITE . '/components/com_breezingformsng/legacy',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/jtable',
            JPATH_SITE . '/components/com_breezingformsng/libraries/jquery/ladda',
            JPATH_SITE . '/components/com_breezingformsng/src/Helper',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode-bootstrap4',
            JPATH_SITE . '/components/com_breezingformsng/themes/quickmode/images',
        ];

        foreach ($obsoleteDirectories as $directory) {
            if (Folder::exists($directory) && Folder::delete($directory)) {
                $this->log('Obsolete directory removed: ' . basename($directory));
            }
        }
    }


    /**
     * method to install the component
     *
     * @return bool
     */
    public function install(InstallerAdapter $adapter): bool
    {
        $this->log('Fresh installation of BreezingForms.');

        return true;
    }
    /**
     * method to update the component
     *
     * @return bool
     */
    public function update(InstallerAdapter $adapter): bool
    {
        $this->log('Updating BreezingForms from version ' . $this->getCurrentInstalledVersion());
        $this->ensureUtf8mb4Columns();
        $this->migrateLegacyComponentOptionReferences();

        $this->migrateDatabase();
        $this->convertRecordModifiedDatesToUtc();

        return true;
    }

    private function getInstalledSchemaVersion(): string
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $component = self::TARGET_COMPONENT;
        $query = $db->createQuery()
            ->select($db->quoteName('schemas.version_id'))
            ->from($db->quoteName('#__schemas', 'schemas'))
            ->join(
                'INNER',
                $db->quoteName('#__extensions', 'extensions'),
                'extensions.extension_id = schemas.extension_id'
            )
            ->where($db->quoteName('extensions.type') . ' = ' . $db->quote('component'))
            ->where($db->quoteName('extensions.element') . ' = :element')
            ->bind(':element', $component, ParameterType::STRING);

        return (string) $db->setQuery($query)->loadResult();
    }

    /**
     * Releases up to 6.1.0-RC07 stored the modification date of a record in
     * the site time zone, while its submission date has always been UTC. Both
     * are UTC now; convert the stored values once, on the update that brings
     * the 6.1.2-2026-10-23 schema version.
     */
    private function convertRecordModifiedDatesToUtc(): void
    {
        if (
            $this->schemaVersionBeforeUpdate === ''
            || version_compare($this->schemaVersionBeforeUpdate, '6.1.2-2026-10-23', '>=')
        ) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $siteTimezone = new \DateTimeZone((string) Factory::getApplication()->get('offset', 'UTC'));
        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'modified']))
            ->from($db->quoteName('#__facileforms_records'))
            ->where($db->quoteName('modified') . ' IS NOT NULL');
        $rows = (array) $db->setQuery($query)->loadObjectList();

        foreach ($rows as $row) {
            $id = (int) $row->id;
            $modified = (new \Joomla\CMS\Date\Date((string) $row->modified, $siteTimezone))->toSql();
            $update = $db->createQuery()
                ->update($db->quoteName('#__facileforms_records'))
                ->set($db->quoteName('modified') . ' = :modified')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':modified', $modified, ParameterType::STRING)
                ->bind(':id', $id, ParameterType::INTEGER);
            $db->setQuery($update)->execute();
        }

        if ($rows !== []) {
            $this->log(
                'Converted the modification date of ' . count($rows) . ' record(s) from '
                . $siteTimezone->getName() . ' to UTC.'
            );
        }
    }

    private function migrateDatabase(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $textCollationClause = $this->getTextCollationClause();
        $auditCollation = $this->utf8mb4Supported ? $this->utf8mb4Collation : null;
        $tables = self::getTableFields($db->getTableList());
        $prefix = $db->getPrefix();

        $recordsTable = $prefix . 'facileforms_records';
        $formsTable   = $prefix . 'facileforms_forms';
        $scriptsTable = $prefix . 'facileforms_scripts';
        $piecesTable  = $prefix . 'facileforms_pieces';
        $packagesTable = $prefix . 'facileforms_packages';

        foreach ([$recordsTable, $formsTable, $scriptsTable, $piecesTable, $packagesTable] as $tableName) {
            if (isset($tables[$tableName])) {
                $tables[$tableName] = $db->setQuery(
                    'SHOW FULL COLUMNS FROM ' . $db->quoteName($tableName)
                )->loadObjectList('Field');
            }
        }

        if (isset($tables[$packagesTable])) {
            $columns = $tables[$packagesTable];

            // The creation date was free text: every value that is no real
            // date (empty, malformed, 2024-02-31...) becomes NULL before the
            // column turns into a DATETIME, so the conversion cannot fail.
            if (
                isset($columns['created'])
                && !self::columnDefinitionMatches($columns['created'], 'datetime', true, null)
            ) {
                $db->setQuery(
                    "ALTER TABLE `{$packagesTable}` MODIFY `created` VARCHAR(20) NULL DEFAULT NULL"
                )->execute();
                $rows = $db->setQuery(
                    "SELECT `id`, `created` FROM `{$packagesTable}` WHERE `created` IS NOT NULL"
                )->loadObjectList();

                foreach ($rows as $row) {
                    $created = self::normaliseSqlDate((string) $row->created);

                    if ($created !== $row->created) {
                        $packageId = (string) $row->id;
                        $query = $db->createQuery()
                            ->update($db->quoteName($packagesTable))
                            ->set($db->quoteName('created') . ' = ' . ($created === null ? 'NULL' : ':created'))
                            ->where($db->quoteName('id') . ' = :id')
                            ->bind(':id', $packageId, ParameterType::STRING);

                        if ($created !== null) {
                            $query->bind(':created', $created, ParameterType::STRING);
                        }

                        $db->setQuery($query)->execute();
                    }
                }

                $db->setQuery("ALTER TABLE `{$packagesTable}` MODIFY `created` DATETIME NULL DEFAULT NULL")->execute();
                $this->log('Updated created column definition in facileforms_packages.');
            }

            $packageAuditColumns = [
                'created_by'  => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `created`",
                'modified'    => "DATETIME NULL DEFAULT NULL AFTER `created_by`",
                'modified_by' => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `modified`",
            ];

            foreach ($packageAuditColumns as $col => $def) {
                if (!isset($columns[$col])) {
                    $db->setQuery("ALTER TABLE `{$packagesTable}` ADD `{$col}` {$def}")->execute();
                    $this->log("Added column {$col} to facileforms_packages.");
                }
            }
        }

        if (isset($tables[$recordsTable])) {
            $columns = $tables[$recordsTable];

            $recordAuditColumns = [
                'modified'        => "DATETIME NULL DEFAULT NULL AFTER `submitted`",
                'modified_by'     => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `modified`",
                'modified_user_id' => "INT(11) NOT NULL DEFAULT '0' AFTER `modified_by`",
            ];

            foreach ($recordAuditColumns as $col => $def) {
                if (!isset($columns[$col])) {
                    $db->setQuery("ALTER TABLE `{$recordsTable}` ADD `{$col}` {$def}")->execute();
                    $this->log("Added column {$col} to facileforms_records.");
                }
            }

            $newColumns = [
                'opted'     => "TINYINT(1) NOT NULL DEFAULT '0' AFTER `paypal_download_tries`",
                'opt_ip'    => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `opted`",
                'opt_date'  => "DATETIME NULL DEFAULT NULL AFTER `opt_ip`",
                'opt_token' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER `opt_date`",
            ];

            foreach ($newColumns as $col => $def) {
                if (!isset($columns[$col])) {
                    $db->setQuery("ALTER TABLE `{$recordsTable}` ADD `{$col}` {$def}, ADD INDEX (`{$col}`)")->execute();
                    $this->log("Added column {$col} to facileforms_records.");
                }
            }

            if (
                isset($columns['opt_date'])
                && !self::columnDefinitionMatches($columns['opt_date'], 'datetime', true, null)
            ) {
                $db->setQuery("ALTER TABLE `{$recordsTable}` MODIFY `opt_date` DATETIME NULL DEFAULT NULL")->execute();
                $this->log('Updated opt_date column definition in facileforms_records.');
            }

            if (isset($columns['ip'])) {
                if (!self::columnDefinitionMatches($columns['ip'], 'varchar(45)', false, '')) {
                    $db->setQuery(
                        "ALTER TABLE `{$recordsTable}` MODIFY `ip` VARCHAR(45) NOT NULL DEFAULT ''"
                    )->execute();
                    $this->log('Updated ip column definition in facileforms_records.');
                }
            }

            if (isset($columns['browser'])) {
                $browserType = strtolower((string) $columns['browser']->Type);
                if (strpos($browserType, 'text') === false) {
                    $db->setQuery("ALTER TABLE `{$recordsTable}` MODIFY `browser` TEXT NOT NULL")->execute();
                    $this->log('Updated browser column definition in facileforms_records.');
                }
            }

            // The archived flag (0/1) became the Joomla publication state:
            // 1 published, 2 archived, -2 trashed. Each step can be replayed.
            if (!isset($columns['state'])) {
                $db->setQuery(
                    "ALTER TABLE `{$recordsTable}` ADD `state` TINYINT NOT NULL DEFAULT '1' AFTER `exported`,"
                    . " ADD INDEX (`state`)"
                )->execute();
                $this->log('Added column state to facileforms_records.');
            }

            if (isset($columns['archived'])) {
                $db->setQuery(
                    "UPDATE `{$recordsTable}` SET `state` = 2 WHERE `archived` = 1 AND `state` = 1"
                )->execute();
                $db->setQuery("ALTER TABLE `{$recordsTable}` DROP `archived`")->execute();
                $this->log('Moved the archived flag of facileforms_records to the state column.');
            }
        }

        if (isset($tables[$formsTable])) {
            $columns = $tables[$formsTable];

            if (!isset($columns['debug_mode'])) {
                $db->setQuery(
                    "ALTER TABLE `{$formsTable}` ADD `debug_mode` TINYINT(1) NOT NULL DEFAULT '0' AFTER `published`"
                )->execute();
                $this->log('Added column debug_mode to facileforms_forms.');
            }

            if (!isset($columns['version'])) {
                $db->setQuery(
                    "ALTER TABLE `{$formsTable}` ADD `version` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `debug_mode`"
                )->execute();
                $this->log('Added column version to facileforms_forms.');
            }

            $newFormColumns = [
                'double_opt' => "TINYINT(1) NOT NULL DEFAULT '0' AFTER `filter_state`",
                'opt_mail'   => "VARCHAR(128) NOT NULL DEFAULT '' AFTER `double_opt`",
            ];

            foreach ($newFormColumns as $col => $def) {
                if (!isset($columns[$col])) {
                    $db->setQuery("ALTER TABLE `{$formsTable}` ADD `{$col}` {$def}, ADD INDEX (`{$col}`)")->execute();
                    $this->log("Added column {$col} to facileforms_forms.");
                }
            }

            $auditFormColumns = [
                'created'     => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER `filter_state`",
                'modified'    => "DATETIME NULL DEFAULT NULL AFTER `created`",
                'created_by'  => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `modified`",
                'modified_by' => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `created_by`"
            ];

            foreach ($auditFormColumns as $col => $def) {
                if (!isset($columns[$col])) {
                    $db->setQuery("ALTER TABLE `{$formsTable}` ADD `{$col}` {$def}")->execute();
                    $this->log("Added column {$col} to facileforms_forms.");
                }
            }

            if (
                isset($columns['created_by'])
                && !self::columnDefinitionMatches($columns['created_by'], 'varchar(255)', false, '', $auditCollation)
            ) {
                $db->setQuery(
                    "ALTER TABLE `{$formsTable}` MODIFY `created_by` VARCHAR(255){$textCollationClause}"
                    . " NOT NULL DEFAULT ''"
                )->execute();
                $this->log("Updated created_by column definition in facileforms_forms.");
            }
            if (
                isset($columns['modified_by'])
                && !self::columnDefinitionMatches($columns['modified_by'], 'varchar(255)', false, '', $auditCollation)
            ) {
                $db->setQuery(
                    "ALTER TABLE `{$formsTable}` MODIFY `modified_by` VARCHAR(255){$textCollationClause}"
                    . " NOT NULL DEFAULT ''"
                )->execute();
                $this->log("Updated modified_by column definition in facileforms_forms.");
            }
        }

        $auditColumns = [
            'created'     => "DATETIME NULL DEFAULT NULL AFTER `code`",
            'created_by'  => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `created`",
            'modified'    => "DATETIME NULL DEFAULT NULL AFTER `created_by`",
            'modified_by' => "VARCHAR(255){$textCollationClause} NOT NULL DEFAULT '' AFTER `modified`",
        ];

        if (isset($tables[$scriptsTable])) {
            $columns = $tables[$scriptsTable];

            if (!isset($columns['unit_tests'])) {
                $db->setQuery("ALTER TABLE `{$scriptsTable}` ADD `unit_tests` LONGTEXT NULL AFTER `code`")->execute();
                $this->log('Added column unit_tests to facileforms_scripts.');
            }
        }

        if (isset($tables[$piecesTable])) {
            $columns = $tables[$piecesTable];

            if (!isset($columns['unit_tests'])) {
                $db->setQuery("ALTER TABLE `{$piecesTable}` ADD `unit_tests` LONGTEXT NULL AFTER `code`")->execute();
                $this->log('Added column unit_tests to facileforms_pieces.');
            }
        }

        // Joomla check-out of the items while they are edited.
        foreach (
            [$formsTable, $prefix . 'facileforms_compmenus', $scriptsTable, $piecesTable, $recordsTable] as $tableName
        ) {
            if (!isset($tables[$tableName]) || isset($tables[$tableName]['checked_out'])) {
                continue;
            }

            $db->setQuery(
                "ALTER TABLE `{$tableName}` ADD `checked_out` INT UNSIGNED NULL DEFAULT NULL,"
                . " ADD `checked_out_time` DATETIME NULL DEFAULT NULL, ADD INDEX `idx_checkout` (`checked_out`)"
            )->execute();
            $this->log("Added the check-out columns to {$tableName}.");
        }

        foreach ([$scriptsTable, $piecesTable] as $tableName) {
            if (!isset($tables[$tableName])) {
                continue;
            }

            $columns = $tables[$tableName];
            foreach ($auditColumns as $col => $def) {
                if (!isset($columns[$col])) {
                    $db->setQuery("ALTER TABLE `{$tableName}` ADD `{$col}` {$def}")->execute();
                    $this->log("Added column {$col} to {$tableName}.");
                }
            }

            if (
                isset($columns['created_by'])
                && !self::columnDefinitionMatches($columns['created_by'], 'varchar(255)', false, '', $auditCollation)
            ) {
                $db->setQuery(
                    "ALTER TABLE `{$tableName}` MODIFY `created_by` VARCHAR(255){$textCollationClause}"
                    . " NOT NULL DEFAULT ''"
                )->execute();
                $this->log("Updated created_by column definition in {$tableName}.");
            }
            if (
                isset($columns['modified_by'])
                && !self::columnDefinitionMatches($columns['modified_by'], 'varchar(255)', false, '', $auditCollation)
            ) {
                $db->setQuery(
                    "ALTER TABLE `{$tableName}` MODIFY `modified_by` VARCHAR(255){$textCollationClause}"
                    . " NOT NULL DEFAULT ''"
                )->execute();
                $this->log("Updated modified_by column definition in {$tableName}.");
            }
        }

        $this->migrateElementData1FunctionNames();
        $this->migrateQuickmodeFunctionNames();
        $this->migrateQuickmodeIconPaths();

        $this->log('BreezingForms database update completed.');
    }

    /**
     * Same check as the component's SqlDate service, which the installer
     * cannot rely on loading: "Y-m-d H:i:s" for a real date, else null.
     */
    private static function normaliseSqlDate(string $value): ?string
    {
        $value = trim($value);

        foreach (['Y-m-d H:i:s', 'Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);

            if ($date !== false && $date->format($format) === $value && (int) $date->format('Y') >= 1000) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    /**
     * @param \stdClass $column
     */
    private static function columnDefinitionMatches(
        object $column,
        string $type,
        bool $nullable,
        ?string $default,
        ?string $collation = null
    ): bool {
        return strtolower($column->Type) === $type
            && ($column->Null === 'YES') === $nullable
            && $column->Default === $default
            && ($collation === null || $column->Collation === $collation);
    }

    private function migrateQuickmodeIconPaths(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $formsTable = $db->getPrefix() . 'facileforms_forms';

        if (!in_array($formsTable, $db->getTableList(), true)) {
            return;
        }

        $db->setQuery(
            "SELECT `id`, `template_code` FROM `{$formsTable}`" .
            " WHERE `template_code` IS NOT NULL AND `template_code` != ''"
        );
        $rows = $db->loadObjectList();

        if (empty($rows)) {
            return;
        }

        $updated = 0;

        foreach ($rows as $row) {
            $json = base64_decode((string) $row->template_code, true);

            if ($json === false || $json === '') {
                continue;
            }

            // Replace any full path ending with /icon_*.png by just the filename.
            // Works regardless of component name (com_breezingforms, com_facileforms, etc.)
            // or URL format (absolute, relative, with or without protocol/host).
            $fixed = preg_replace(
                '/"icon":"[^"]*\/(icon_[^"\/]+\.png)"/',
                '"icon":"$1"',
                $json
            );

            if ($fixed === null || $fixed === $json) {
                continue;
            }

            try {
                $db->setQuery(
                    "UPDATE `{$formsTable}` SET `template_code` = " .
                    $db->quote(base64_encode($fixed)) .
                    " WHERE `id` = " . (int) $row->id
                )->execute();
                $updated++;
            } catch (\Throwable $e) {
                $this->log(
                    "Unable to migrate quickmode icon paths for form {$row->id}: " . $e->getMessage(),
                    Log::WARNING
                );
            }
        }

        if ($updated > 0) {
            $this->announce("[OK] Migrated quickmode icon paths in {$updated} form(s).", 'message', Log::INFO);
        } else {
            $this->log('Quickmode icon paths: no migration needed.');
        }
    }

    /**
     * QuickMode rebuilds the elements of a form from its template code on
     * every save: the gn_vcmb_ to gn_get_vcmb_ renaming of the element
     * fields has to be made there too, or the next save brings the old
     * function names back.
     */
    private function migrateQuickmodeFunctionNames(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $formsTable = $db->getPrefix() . 'facileforms_forms';

        if (!in_array($formsTable, $db->getTableList(), true)) {
            return;
        }

        $db->setQuery(
            "SELECT `id`, `template_code` FROM `{$formsTable}`" .
            " WHERE `template_code` IS NOT NULL AND `template_code` != ''"
        );
        $updated = 0;

        foreach ($db->loadObjectList() as $row) {
            $json = base64_decode((string) $row->template_code, true);

            if ($json === false || !str_contains($json, 'gn_vcmb_')) {
                continue;
            }

            try {
                $db->setQuery(
                    "UPDATE `{$formsTable}` SET `template_code` = " .
                    $db->quote(base64_encode(str_replace('gn_vcmb_', 'gn_get_vcmb_', $json))) .
                    " WHERE `id` = " . (int) $row->id
                )->execute();
                $updated++;
            } catch (\Throwable $e) {
                $this->log(
                    "Unable to migrate gn_vcmb_ function names in the quickmode code of form {$row->id}: "
                    . $e->getMessage(),
                    Log::WARNING
                );
            }
        }

        try {
            $db->setQuery(
                "UPDATE `{$formsTable}` SET `template_areas` = REPLACE(`template_areas`, 'gn_vcmb_', 'gn_get_vcmb_')" .
                " WHERE `template_areas` LIKE '%gn\\_vcmb\\_%'"
            )->execute();
        } catch (\Throwable $e) {
            $this->log(
                'Unable to migrate gn_vcmb_ function names in the quickmode areas: ' . $e->getMessage(),
                Log::WARNING
            );
        }

        if ($updated > 0) {
            $this->announce(
                "[OK] Migrated gn_vcmb_* → gn_get_vcmb_* in the quickmode code of {$updated} form(s).",
                'message',
                Log::INFO
            );
        }
    }

    private function migrateElementData1FunctionNames(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $elementsTable = $db->getPrefix() . 'facileforms_elements';

        if (!in_array($elementsTable, $db->getTableList(), true)) {
            return;
        }

        $existingColumns = array_keys($db->getTableColumns($elementsTable, true));
        $candidates = ['data1', 'data2', 'data3', 'data4', 'data5',
                       'data6', 'data7', 'data8', 'data9', 'data10'];
        $columns = array_intersect($candidates, $existingColumns);

        if (empty($columns)) {
            return;
        }

        $totalUpdated = 0;

        foreach ($columns as $col) {
            try {
                $db->setQuery(
                    "UPDATE `{$elementsTable}`" .
                    " SET `{$col}` = REPLACE(`{$col}`, 'gn_vcmb_', 'gn_get_vcmb_')" .
                    " WHERE `{$col}` LIKE '%gn\\_vcmb\\_%'"
                )->execute();

                $updated = (int) $db->getAffectedRows();
                $totalUpdated += $updated;

                if ($updated > 0) {
                    $this->log("Migrated gn_vcmb_ → gn_get_vcmb_ in {$col}: {$updated} element(s) updated.");
                }
            } catch (\Throwable $e) {
                $this->log("Unable to migrate gn_vcmb_ function names in {$col}: " . $e->getMessage(), Log::WARNING);
            }
        }

        if ($totalUpdated > 0) {
            $this->announce(
                "[OK] Migrated {$totalUpdated} element field(s): gn_vcmb_* → gn_get_vcmb_*.",
                'message',
                Log::INFO
            );
        } else {
            $this->log('No gn_vcmb_ function references found in element fields – migration skipped.');
        }
    }

    /**
     * method to uninstall the component
     *
     * @return bool
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $installer = new Installer();
        $installer->setDatabase($db);

        foreach ($this->getPlugins() as $folder => $plugins) {
            foreach ($plugins as $plugin) {
                $query = $db->createQuery()
                    ->select($db->quoteName('extension_id'))
                    ->from($db->quoteName('#__extensions'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                    ->where($db->quoteName('element') . ' = ' . $db->quote($plugin))
                    ->where($db->quoteName('folder') . ' = ' . $db->quote($folder));
                $id = (int) $db->setQuery($query)->loadResult();

                if ($id > 0) {
                    $installer->uninstall('plugin', $id);
                }
            }
        }

        $query = $db->createQuery()
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('module'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('mod_breezingforms'))
            ->where($db->quoteName('client_id') . ' = 0');
        $moduleId = (int) $db->setQuery($query)->loadResult();

        if ($moduleId > 0) {
            $installer->uninstall('module', $moduleId);
        }

        $this->cleanupOldConfig();
        $this->cleanupComponentLogFile();
        $this->removeBreezingFormsMenuEntries();
        $this->log('BreezingForms uninstallation completed.');

        return true;
    }

    /**
     * method to run before an install/update/uninstall method
     *
     * @return bool
     */

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        $this->resetCriticalFailures();

        $type = strtolower((string) $type);
        $this->incomingPackageVersion = $this->getIncomingVersion($adapter);

        if (!$this->checkRequirements()) {
            return false;
        }

        if (in_array($type, ['install', 'update', 'discover_install'], true)) {
            if (!$this->checkInstalledFilePermissionsBeforeCopy()) {
                return false;
            }

            $this->purgeCaches('preflight:' . $type);
            $this->migrateLegacyBreezingFormsName();
        }

        $this->currentInstalledVersion = $this->getCurrentInstalledVersion();

        if ($type === 'update') {
            $this->schemaVersionBeforeUpdate = $this->getInstalledSchemaVersion();
        }

        $this->announce(
            '[INFO] BreezingForms ' . strtoupper($type) .
            ' | package version: <strong>' . htmlspecialchars(
                $this->incomingPackageVersion !== '' ? $this->incomingPackageVersion : 'unknown',
                ENT_QUOTES,
                'UTF-8'
            ) . '</strong>' .
            ' | installed version: <strong>'
            . htmlspecialchars($this->currentInstalledVersion, ENT_QUOTES, 'UTF-8') . '</strong>.',
            'message',
            Log::INFO
        );
        $this->announce(
            '[INFO] Environment | Joomla <strong>'
            . htmlspecialchars(defined('JVERSION') ? JVERSION : 'unknown', ENT_QUOTES, 'UTF-8') . '</strong>' .
            ' | PHP <strong>' . htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') . '</strong>' .
            ' | DB <strong>' . htmlspecialchars($this->getDatabaseRuntimeLabel(), ENT_QUOTES, 'UTF-8') . '</strong>.',
            'message',
            Log::INFO
        );
        $this->log("Preflight executed for action: {$type}");

        if (in_array($type, ['install', 'update', 'discover_install'], true)) {
            $this->announceUtf8mb4Capability();
        }

        return !$this->hasCriticalFailure();
    }
    /**
     * method to run after an install/update/uninstall method
     *
     * @return bool
     */
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        $type = strtolower((string) $type);

        // === LOG POUR DÉBOGAGE ===
        $this->log('Postflight installation method call, parameter : ' . $type . '.');
        $this->log('Current version in manifest_cache : ' . $this->getCurrentInstalledVersion() . '.');

        if (in_array($type, ['install', 'update', 'discover_install'], true)) {
            $this->ensureUtf8mb4Columns();
            // update() already calls this for a genuine extension update, but a
            // fresh install()/discover_install can still run over facileforms_*
            // tables preserved from a pre-NG install (CREATE TABLE IF NOT EXISTS
            // never drops them), so it needs the same cleanup. Idempotent - only
            // rewrites rows that still contain the literal old reference.
            $this->migrateLegacyComponentOptionReferences();
            // A first NG installation can reuse BF Pro tables. The install SQL
            // preserves them, so apply the same migrations as an NG update.
            if ($type !== 'update') {
                $this->migrateDatabase();
            }
            $this->migrateLegacyConfig();
            // The forms, scripts and pieces taken over keep their state as
            // their version of origin before the standard library updates them.
            $this->registerContentTypes();
            $this->setVersionHistoryDefaults();
            $this->keepVersionOrigins();
            $this->importStandardLibrary();
            $this->copyComponentImageAssets();
            $this->installPlugins();
            $this->installModules();
            $this->registerActionLogExtension();
            $this->cleanupLegacyBreezingFormsAfterInstall();
            $this->reportLegacyPdfCustomisations();
            $this->removeOldUpdateSite();
            $this->cleanupOldConfig();
            $this->purgeCaches('postflight:' . $type);
            $this->verifyInstalledExtensionConsistency($adapter);
        } elseif ($type === 'uninstall') {
            $this->removeBreezingFormsMenuEntries();
            $this->unregisterActionLogExtension();
            $this->unregisterContentTypes();
            $this->cleanupOldConfig();
            $this->cleanupComponentLogFile();
            $this->purgeCaches('postflight:' . $type);
        }

        $durationSeconds = max(0.0, microtime(true) - $this->installStartedAt);
        $actionLabel = strtoupper((string) $type);
        $packageVersion = $this->incomingPackageVersion !== ''
            ? $this->incomingPackageVersion
            : $this->getIncomingVersion($adapter);
        $installedVersion = $this->getCurrentInstalledVersion();

        $finishedWithFailure = $this->hasCriticalFailure();
        $finishMessage = ($finishedWithFailure ? '[ERROR] BreezingForms ' : '[OK] BreezingForms ')
            . $actionLabel
            . ($finishedWithFailure ? ' finished with critical issue(s).' : ' finished successfully.')
            . ' Package version: <strong>'
            . htmlspecialchars($packageVersion !== '' ? $packageVersion : 'unknown', ENT_QUOTES, 'UTF-8') . '</strong>'
            . ' | installed version: <strong>' . htmlspecialchars($installedVersion, ENT_QUOTES, 'UTF-8') . '</strong>'
            . ' | duration: <strong>' . number_format($durationSeconds, 2, '.', '') . 's</strong>.';

        $this->announce(
            $finishMessage,
            $finishedWithFailure ? 'error' : 'message',
            $finishedWithFailure ? Log::ERROR : Log::INFO
        );

        if ($finishedWithFailure) {
            $summary = $this->getCriticalFailureSummary();
            $this->log(
                'BreezingForms installation/update process finished with critical issue(s)'
                . ($summary !== '' ? ': ' . $summary : '.'),
                Log::ERROR
            );

            // Actually fail the installer instead of silently reporting success -
            // logging alone left Joomla showing "Installation Successful" even
            // when a critical step failed.
            throw new \RuntimeException(
                'BreezingForms NG postflight failed' . ($summary !== '' ? ': ' . $summary : '.')
            );
        }

        $this->log('BreezingForms installation/update process finished successfully.');

        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    private function getPlugins(): array
    {
        $plugins = array();
        $plugins['system'] = array();
        $plugins['system'][] = 'bfcompat';
        $plugins['content'] = ['breezingforms'];
        $plugins['privacy'] = ['breezingformsng'];
        return $plugins;
    }

    /**
     * @param string|array<string> $tables
     * @param bool $typeOnly
     * @return array<string, array<string, mixed>>
     */
    public static function getTableFields($tables, $typeOnly = true)
    {
        $results = array();
        settype($tables, 'array');

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        foreach ($tables as $table) {
            $results[$table] = $db->getTableColumns($table, $typeOnly);
        }

        return $results;
    }
}
