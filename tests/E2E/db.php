<?php

/**
 * Reads and sets test data in the Joomla of the journeys, inside its container.
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

require '/var/www/html/configuration.php';

$config = new JConfig();
$db = new mysqli($config->host, $config->user, $config->password, $config->db);
$forms = $config->dbprefix . 'facileforms_forms';
$id = (int) ($argv[2] ?? 0);

switch ($argv[1] ?? '') {
    case 'form':
        $form = $db->query("SELECT * FROM {$forms} WHERE id = {$id}")->fetch_assoc();
        $form['records'] = (int) $db->query("SELECT COUNT(*) c FROM {$config->dbprefix}facileforms_records WHERE form = {$id}")->fetch_assoc()['c'];
        $form['history'] = (int) $db->query("SELECT COUNT(*) c FROM {$config->dbprefix}history WHERE item_id = 'com_breezingformsng.form.{$id}'")->fetch_assoc()['c'];
        echo json_encode($form);
        break;
    case 'package-form':
        $package = $db->real_escape_string($argv[2] ?? '');
        echo json_encode($db->query("SELECT id, name, published, runmode FROM {$forms} WHERE package = '{$package}' LIMIT 1")->fetch_assoc());
        break;
    case 'set':
        $db->query("UPDATE {$forms} SET `" . $db->real_escape_string($argv[3]) . "` = '" . $db->real_escape_string($argv[4]) . "' WHERE id = {$id}");
        break;
    case 'plugin':
        // plugin <folder> <element> [enabled]: prints the state, after setting it when given.
        $folder = $db->real_escape_string($argv[2] ?? '');
        $element = $db->real_escape_string($argv[3] ?? '');
        $where = "type = 'plugin' AND folder = '{$folder}' AND element = '{$element}'";
        if (isset($argv[4])) {
            $db->query("UPDATE {$config->dbprefix}extensions SET enabled = " . (int) $argv[4] . " WHERE {$where}");
        }
        echo json_encode($db->query("SELECT enabled FROM {$config->dbprefix}extensions WHERE {$where}")->fetch_assoc());
        break;
    case 'sample-form':
        // sample-form <name>: a form with a text element, created once; prints its id.
        $name = $db->real_escape_string($argv[2] ?? '');
        $form = $db->query("SELECT id FROM {$forms} WHERE name = '{$name}'")->fetch_assoc();
        if ($form === null) {
            $db->query("INSERT INTO {$forms} (name, title, published, pages, emailadr, mailchimp_api_key) VALUES ('{$name}', 'Sample {$name}', 1, 1, 'owner@example.invalid', 'secret-key')");
            $formId = $db->insert_id;
            $db->query("INSERT INTO {$config->dbprefix}facileforms_elements (form, page, ordering, published, name, title, type, data1, data2, data3) VALUES ({$formId}, 1, 1, 1, 'email', 'Email', 'Text', '', '', '')");
            $form = ['id' => $formId];
        }
        echo (int) $form['id'];
        break;
    case 'legacy-fixture':
        // legacy-fixture <mode>: isolated test data, including an old submission.
        $mode = $argv[2] ?? '';
        if (!in_array($mode, ['easy', 'classic', 'unsupported', 'select', 'notifications'], true)) {
            throw new InvalidArgumentException('Invalid fixture mode');
        }
        $name = 'e2e_legacy_' . bin2hex(random_bytes(6));
        $package = $mode === 'easy' ? 'EasyModeForms' : 'ClassicForms';
        $template = $mode === 'easy' ? '<div>Legacy Easy Mode</div>' : '';
        $template = $db->real_escape_string($template);
        $db->query("INSERT INTO {$forms} (name, title, package, template_code, template_code_processed, template_areas, published, pages, emailntf, emailadr) VALUES ('{$name}', '{$name}', '{$package}', '{$template}', '{$template}', '[]', 1, 1, 0, '')");
        $formId = (int) $db->insert_id;
        $type = match ($mode) {
            'unsupported' => 'Radio Button',
            'select' => 'Select List',
            default => 'Text',
        };
        $elementName = $mode === 'unsupported' ? '<img src=x onerror=alert(1)>' : 'first_name';
        $elementName = $db->real_escape_string($elementName);
        $data1 = $mode === 'select' ? '1' : 'initial';
        $data2 = $mode === 'select' ? "0;One;one\n1;Two;two" : '';
        $data2 = $db->real_escape_string($data2);
        $db->query("INSERT INTO {$config->dbprefix}facileforms_elements (form, page, ordering, published, name, title, type, data1, data2, data3) VALUES ({$formId}, 1, 1, 1, '{$elementName}', 'First name', '{$type}', '{$data1}', '{$data2}', '')");
        if ($mode === 'notifications') {
            // Legacy mode 1 sends to the site address, incompatible with an explicit address.
            $db->query("UPDATE {$forms} SET emailntf = 1, emailadr = 'owner@example.invalid' WHERE id = {$formId}");
        }
        $db->query("INSERT INTO {$config->dbprefix}facileforms_records (form, name, title, browser) VALUES ({$formId}, '{$name}', '{$name}', 'test')");
        echo json_encode(['id' => $formId, 'name' => $name]);
        break;
    case 'legacy-template-create':
    case 'legacy-template-remove':
        // Only touch test-owned names; never remove a runtime template belonging to a user.
        $name = (string) ($argv[2] ?? '');
        if (!preg_match('/^e2e_legacy_[a-f0-9]{12}$/D', $name)) {
            throw new InvalidArgumentException('Invalid fixture name');
        }
        $directory = '/var/www/html/media/breezingforms/mailtpl';
        $path = $directory . '/' . $name . '.html.php';
        if ($argv[1] === 'legacy-template-create') {
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('Could not create test template directory');
            }
            if (file_exists($path) || file_put_contents($path, '<?php // e2e fixture') === false) {
                throw new RuntimeException('Could not create test template');
            }
        } elseif (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Could not remove test template');
        }
        break;
    case 'legacy-manager-deny':
        // On disposable test installations only, deny create to the Manager
        // group while keeping access to the component. Return the exact rules
        // so the caller can restore them even if the browser assertion fails.
        $asset = $db->query("SELECT rules FROM {$config->dbprefix}assets WHERE name = 'com_breezingformsng'")->fetch_assoc();
        $manager = $db->query("SELECT id FROM {$config->dbprefix}usergroups WHERE title = 'Manager'")->fetch_assoc();
        if ($asset === null || $manager === null) {
            throw new RuntimeException('Missing component asset or Manager group');
        }
        $rules = json_decode($asset['rules'], true, 512, JSON_THROW_ON_ERROR);
        $group = (string) $manager['id'];
        $rules['core.manage'][$group] = 1;
        $rules['core.edit'][$group] = 1;
        $rules['core.create'][$group] = 0;
        $updated = $db->real_escape_string(json_encode($rules, JSON_THROW_ON_ERROR));
        $db->query("UPDATE {$config->dbprefix}assets SET rules = '{$updated}' WHERE name = 'com_breezingformsng'");
        echo base64_encode($asset['rules']);
        break;
    case 'legacy-manager-restore':
        $original = base64_decode((string) ($argv[2] ?? ''), true);
        if ($original === false || !is_array(json_decode($original, true))) {
            throw new InvalidArgumentException('Invalid original access rules');
        }
        $original = $db->real_escape_string($original);
        $db->query("UPDATE {$config->dbprefix}assets SET rules = '{$original}' WHERE name = 'com_breezingformsng'");
        break;
    case 'legacy-snapshot':
        // legacy-snapshot <form-id>: include originals, submissions and all conversion copies.
        $form = $db->query("SELECT * FROM {$forms} WHERE id = {$id}")->fetch_assoc();
        $elements = $db->query("SELECT * FROM {$config->dbprefix}facileforms_elements WHERE form = {$id} ORDER BY id")->fetch_all(MYSQLI_ASSOC);
        $records = $db->query("SELECT * FROM {$config->dbprefix}facileforms_records WHERE form = {$id} ORDER BY id")->fetch_all(MYSQLI_ASSOC);
        $prefix = 'quickmode_' . $id . '_';
        $prefix = $db->real_escape_string($prefix);
        $copies = $db->query("SELECT * FROM {$forms} WHERE LEFT(name, CHAR_LENGTH('{$prefix}')) = '{$prefix}' ORDER BY id")->fetch_all(MYSQLI_ASSOC);
        foreach ($copies as &$copy) {
            $copyId = (int) $copy['id'];
            $copy['elements'] = $db->query("SELECT * FROM {$config->dbprefix}facileforms_elements WHERE form = {$copyId} ORDER BY id")->fetch_all(MYSQLI_ASSOC);
            $copy['records'] = (int) $db->query("SELECT COUNT(*) c FROM {$config->dbprefix}facileforms_records WHERE form = {$copyId}")->fetch_assoc()['c'];
        }
        unset($copy);
        echo json_encode(compact('form', 'elements', 'records', 'copies'));
        break;
    case 'api-token':
        // api-token <username>: gives the user a new Joomla API token, as the User - Joomla API Token plugin does.
        $user = $db->query("SELECT id FROM {$config->dbprefix}users WHERE username = '" . $db->real_escape_string($argv[2] ?? '') . "'")->fetch_assoc();
        $userId = (int) $user['id'];
        $seed = random_bytes(32);
        $profiles = $config->dbprefix . 'user_profiles';
        $db->query("DELETE FROM {$profiles} WHERE user_id = {$userId} AND profile_key LIKE 'joomlatoken.%'");
        $db->query("INSERT INTO {$profiles} (user_id, profile_key, profile_value, ordering) VALUES ({$userId}, 'joomlatoken.token', '" . base64_encode($seed) . "', 1), ({$userId}, 'joomlatoken.enabled', '1', 2)");
        echo base64_encode('sha256:' . $userId . ':' . hash_hmac('sha256', $seed, $config->secret));
        break;
    default:
        exit(1);
}
