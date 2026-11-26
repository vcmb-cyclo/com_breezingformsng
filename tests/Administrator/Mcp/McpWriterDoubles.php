<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Administrator\Mcp;

use Joomla\Database\DatabaseInterface;

require_once __DIR__ . '/McpTestDoubles.php';

// The component models extend Joomla classes that are not installed with the unit tests:
// minimal parents let PHPUnit build doubles of the models.
if (!class_exists(\Joomla\CMS\MVC\Model\BaseDatabaseModel::class)) {
    eval('namespace Joomla\\CMS\\MVC\\Model; class BaseDatabaseModel {}');
}
if (!class_exists(\Joomla\CMS\MVC\Model\AdminModel::class)) {
    eval(
        'namespace Joomla\\CMS\\MVC\\Model; class AdminModel extends BaseDatabaseModel {'
        . ' public function checkout($pk = null) { return true; }'
        . ' public function checkin($pk = null) { return true; }'
        . ' public function getName() { return "Form"; }'
        . ' public function setState($property, $value = null) { return null; }'
        . ' protected function canDelete($record) { return true; }'
        . '}'
    );
}
if (!interface_exists(\Joomla\CMS\Versioning\VersionableModelInterface::class)) {
    eval('namespace Joomla\\CMS\\Versioning; interface VersionableModelInterface {'
        . ' public function saveHistory(array $data, string $context); }');
}
if (!trait_exists(\Joomla\CMS\Versioning\VersionableModelTrait::class)) {
    eval('namespace Joomla\\CMS\\Versioning; trait VersionableModelTrait {'
        . ' public function saveHistory(array $data, string $context) { return true; }}');
}
if (!class_exists(\Joomla\CMS\Component\ComponentHelper::class)) {
    eval('namespace Joomla\\CMS\\Component; final class ComponentHelper {
        public static function getParams(string $name): object {
            return new class {
                public function get(string $key, mixed $default = null): mixed { return $default; }
            };
        }
    }');
}

if (!class_exists(\Joomla\Database\ParameterType::class)) {
    eval('namespace Joomla\\Database; final class ParameterType { public const INTEGER = 1; public const STRING = 2; }');
}

/** A query builder that accepts every call of the writer and builds nothing. */
final class FakeQuery
{
    /** @param list<mixed> $arguments */
    public function __call(string $method, array $arguments): self
    {
        return $this;
    }
}

/** A database that records its transactions and answers every query with an empty result. */
final class FakeWriterDatabase implements DatabaseInterface
{
    /** @var list<string> */
    public array $log = [];

    public mixed $result = '0';

    public function createQuery(): FakeQuery
    {
        return new FakeQuery();
    }

    public function quoteName(mixed $name): string
    {
        return '`' . (is_array($name) ? implode(',', $name) : $name) . '`';
    }

    public function setQuery(mixed ...$arguments): self
    {
        $this->log[] = 'query';

        return $this;
    }

    public function execute(): bool
    {
        $this->log[] = 'execute';

        return true;
    }

    public function loadResult(): mixed
    {
        return $this->result;
    }

    public function transactionStart(bool $asSavepoint = false): void
    {
        $this->log[] = 'start';
    }

    public function transactionCommit(bool $toSavepoint = false): void
    {
        $this->log[] = 'commit';
    }

    public function transactionRollback(bool $toSavepoint = false): void
    {
        $this->log[] = 'rollback';
    }
}

/** Builds an McpFormWriter on doubles of the three models and a fake database. */
trait BuildsFormWriter
{
    private FakeWriterDatabase $db;
    private \PHPUnit\Framework\MockObject\MockObject $options;
    private \PHPUnit\Framework\MockObject\MockObject $forms;
    private \PHPUnit\Framework\MockObject\MockObject $quickmode;
    private \Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpFormWriter $writer;

    private function buildWriter(): void
    {
        $this->db = new FakeWriterDatabase();
        $this->options = $this->createMock(\Vcmb\Component\BreezingformsNG\Administrator\Model\FormOptionsModel::class);
        $this->forms = $this->createMock(\Vcmb\Component\BreezingformsNG\Administrator\Model\FormModel::class);
        $this->quickmode = $this->createMock(\Vcmb\Component\BreezingformsNG\Administrator\Model\QuickmodeModel::class);
        $this->writer = new \Vcmb\Component\BreezingformsNG\Administrator\Mcp\McpFormWriter(
            $this->db,
            $this->options,
            $this->forms,
            $this->quickmode
        );
    }

    /** @param array<string, mixed> $overrides */
    private static function storedForm(array $overrides = []): \stdClass
    {
        return (object) ($overrides + [
            'id' => 7,
            'name' => 'contact',
            'title' => 'Contact',
            'description' => '',
            'published' => 0,
            'checked_out' => 0,
            'version' => 1,
            'modified' => '2026-01-01 00:00:00',
            'width' => 600,
            'widthmode' => 0,
            'height' => 400,
            'heightmode' => 1,
            'autoheight' => 1,
            'emailntf' => 0,
            'emailadr' => '',
            'package' => 'QuickModeForms',
            'template_code_processed' => 'QuickMode',
        ]);
    }

    /** A QuickMode structure the field editor accepts: one page with one text field. */
    private static function quickmodeTemplate(): string
    {
        return (string) json_encode([
            'attributes' => ['id' => 'bfQuickModeForm'],
            'properties' => ['type' => 'root', 'submittedScriptCondidtion' => -1],
            'children' => [[
                'attributes' => ['id' => 'bfQuickModePage1'],
                'properties' => ['type' => 'page', 'pageNumber' => 1, 'pageIntro' => ''],
                'children' => [[
                    'attributes' => ['id' => 'bfQuickModeElement1_email'],
                    'properties' => [
                        'type' => 'element', 'bfType' => 'bfTextfield', 'value' => '', 'label' => 'Email',
                        'bfName' => 'email', 'orderNumber' => 1, 'tabIndex' => 0, 'logging' => 1,
                        'validationId' => 0, 'validationCode' => '', 'validationMessage' => '',
                        'validationFunctionName' => '', 'validationCondition' => 0,
                        'initId' => 0, 'initCode' => '', 'initFunctionName' => '', 'initCondition' => 0,
                        'actionId' => 0, 'actionFunctionName' => '', 'dbId' => 11,
                    ],
                    'children' => [],
                ]],
            ]],
        ]);
    }
}
