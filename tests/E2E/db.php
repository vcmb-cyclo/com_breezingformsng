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
    case 'forms-count':
        // forms-count [package]: how many forms there are, and how many in the package.
        $package = $db->real_escape_string($argv[2] ?? '');
        echo json_encode([
            'all' => (int) $db->query("SELECT COUNT(*) c FROM {$forms}")->fetch_assoc()['c'],
            'package' => (int) $db->query("SELECT COUNT(*) c FROM {$forms} WHERE package = '{$package}'")->fetch_assoc()['c'],
            'elements' => (int) $db->query("SELECT COUNT(*) c FROM {$config->dbprefix}facileforms_elements")->fetch_assoc()['c'],
        ]);
        break;
    case 'record':
        // record <id> | record last <formId>: a record, with its state and payment transaction.
        $records = $config->dbprefix . 'facileforms_records';
        $where = ($argv[2] ?? '') === 'last'
            ? 'form = ' . (int) ($argv[3] ?? 0) . ' ORDER BY id DESC LIMIT 1'
            : "id = {$id}";
        echo json_encode($db->query("SELECT id, form, state, paypal_tx_id FROM {$records} WHERE {$where}")->fetch_assoc());
        break;
    case 'records-count':
        // records-count <form id>
        echo (int) $db->query("SELECT COUNT(*) c FROM {$config->dbprefix}facileforms_records WHERE form = {$id}")->fetch_assoc()['c'];
        break;
    case 'create-user':
        // create-user <username> <password> <group id>: a user of that group only, created again each time.
        $username = $db->real_escape_string($argv[2] ?? '');
        $hash = $db->real_escape_string(password_hash($argv[3] ?? '', PASSWORD_BCRYPT));
        $users = $config->dbprefix . 'users';
        $map = $config->dbprefix . 'user_usergroup_map';
        $db->query("DELETE FROM {$map} WHERE user_id IN (SELECT id FROM {$users} WHERE username = '{$username}')");
        $db->query("DELETE FROM {$users} WHERE username = '{$username}'");
        $db->query("INSERT INTO {$users} (name, username, email, password, block, sendEmail, registerDate, params, activation, otpKey, otep, authProvider) VALUES ('{$username}', '{$username}', '{$username}@example.invalid', '{$hash}', 0, 0, NOW(), '{}', '', '', '', '')");
        $userId = $db->insert_id;
        $db->query("INSERT INTO {$map} (user_id, group_id) VALUES ({$userId}, " . (int) ($argv[4] ?? 2) . ')');
        echo $userId;
        break;
    case 'component-rules':
        // component-rules <json>: the permissions of the component, as the Permissions tab stores them.
        $db->query("UPDATE {$config->dbprefix}assets SET rules = '" . $db->real_escape_string($argv[2] ?? '{}') . "' WHERE name = 'com_breezingformsng'");
        echo $db->affected_rows >= 0 ? 'ok' : 'failed';
        break;
    case 'pay-twice':
        // pay-twice <record id>: the payment storage of the Stripe return and webhook, run twice on a real database.
        // Joomla's framework reuses the name $config: the table prefix is kept first.
        $records = $config->dbprefix . 'facileforms_records';
        define('_JEXEC', 1);
        define('JPATH_BASE', '/var/www/html');
        $_SERVER['HTTP_HOST'] = 'localhost';
        require JPATH_BASE . '/includes/defines.php';
        require JPATH_BASE . '/includes/framework.php';
        require_once JPATH_BASE . '/components/com_breezingformsng/src/Service/Callback/PaymentRecordService.php';
        $service = new \Vcmb\Component\BreezingformsNG\Site\Service\Callback\PaymentRecordService(
            \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class)
        );
        $first = $service->storeTransactionOnce($id, 'Stripe: pi_first', '2026-01-01 10:00:00', 1);
        $second = $service->storeTransactionOnce($id, 'Stripe: pi_replay', '2026-01-02 10:00:00', 1);
        $row = $db->query("SELECT paypal_tx_id, paypal_payment_date FROM {$records} WHERE id = {$id}")->fetch_assoc();
        echo json_encode(['first' => $first, 'second' => $second] + $row);
        break;
    default:
        exit(1);
}
