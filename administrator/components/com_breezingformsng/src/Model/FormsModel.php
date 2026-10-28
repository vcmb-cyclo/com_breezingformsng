<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class FormsModel extends ListModel
{
    private const SORTS = ['id', 'title', 'name', 'pages', 'published', 'ordering', 'modified'];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct($config = [], ?MVCFactoryInterface $factory = null)
    {
        $config['filter_fields'] ??= [...self::SORTS, 'package'];

        parent::__construct($config, $factory);
    }

    private function db(): DatabaseInterface
    {
        return $this->getDatabase();
    }

    protected function populateState($ordering = 'ordering', $direction = 'ASC')
    {
        parent::populateState($ordering, $direction);
    }

    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.package');
        $id .= ':' . $this->getState('filter.published');

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db = $this->db();
        $editor = $db->createQuery()
            ->select($db->quoteName('editor.name'))
            ->from($db->quoteName('#__users', 'editor'))
            ->where($db->quoteName('editor.id') . ' = ' . $db->quoteName('#__facileforms_forms.checked_out'));
        $query = $db->createQuery()
            ->select($db->quoteName([
                'id', 'title', 'name', 'pages', 'published', 'ordering', 'modified', 'checked_out', 'checked_out_time',
            ]))
            ->select('(' . $editor . ') AS ' . $db->quoteName('editor'))
            ->from($db->quoteName('#__facileforms_forms'));

        $package = (string) $this->getState('filter.package');

        if ($package !== '') {
            $query->where($db->quoteName('package') . ' = :package')
                ->bind(':package', $package);
        }

        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $published = (int) $published;
            $query->where($db->quoteName('published') . ' = :published')
                ->bind(':published', $published, ParameterType::INTEGER);
        }

        $search = trim((string) $this->getState('filter.search'));

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(
                '(' . $db->quoteName('title') . ' LIKE :searchTitle'
                . ' OR ' . $db->quoteName('name') . ' LIKE :searchName'
                . ' OR ' . $db->quoteName('description') . ' LIKE :searchDescription)'
            )
                ->bind(':searchTitle', $like)
                ->bind(':searchName', $like)
                ->bind(':searchDescription', $like);
        }

        $ordering = (string) $this->getState('list.ordering', 'ordering');
        $ordering = in_array($ordering, self::SORTS, true) ? $ordering : 'ordering';
        $direction = strtoupper((string) $this->getState('list.direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        return $query->order($db->quoteName($ordering) . ' ' . $direction . ', ' . $db->quoteName('id') . ' DESC');
    }

    /**
     * @return list<\stdClass>
     */
    public function getScripts(string $type): array
    {
        $db = $this->db();
        $q  = $db->createQuery()
            ->select([$db->quoteName('id'), "CONCAT(" . $db->quoteName('package') . ", '::', " . $db->quoteName('name') . ") AS text"])
            ->from($db->quoteName('#__facileforms_scripts'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote($type))
            ->order('text, ' . $db->quoteName('id') . ' DESC');

        return $db->setQuery($q)->loadObjectList() ?: [];
    }

    /**
     * @return list<\stdClass>
     */
    public function getPieces(string $type): array
    {
        $db = $this->db();
        $q  = $db->createQuery()
            ->select([$db->quoteName('id'), "CONCAT(" . $db->quoteName('package') . ", '::', " . $db->quoteName('name') . ") AS text"])
            ->from($db->quoteName('#__facileforms_pieces'))
            ->where($db->quoteName('published') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote($type))
            ->order('text, ' . $db->quoteName('id') . ' DESC');

        return $db->setQuery($q)->loadObjectList() ?: [];
    }
}
