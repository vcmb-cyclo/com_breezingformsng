<?php

/** Focused executable regression: php tests/InstallerComponentDeduplicationRegression.php */

declare(strict_types=1);

$source = file_get_contents($argv[1] ?? __DIR__ . '/../script.php');
$methods = '';
foreach (['deduplicateBreezingFormsComponentRows', 'verifyInstalledExtensionConsistency', 'getCurrentInstalledVersion'] as $name) {
    $start = strpos($source, '    private function ' . $name . '(');
    $end = strpos($source, "\n    private function ", $start + 1);
    $method = substr($source, $start, $end - $start);
    $method = preg_replace('/\n    \/\*\*.*$/s', '', $method);
    $method = str_replace('private function', 'public function', $method);
    $method = str_replace('Factory::getContainer()->get(DatabaseInterface::class)', '$this->db', $method);
    $method = str_replace(['Log::ERROR', 'Log::WARNING', 'ParameterType::INTEGER'], ['3', '4', '1'], $method);
    $methods .= $method;
}

$runner = eval('return new class {
    private const TARGET_COMPONENT = "com_breezingformsng";
    public string $incomingPackageVersion = "6.1.0-RC12";
    public object $db;
    public array $errors = [];
    public function getIncomingVersion($adapter): string { return $this->incomingPackageVersion; }
    public function log($message, $priority = 6): void { if ($priority === 3) $this->errors[] = $message; }
    public function cleanupExtensionReferences(array $ids): void {
        foreach ($ids as $id) unset($this->db->schemas[$id]);
    }
' . $methods . '};');

$db = new class {
    public array $rows = [];
    public array $menus = [];
    public array $schemas = [];
    public array $bindings = [];
    public string $table = '';
    public string $action = '';
    public array $conditions = [];
    public string $assignment = '';
    public function createQuery(): self
    {
        $this->bindings = [];
        $this->conditions = [];
        return $this;
    }
    public function quoteName($name)
    {
        return is_array($name) ? $name : '`' . $name . '`';
    }
    public function quote($value): string
    {
        return "'" . $value . "'";
    }
    public function select($columns): self
    {
        $this->action = 'select';
        return $this;
    }
    public function from($table): self
    {
        $this->table = $table;
        return $this;
    }
    public function where($value): self
    {
        $this->conditions[] = $value;
        return $this;
    }
    public function order($value): self
    {
        return $this;
    }
    public function group($value): self
    {
        return $this;
    }
    public function update($table): self
    {
        $this->table = $table;
        $this->action = 'update';
        return $this;
    }
    public function delete($table): self
    {
        $this->table = $table;
        $this->action = 'delete';
        return $this;
    }
    public function set($value): self
    {
        $this->assignment = $value;
        return $this;
    }
    public function bind($key, &$value, $type = null): self
    {
        $this->bindings[$key] = $value;
        return $this;
    }
    public function setQuery($query): self
    {
        return $this;
    }
    public function loadResult(): string
    {
        return (string) (array_values($this->rows)[0]['manifest_cache'] ?? '');
    }
    public function loadAssocList(): array
    {
        if (str_contains($this->table, '#__extensions')) {
            return array_values($this->rows);
        }
        $counts = array_count_values($this->menus);
        $rows = [];
        foreach ($counts as $id => $refs) {
            $rows[] = ['component_id' => $id, 'refs' => $refs];
        }
        return $rows;
    }
    public function execute(): self
    {
        if (isset($this->bindings[':params'])) {
            $this->rows[$this->bindings[':extensionId']]['params'] = $this->bindings[':params'];
            return $this;
        }
        preg_match('/IN \(([\d,]+)\)/', implode(' ', $this->conditions), $match);
        $ids = isset($match[1]) ? array_map('intval', explode(',', $match[1])) : [];
        if ($this->action === 'delete') {
            foreach ($ids as $id) {
                unset($this->rows[$id]);
            }
        } elseif (str_contains($this->table, '#__menu')) {
            preg_match('/= (\d+)/', $this->assignment, $assignment);
            foreach ($this->menus as &$id) {
                if (in_array($id, $ids, true)) {
                    $id = (int) $assignment[1];
                }
            }
        }
        return $this;
    }
};
$runner->db = $db;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$manifest = static fn(string $version): string => json_encode([
    'name' => 'COM_BREEZINGFORMSNG', 'authorUrl' => 'https://breezingforms-ng.vcmb.fr', 'version' => $version,
], JSON_THROW_ON_ERROR);
$db->rows = [
    400 => ['extension_id' => 400, 'enabled' => 1, 'manifest_cache' => $manifest('6.1.0-RC12'),
        'params' => '{"uploads":"default","new_option":true}'],
    250 => ['extension_id' => 250, 'enabled' => 1, 'manifest_cache' => $manifest('6.0.8-RC7'),
        'params' => '{"uploads":"custom/uploads","save_history":1}'],
];
$db->menus = array_fill(0, 18, 250);
$db->menus[] = 400;
$db->schemas = [250 => '6.0.8', 400 => '6.1.0'];
$runner->deduplicateBreezingFormsComponentRows();
$assert(array_keys($db->rows) === [400], 'The RC12 extension row must survive legacy deduplication.');
$assert(array_unique($db->menus) === [400], 'All menu references must point to RC12.');
$assert($db->schemas === [400 => '6.1.0'], 'The schema registration written by Joomla must survive.');
$params = json_decode($db->rows[400]['params'], true);
$assert($params === ['uploads' => 'custom/uploads', 'new_option' => true, 'save_history' => 1], 'Preserve existing and newly introduced parameters.');

$root = sys_get_temp_dir() . '/bfng-installer-regression-' . bin2hex(random_bytes(6));
define('JPATH_ADMINISTRATOR', $root . '/administrator');
define('JPATH_SITE', $root);
$files = [
    JPATH_ADMINISTRATOR . '/components/com_breezingformsng/services/provider.php',
    JPATH_ADMINISTRATOR . '/components/com_breezingformsng/src/Extension/BreezingFormsNGComponent.php',
    JPATH_SITE . '/components/com_breezingformsng/breezingformsng.php',
];
try {
    foreach ($files as $file) {
        mkdir(dirname($file), 0755, true);
        file_put_contents($file, '<?php');
    }
    $runner->verifyInstalledExtensionConsistency(null);
    $assert($runner->errors === [], 'The valid first RC12 upgrade must pass consistency verification.');
    $before = [$db->rows, $db->menus, $db->schemas];
    $runner->deduplicateBreezingFormsComponentRows();
    $assert([$db->rows, $db->menus, $db->schemas] === $before, 'Reinstallation must remain idempotent.');
    unlink($files[0]);
    $runner->verifyInstalledExtensionConsistency(null);
    $assert(count($runner->errors) === 1 && str_contains($runner->errors[0], 'required file missing'), 'A genuine deployment error must still fail.');
} finally {
    foreach (array_reverse($files) as $file) {
        if (is_file($file)) {
            unlink($file);
        }
        $directory = dirname($file);
        while (str_starts_with($directory, $root) && is_dir($directory) && count(scandir($directory)) === 2) {
            rmdir($directory);
            $directory = dirname($directory);
        }
    }
}
echo "PASS: first legacy upgrade, current manifest/schema, preserved options, menu remapping, reinstall, missing-file failure.\n";
