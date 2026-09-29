<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * script.php can't be require()'d directly in PHPUnit (it dies immediately
 * without _JEXEC and calls Log::addLogger() at file scope, needing a full
 * Joomla bootstrap), so - matching InstallerScriptArchitectureTest's existing
 * approach - these assertions work against the raw source. The regex is
 * additionally extracted and actually executed against representative
 * historical content for each covered column, rather than only asserting
 * its presence as a string.
 */
final class LegacyComponentOptionMigrationTest extends TestCase
{
    private const LEGACY_OPTION_PATTERN = '/com_breezingforms(?!ng)/';

    public function testMigrationScansEveryColumnKnownToStoreExecutableOrLinkedContent(): void
    {
        $source = file_get_contents(__DIR__ . '/../../script.php');

        self::assertIsString($source);

        // facileforms_elements: per-element free-text/code columns.
        self::assertMatchesRegularExpression(
            "/\\\$elementsTable,\s*\['data1', 'data2', 'data3', 'script1code', 'script2code', 'script3code', "
            . "'script3msg', 'mailbackfile'\]/",
            $source
        );

        // facileforms_forms: the compiled tree copies plus form-level script/piece code.
        self::assertMatchesRegularExpression(
            "/\\\$formsTable,\s*\[\s*'template_areas', 'template_code_processed',\s*"
            . "'script1code', 'script2code',\s*'piece1code', 'piece2code', 'piece3code', 'piece4code',\s*\]/",
            $source
        );

        self::assertStringContainsString("migrateLegacyOptionInPlainColumns(\$db, \$scriptsTable, ['code']);", $source);
        self::assertStringContainsString("migrateLegacyOptionInPlainColumns(\$db, \$piecesTable, ['code']);", $source);

        // Both the plain-column scanner and the base64 template_code scanner must use
        // the exact same rewrite pattern, verified separately below against real
        // sample content.
        self::assertSame(2, substr_count($source, self::LEGACY_OPTION_PATTERN));
    }

    #[DataProvider('historicalContentProvider')]
    public function testPatternRewritesLegacyOptionInEachColumnsContent(
        string $column,
        string $content,
        string $expected
    ): void {
        $rewritten = preg_replace(self::LEGACY_OPTION_PATTERN, 'com_breezingformsng', $content);

        self::assertSame($expected, $rewritten, "Column {$column} was not rewritten as expected.");
    }

    public static function historicalContentProvider(): array
    {
        return [
            'facileforms_elements.data1 (Stripe thankYouPage)' => [
                'data1',
                '{"secretKey":"sk_test_x",'
                    . '"thankYouPage":"index.php?option=com_breezingforms&ff_name=StripePaiement&ff_page=2"}',
                '{"secretKey":"sk_test_x",'
                    . '"thankYouPage":"index.php?option=com_breezingformsng&ff_name=StripePaiement&ff_page=2"}',
            ],
            'facileforms_elements.script1code (custom init code)' => [
                'script1code',
                "function ff_x_init(){ location.href='index.php?option=com_breezingforms&view=form'; }",
                "function ff_x_init(){ location.href='index.php?option=com_breezingformsng&view=form'; }",
            ],
            'facileforms_forms.template_areas (compiled QuickMode JSON)' => [
                'template_areas',
                '{"thankYouPage":"index.php?option=com_breezingforms&ff_name=Vcmb_Check&ff_page=2"}',
                '{"thankYouPage":"index.php?option=com_breezingformsng&ff_name=Vcmb_Check&ff_page=2"}',
            ],
            'facileforms_forms.piece1code (reusable piece code)' => [
                'piece1code',
                "//+trace max all\n\$this->traceMode = 'index.php?option=com_breezingforms&task=redirect';",
                "//+trace max all\n\$this->traceMode = 'index.php?option=com_breezingformsng&task=redirect';",
            ],
            'facileforms_scripts.code (standalone reusable script)' => [
                'code',
                "function ff_validate(){ return 'index.php?option=com_breezingforms&ff_page=1'; }",
                "function ff_validate(){ return 'index.php?option=com_breezingformsng&ff_page=1'; }",
            ],
            'facileforms_pieces.code (standalone reusable piece)' => [
                'code',
                "redirect('index.php?option=com_breezingforms&view=thanks');",
                "redirect('index.php?option=com_breezingformsng&view=thanks');",
            ],
        ];
    }

    public function testPatternNeverDoubleMigratesAnAlreadyCorrectReference(): void
    {
        $alreadyMigrated = 'index.php?option=com_breezingformsng&ff_name=StripePaiement&ff_page=2';

        self::assertSame(
            $alreadyMigrated,
            preg_replace(self::LEGACY_OPTION_PATTERN, 'com_breezingformsng', $alreadyMigrated)
        );
    }

    public function testPatternMigratesAMixOfOldAndAlreadyCorrectReferencesInTheSameValue(): void
    {
        $mixed = 'old: option=com_breezingforms, new: option=com_breezingformsng';

        self::assertSame(
            'old: option=com_breezingformsng, new: option=com_breezingformsng',
            preg_replace(self::LEGACY_OPTION_PATTERN, 'com_breezingformsng', $mixed)
        );
    }
}
