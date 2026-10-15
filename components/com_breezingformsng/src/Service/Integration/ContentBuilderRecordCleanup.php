<?php

/**
 * BreezingForms NG - A Joomla Forms Application
 *
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 *
 * SPDX-License-Identifier: GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Integration;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Event\Model\AfterDeleteEvent;
use Joomla\CMS\Event\Model\BeforeDeleteEvent;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Removes what ContentBuilder NG keeps about submissions that are deleted
 * on the site: record metadata, list states, article links and, when the
 * ContentBuilder NG form deletes its articles along with their records, the
 * generated articles themselves.
 *
 * Articles are deleted through their table rather than the article model:
 * the model only deletes trashed articles and requires the delete permission,
 * which a visitor replacing their own submission does not have.
 */
final class ContentBuilderRecordCleanup
{
    private const TYPE = 'com_breezingformsng';

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly CMSApplication $app,
        private readonly CacheControllerFactoryInterface $cacheControllerFactory,
    ) {
    }

    /**
     * @param int[] $recordIds
     */
    public function remove(int $formId, array $recordIds): void
    {
        $recordIds = array_values(array_filter(array_map('intval', $recordIds)));

        if ($recordIds === [] || !ContentBuilderInstallation::isInstalled()) {
            return;
        }

        $db = $this->db;
        $type = self::TYPE;

        $query = $db->createQuery()
            ->delete($db->quoteName('#__contentbuilderng_records'))
            ->where($db->quoteName('type') . ' = :type')
            ->where($db->quoteName('reference_id') . ' = :formId')
            ->whereIn($db->quoteName('record_id'), $recordIds)
            ->bind(':type', $type)
            ->bind(':formId', $formId, ParameterType::INTEGER);
        $db->setQuery($query)->execute();

        $query = $db->createQuery()
            ->select($db->quoteName(['id', 'delete_articles']))
            ->from($db->quoteName('#__contentbuilderng_forms'))
            ->where($db->quoteName('type') . ' = :type')
            ->where($db->quoteName('reference_id') . ' = :formId')
            ->bind(':type', $type)
            ->bind(':formId', $formId, ParameterType::INTEGER);
        $deletesArticles = $db->setQuery($query)->loadAssocList('id', 'delete_articles');
        $cbFormIds = array_map('intval', array_keys($deletesArticles));

        if ($cbFormIds === []) {
            return;
        }

        $query = $db->createQuery()
            ->delete($db->quoteName('#__contentbuilderng_list_records'))
            ->whereIn($db->quoteName('form_id'), $cbFormIds)
            ->whereIn($db->quoteName('record_id'), $recordIds);
        $db->setQuery($query)->execute();

        $query = $db->createQuery()
            ->select($db->quoteName(['article_id', 'form_id']))
            ->from($db->quoteName('#__contentbuilderng_articles'))
            ->whereIn($db->quoteName('form_id'), $cbFormIds)
            ->whereIn($db->quoteName('record_id'), $recordIds);
        $articleIds = [];

        foreach ($db->setQuery($query)->loadAssocList() as $article) {
            if ((int) $deletesArticles[$article['form_id']] === 1) {
                $articleIds[] = (int) $article['article_id'];
            }
        }

        if ($articleIds !== []) {
            $this->deleteArticles(array_values(array_unique($articleIds)));
        }

        $query = $db->createQuery()
            ->delete($db->quoteName('#__contentbuilderng_articles'))
            ->whereIn($db->quoteName('form_id'), $cbFormIds)
            ->whereIn($db->quoteName('record_id'), $recordIds);
        $db->setQuery($query)->execute();
    }

    /**
     * @param int[] $articleIds
     */
    private function deleteArticles(array $articleIds): void
    {
        $component = $this->app->bootComponent('com_content');

        if (!$component instanceof MVCFactoryServiceInterface) {
            throw new \RuntimeException('com_content is not available.');
        }

        PluginHelper::importPlugin('content');
        $dispatcher = $this->app->getDispatcher();
        $context = 'com_content.article';

        foreach ($articleIds as $articleId) {
            $table = $component->getMVCFactory()->createTable('Article', 'Administrator');

            if (!$table->load($articleId)) {
                continue;
            }

            $dispatcher->dispatch(
                'onContentBeforeDelete',
                new BeforeDeleteEvent('onContentBeforeDelete', ['context' => $context, 'subject' => $table])
            );
            $table->delete();
            $dispatcher->dispatch(
                'onContentAfterDelete',
                new AfterDeleteEvent('onContentAfterDelete', ['context' => $context, 'subject' => $table])
            );
        }

        $db = $this->db;
        $query = $db->createQuery()
            ->delete($db->quoteName('#__workflow_associations'))
            ->where($db->quoteName('extension') . ' = :extension')
            ->whereIn($db->quoteName('item_id'), $articleIds)
            ->bind(':extension', $context);
        $db->setQuery($query)->execute();

        $this->cacheControllerFactory->createCacheController('callback', ['defaultgroup' => 'com_content'])->clean();
        $this->cacheControllerFactory->createCacheController('callback', ['defaultgroup' => 'com_contentbuilderng'])->clean();
    }
}
