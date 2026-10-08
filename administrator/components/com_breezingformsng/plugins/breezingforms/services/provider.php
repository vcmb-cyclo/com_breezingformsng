<?php

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Database\DatabaseInterface;
use Vcmb\Plugin\Content\Breezingforms\Extension\Breezingforms;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, static function (Container $container): PluginInterface {
            $plugin = new Breezingforms((array) PluginHelper::getPlugin('content', 'breezingforms'));
            $plugin->setApplication(Factory::getApplication());
            $plugin->setDatabase($container->get(DatabaseInterface::class));

            return $plugin;
        });
    }
};
