<?php
/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Log\Log;
use Psr\Container\ContainerInterface;
use Vcmb\Component\BreezingformsNG\Site\Service\EngineDispatcher;

class BreezingFormsNGComponent extends MVCComponent implements RouterServiceInterface, BootableExtensionInterface
{
    use RouterServiceTrait;

    private EngineDispatcher $engineDispatcher;

    public function boot(ContainerInterface $container): void
    {
        $this->engineDispatcher = $container->get(EngineDispatcher::class);

        // Without a logger, the component's warnings and errors (payment
        // problems, refused submissions) would be dropped.
        Log::addLogger(
            ['text_file' => 'com_breezingformsng.php'],
            Log::ALL & ~Log::DEBUG,
            ['com_breezingformsng']
        );
    }

    public function getEngineDispatcher(): EngineDispatcher
    {
        return $this->engineDispatcher;
    }
}
