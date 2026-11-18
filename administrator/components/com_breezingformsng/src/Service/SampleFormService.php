<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

namespace Vcmb\Component\BreezingformsNG\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Menu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * The sample forms shipped with the component: where their package file is, whether
 * one is installed, and its entry in the site menu ("Tests" > the form).
 */
final class SampleFormService
{
    public const FORM_NAME = 'Sample_Contact';
    public const FORM_PACKAGE_NAME = 'QuickModeForms';
    public const FORM_TITLE = 'Sample contact form';
    public const MENU_PARENT_TITLE = 'Tests';
    public const DEFAULT_SAMPLE = 'contact';

    /**
     * The shipped samples by slug: the name of the form (it is the menu route), the title shown in the
     * site menu, the alias of the menu item, the package file under packages/samples, and the category:
     * "business" for a ready-to-use template (contact, registration, quote, survey), "technical" for an
     * example of one feature (conditional fields, calculations, validation, repeatable fields...).
     */
    public const SAMPLES = [
        'contact' => [
            'name' => self::FORM_NAME, 'title' => self::FORM_TITLE,
            'alias' => 'bf-sample-contact', 'file' => 'bfng-sample-contact.json', 'category' => 'business',
        ],
        'event' => [
            'name' => 'Sample_Event', 'title' => 'Sample event registration',
            'alias' => 'bf-sample-event', 'file' => 'bfng-sample-event.json', 'category' => 'business',
        ],
        'quote' => [
            'name' => 'Sample_Quote', 'title' => 'Sample quote request',
            'alias' => 'bf-sample-quote', 'file' => 'bfng-sample-quote.json', 'category' => 'business',
        ],
        'survey' => [
            'name' => 'Sample_Survey', 'title' => 'Sample satisfaction survey',
            'alias' => 'bf-sample-survey', 'file' => 'bfng-sample-survey.json', 'category' => 'business',
        ],
    ];

    private const FORM_LINK = 'index.php?option=com_breezingformsng&view=form';

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly string $sample = self::DEFAULT_SAMPLE
    ) {
        if (!self::isSample($sample)) {
            throw new \InvalidArgumentException('Unknown sample form.');
        }
    }

    public static function isSample(string $sample): bool
    {
        return isset(self::SAMPLES[$sample]);
    }

    /** The categories, in the order the Tools screen lists them. */
    public const CATEGORIES = ['business', 'technical'];

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::SAMPLES);
    }

    public static function category(string $sample): string
    {
        return self::SAMPLES[$sample]['category'] ?? 'business';
    }

    public static function packagePath(string $sample = self::DEFAULT_SAMPLE): string
    {
        if (!self::isSample($sample)) {
            throw new \InvalidArgumentException('Unknown sample form.');
        }

        return JPATH_ADMINISTRATOR . '/components/com_breezingformsng/packages/samples/'
            . self::SAMPLES[$sample]['file'];
    }

    public function formName(): string
    {
        return self::SAMPLES[$this->sample]['name'];
    }

    /** Id of the installed sample form, 0 when it is not installed. */
    public function getInstalledFormId(): int
    {
        $name = $this->formName();
        $package = self::FORM_PACKAGE_NAME;
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__facileforms_forms'))
            ->where($this->db->quoteName('name') . ' = :name')
            ->where($this->db->quoteName('package') . ' = :package')
            ->bind(':name', $name, ParameterType::STRING)
            ->bind(':package', $package, ParameterType::STRING);

        return (int) $this->db->setQuery($query)->loadResult();
    }

    /** Id of the oldest form using the sample's name, whatever its package; 0 when there is none. */
    public function getFormIdByName(): int
    {
        $name = $this->formName();
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__facileforms_forms'))
            ->where($this->db->quoteName('name') . ' = :name')
            ->order($this->db->quoteName('id') . ' ASC')
            ->bind(':name', $name, ParameterType::STRING)
            ->setLimit(1);

        return (int) $this->db->setQuery($query)->loadResult();
    }

    /** Number of forms whose name would be used by the sample's name-based menu route. */
    public function getFormNameCount(): int
    {
        $name = $this->formName();
        $query = $this->db->createQuery()
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__facileforms_forms'))
            ->where($this->db->quoteName('name') . ' = :name')
            ->bind(':name', $name, ParameterType::STRING);

        return (int) $this->db->setQuery($query)->loadResult();
    }

    /**
     * Makes sure the site menu has a "Tests" entry with the sample form under it.
     *
     * @return int Id of the menu item of the sample form
     */
    public function ensureMenu(): int
    {
        $existing = $this->findFormMenuItem();

        if ($existing > 0) {
            return $existing;
        }

        $menutype = $this->siteMenuType();
        $parentId = $this->ensureParent($menutype);
        $componentId = $this->componentId();
        $params = [
            'ff_com_name' => $this->formName(), 'ff_com_page' => '1', 'ff_com_editable' => '0',
            'ff_com_editable_override' => '0', 'ff_com_frame' => '0', 'ff_com_border' => '0', 'ff_com_align' => '1',
            'ff_com_left' => '0', 'ff_com_top' => '0', 'ff_com_parprv' => '', 'ff_com_parpub' => '',
            'ff_com_suffix' => '', 'menu-anchor_title' => '', 'menu-anchor_css' => '', 'menu_icon_css' => '',
            'menu_image' => '', 'menu_image_css' => '', 'menu_text' => 1, 'menu_show' => 1, 'page_title' => '',
            'show_page_heading' => '', 'page_heading' => '', 'pageclass_sfx' => '', 'menu-meta_description' => '',
            'robots' => '',
        ];

        return $this->storeItem([
            'menutype' => $menutype,
            'title' => '(BF) ' . self::SAMPLES[$this->sample]['title'],
            'alias' => self::SAMPLES[$this->sample]['alias'],
            'path' => 'tests/' . self::SAMPLES[$this->sample]['alias'],
            'link' => self::FORM_LINK,
            'type' => 'component',
            'parent_id' => $parentId,
            'level' => 2,
            'component_id' => $componentId,
            'params' => json_encode($params, JSON_THROW_ON_ERROR),
        ]);
    }

    private function findFormMenuItem(): int
    {
        $link = self::FORM_LINK;
        $like = '%"ff_com_name":"' . $this->formName() . '"%';
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__menu'))
            ->where($this->db->quoteName('client_id') . ' = 0')
            ->where($this->db->quoteName('published') . ' >= 0')
            ->where($this->db->quoteName('link') . ' = :link')
            ->where($this->db->quoteName('params') . ' LIKE :params')
            ->bind(':link', $link, ParameterType::STRING)
            ->bind(':params', $like, ParameterType::STRING);

        return (int) $this->db->setQuery($query)->loadResult();
    }

    private function ensureParent(string $menutype): int
    {
        $title = self::MENU_PARENT_TITLE;
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__menu'))
            ->where($this->db->quoteName('client_id') . ' = 0')
            ->where($this->db->quoteName('menutype') . ' = :menutype')
            ->where($this->db->quoteName('parent_id') . ' = 1')
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('separator'))
            ->where($this->db->quoteName('title') . ' = :title')
            ->where($this->db->quoteName('published') . ' >= 0')
            ->bind(':menutype', $menutype, ParameterType::STRING)
            ->bind(':title', $title, ParameterType::STRING);
        $id = (int) $this->db->setQuery($query)->loadResult();

        if ($id > 0) {
            return $id;
        }

        return $this->storeItem([
            'menutype' => $menutype,
            'title' => $title,
            'alias' => 'tests',
            'path' => 'tests',
            'link' => '',
            'type' => 'separator',
            'parent_id' => 1,
            'level' => 1,
            'component_id' => 0,
            'params' => json_encode(
                ['menu-anchor_css' => '', 'menu_icon_css' => '', 'menu_image' => '', 'menu_image_css' => '',
                    'menu_text' => 1, 'menu_show' => 1],
                JSON_THROW_ON_ERROR
            ),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function storeItem(array $data): int
    {
        $table = new Menu($this->db);
        $table->setLocation((int) $data['parent_id'], 'last-child');
        $data += [
            'note' => '', 'published' => 1, 'checked_out' => 0, 'checked_out_time' => null, 'browserNav' => 0,
            'access' => 1, 'img' => '', 'template_style_id' => 0, 'home' => 0, 'language' => '*', 'client_id' => 0,
        ];

        if (!$table->bind($data) || !$table->check() || !$table->store()) {
            throw new \RuntimeException('The menu item could not be stored.');
        }

        return (int) $table->id;
    }

    private function siteMenuType(): string
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('menutype'))
            ->from($this->db->quoteName('#__menu_types'))
            ->where($this->db->quoteName('client_id') . ' = 0')
            ->order(
                'CASE WHEN ' . $this->db->quoteName('menutype') . ' = ' . $this->db->quote('mainmenu')
                . ' THEN 0 ELSE 1 END, ' . $this->db->quoteName('id') . ' ASC'
            )
            ->setLimit(1);
        $menutype = (string) $this->db->setQuery($query)->loadResult();

        if ($menutype === '') {
            throw new \RuntimeException('No site menu is available.');
        }

        return $menutype;
    }

    private function componentId(): int
    {
        $query = $this->db->createQuery()
            ->select($this->db->quoteName('extension_id'))
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_breezingformsng'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'));

        return (int) $this->db->setQuery($query)->loadResult();
    }
}
