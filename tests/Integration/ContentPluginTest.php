<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Integration;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Mysqli\MysqliQuery;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Vcmb\Plugin\Content\Breezingforms\Extension\Breezingforms;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ContentPluginTest extends TestCase
{
    private string $site;

    protected function setUp(): void
    {
        $libraries = getenv('BFNG_JOOMLA_LIBRARIES');
        if (!$libraries || !is_file($libraries . '/vendor/autoload.php')) {
            self::markTestSkipped('Set BFNG_JOOMLA_LIBRARIES to a Joomla 6 libraries directory.');
        }
        $loader = require $libraries . '/vendor/autoload.php';
        $loader->setClassMapAuthoritative(false);
        $loader->addPsr4('Joomla\\CMS\\', $libraries . '/src', true);
        foreach ($loader->getClassMap() as $class => $path) {
            if (str_starts_with($class, 'Joomla\\CMS\\')) {
                $loader->addClassMap([$class => $libraries . '/src/'
                    . str_replace('\\', '/', substr($class, strlen('Joomla\\CMS\\'))) . '.php']);
            }
        }
        require_once getenv('BFNG_PLUGIN_SOURCE') ?: dirname(__DIR__, 2)
            . '/administrator/components/com_breezingformsng/plugins/breezingforms/src/Extension/Breezingforms.php';
        $this->site = sys_get_temp_dir() . '/bfng-content-test-' . bin2hex(random_bytes(6));
        mkdir($this->site . '/components/com_breezingformsng/src/Support', 0777, true);
        define('JPATH_SITE', $this->site);
        define('JPATH_ROOT', $this->site);
        file_put_contents(
            $this->site . '/components/com_breezingformsng/src/Support/runtime_bootstrap.php',
            '<?php $GLOBALS["ff_target"] = 0;'
        );
        file_put_contents($this->site . '/components/com_breezingformsng/breezingformsng.php', <<<'PHP'
<?php
        ++$GLOBALS['ff_target'];
        if ($input->getBool('throw_fixture')) {
            echo 'partial output';
            throw new RuntimeException('Render failed');
        }
        echo json_encode([
            'name' => $input->getString('ff_name'),
            'page' => $input->getInt('ff_page'),
            'target' => $GLOBALS['ff_target'],
            'form' => $input->getInt('ff_form'),
            'source' => $input->getString('ff_param_source'),
            'foreign' => $input->getString('ff_param_foreign', ''),
        ]) . "\n";
PHP);
    }

    protected function tearDown(): void
    {
        if (!isset($this->site)) {
            return;
        }
        $paths = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->site, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($paths as $path) {
            $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
        }
        rmdir($this->site);
        Factory::$application = null;
    }

    private function plugin(Input $input): Breezingforms
    {
        $database = $this->createStub(DatabaseInterface::class);
        $database->method('getQuery')->willReturnCallback(static fn () => new MysqliQuery());
        $database->method('quoteName')->willReturnArgument(0);
        $database->method('setQuery')->willReturnSelf();
        $database->method('loadObject')->willReturn((object) ['id' => 10, 'name' => 'Contact']);
        $session = $this->createStub(Session::class);
        $application = $this->createStub(CMSApplication::class);
        $application->method('isClient')->willReturn(true);
        $application->method('getInput')->willReturn($input);
        $application->method('getSession')->willReturn($session);
        $plugin = new Breezingforms(['name' => 'breezingforms', 'type' => 'content', 'params' => '{}']);
        $plugin->setApplication($application);
        $plugin->setDatabase($database);

        return $plugin;
    }

    private function render(Breezingforms $plugin, string $text): string
    {
        $item = (object) ['id' => 42, 'text' => $text];
        $plugin->prepareContent(new ContentPrepareEvent('onContentPrepare', [
            'context' => 'com_content.article',
            'subject' => $item,
            'params' => new Registry(),
            'page' => 0,
        ]));

        return $item->text;
    }

    public function testOnlySubmittedFormReceivesRequestPageAndParameters(): void
    {
        $input = new Input(['ff_target' => 2, 'ff_page' => 7, 'ff_param_foreign' => 'request']);
        $output = $this->render($this->plugin($input),
            '{BreezingForms:First,1,0,&ff_param_source=article}{BreezingForms:Second}');
        $forms = array_map(static fn ($line) => json_decode($line, true), explode("\n", trim($output)));
        self::assertSame(1, $forms[0]['page']);
        self::assertSame('', $forms[0]['foreign']);
        self::assertSame('article', $forms[0]['source']);
        self::assertSame(7, $forms[1]['page']);
        self::assertSame('request', $forms[1]['foreign']);
        self::assertSame([1, 2], array_column($forms, 'target'));
    }

    public function testSharesTargetCounterWithExistingModule(): void
    {
        require_once $this->site . '/components/com_breezingformsng/src/Support/runtime_bootstrap.php';
        $GLOBALS['ff_target'] = 1;
        $input = new Input(['ff_target' => 2, 'ff_page' => 7]);
        $form = json_decode(trim($this->render($this->plugin($input), '{BreezingForms:Contact}')), true);
        self::assertSame(2, $form['target']);
        self::assertSame(7, $form['page']);
    }

    public function testRestoresRawArticleRequestAndParameters(): void
    {
        $raw = '<strong>original</strong>';
        $input = new Input(['article_html' => $raw, 'ff_name' => $raw, 'ff_param_source' => $raw]);
        $this->render($this->plugin($input), '{BreezingForms:Contact}');
        self::assertSame($raw, $input->get('article_html', null, 'raw'));
        self::assertSame($raw, $input->get('ff_name', null, 'raw'));
        self::assertSame($raw, $input->get('ff_param_source', null, 'raw'));
        self::assertNull($input->get('ff_contentid', null, 'raw'));
    }

    public function testFailureRestoresInputAndOutputBuffer(): void
    {
        $input = new Input(['throw_fixture' => true, 'ff_name' => 'original']);
        $level = ob_get_level();
        try {
            $this->render($this->plugin($input), '{BreezingForms:Contact}');
            self::fail('Expected rendering failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('Render failed', $exception->getMessage());
        }
        self::assertSame('original', $input->getString('ff_name'));
        self::assertSame($level, ob_get_level());
    }

    public function testUnrelatedAndMalformedTagsRemainUnchanged(): void
    {
        $text = '{BreezingForms:} {breezingforms:Contact} {Other:Contact}';
        self::assertSame($text, $this->render($this->plugin(new Input([])), $text));
    }
}
