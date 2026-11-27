<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

/**
 * Tells which tool a database table belongs to, from its name: a table of no
 * known tool is free to be filled by an Integrator rule.
 */
final class TableOwner
{
    public const JOOMLA = 'joomla';
    public const BFNG = 'bfng';
    public const CBNG = 'cbng';
    public const EXTENSION = 'extension';
    public const FREE = 'free';

    public const ALL = [self::FREE, self::JOOMLA, self::BFNG, self::CBNG, self::EXTENSION];

    /** The tables of the Joomla 6.2 installation, without the site prefix. */
    private const CORE = [
        'action_log_config', 'action_logs', 'action_logs_extensions', 'action_logs_users', 'assets',
        'associations', 'banner_clients', 'banner_tracks', 'banners', 'categories', 'contact_details',
        'content', 'content_frontpage', 'content_rating', 'content_types', 'contentitem_tag_map', 'extensions',
        'fields', 'fields_categories', 'fields_groups', 'fields_values', 'finder_filters', 'finder_links',
        'finder_links_terms', 'finder_logging', 'finder_taxonomy', 'finder_taxonomy_map', 'finder_terms',
        'finder_terms_common', 'finder_tokens', 'finder_tokens_aggregate', 'finder_types', 'guidedtour_steps',
        'guidedtours', 'history', 'languages', 'mail_templates', 'menu', 'menu_types', 'messages',
        'messages_cfg', 'modules', 'modules_menu', 'newsfeeds', 'overrider', 'postinstall_messages',
        'privacy_consents', 'privacy_requests', 'redirect_links', 'scheduler_logs', 'scheduler_tasks',
        'schemaorg', 'schemas', 'session', 'tags', 'template_overrides', 'template_styles', 'tuf_metadata',
        'ucm_base', 'ucm_content', 'update_sites', 'update_sites_extensions', 'updates', 'user_keys',
        'user_mfa', 'user_notes', 'user_profiles', 'user_usergroup_map', 'usergroups', 'users', 'viewlevels',
        'webauthn_credentials', 'workflow_associations', 'workflow_stages', 'workflow_transitions', 'workflows',
    ];

    /**
     * @param string       $table      Full name of the table
     * @param string       $prefix     Table prefix of the site
     * @param list<string> $components Elements of the installed components (com_...)
     */
    public static function of(string $table, string $prefix, array $components = []): string
    {
        // Without the prefix of the site, no extension of this site installed it.
        if ($prefix === '' || !str_starts_with($table, $prefix)) {
            return self::FREE;
        }

        $name = strtolower(substr($table, \strlen($prefix)));

        if (\in_array($name, self::CORE, true)) {
            return self::JOOMLA;
        }

        if (str_starts_with($name, 'facileforms_') || str_starts_with($name, 'breezingformsng_')) {
            return self::BFNG;
        }

        if (str_starts_with($name, 'contentbuilderng_')) {
            return self::CBNG;
        }

        foreach ($components as $component) {
            $base = strtolower(preg_replace('/^com_/i', '', $component) ?? '');

            if ($base !== '' && ($name === $base || str_starts_with($name, $base . '_'))) {
                return self::EXTENSION;
            }
        }

        return self::FREE;
    }
}
