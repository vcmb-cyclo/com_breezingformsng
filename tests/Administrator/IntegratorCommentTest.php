<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The comment of an Integrator rule and the toolbar that saves it.
 */
final class IntegratorCommentTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../';
    private const ADMIN = self::ROOT . 'administrator/components/com_breezingformsng/';

    private function read(string $path): string
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        return $source;
    }

    public function testNewAndUpgradedSitesGetTheColumn(): void
    {
        $sql = $this->read(self::ADMIN . 'sql/install.mysql.utf8.sql');
        self::assertSame(1, preg_match('/integrator_rules` \((.*?)\) ENGINE/s', $sql, $table));
        self::assertStringContainsString('`comment` text,', $table[1]);

        $script = $this->read(self::ROOT . 'script.php');
        self::assertStringContainsString("!isset(\$tables[\$rulesTable]['comment'])", $script);
        self::assertStringContainsString('ADD `comment` TEXT', $script);
    }

    public function testTheCommentIsEscapedAndNotAListColumn(): void
    {
        $edit = $this->read(self::ADMIN . 'tmpl/integrator/edit.php');

        self::assertStringContainsString("htmlspecialchars(\$rule->comment ?? '')", $edit);
        self::assertStringNotContainsString('comment', $this->read(self::ADMIN . 'tmpl/integrator/default.php'));
    }

    public function testTheToolbarSavesTheCommentAndTheFinalizeCodeAsOneVersion(): void
    {
        $view = $this->read(self::ADMIN . 'src/View/Integrator/HtmlView.php');

        self::assertMatchesRegularExpression(
            "/apply\('integrator\.apply'\).*save\('integrator\.save'\).*versions\(.*cancel\('integrator\.display'.*addItemPagination\(/s",
            $view
        );

        $model = $this->read(self::ADMIN . 'src/Model/IntegratorModel.php');
        self::assertSame(1, preg_match('/function saveDetails\(.*?\n    \}\n/s', $model, $method));
        // An absent code leaves the stored one.
        self::assertStringContainsString('if ($finalizeCode !== null)', $method[0]);
        self::assertSame(1, substr_count($method[0], 'touchRule('));

        $script = $this->read(self::ROOT . 'media/com_breezingformsng/js/admin/integrator-edit.js');
        self::assertStringContainsString('field.disabled = false', $script);
    }

    public function testTheElementsOfTheTablesShowTheirFadedTypeIcon(): void
    {
        $edit = $this->read(self::ADMIN . 'tmpl/integrator/edit.php');

        self::assertStringContainsString('$typeLabelOf($item->element_type)', $edit);
        self::assertStringContainsString('$typeLabelOf($crit->element_type)', $edit);
        self::assertMatchesRegularExpression(
            '/\.bfng-type-label::before \{[^}]*opacity: \.5;/',
            $this->read(self::ROOT . 'media/com_breezingformsng/css/bfng-admin.css')
        );
    }

    /** @return iterable<string, array{string}> */
    public static function languages(): iterable
    {
        foreach (['de-DE', 'en-GB', 'es-ES', 'fr-FR', 'hu-HU', 'it-IT', 'nl-NL', 'tr-TR'] as $tag) {
            yield $tag => [$tag];
        }
    }

    #[DataProvider('languages')]
    public function testTheTextsAreTranslated(string $tag): void
    {
        $strings = parse_ini_file(self::ADMIN . 'language/' . $tag . '/com_breezingformsng.ini');

        self::assertIsArray($strings);
        self::assertNotEmpty($strings['COM_BREEZINGFORMSNG_INTEGRATOR_COMMENT'] ?? '');
        self::assertNotEmpty($strings['COM_BREEZINGFORMSNG_INTEGRATOR_COMMENT_HINT'] ?? '');
    }
}
