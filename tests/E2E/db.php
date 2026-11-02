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
    default:
        exit(1);
}
