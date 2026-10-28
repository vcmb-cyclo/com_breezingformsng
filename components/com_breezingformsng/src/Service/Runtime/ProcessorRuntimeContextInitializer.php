<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2024-2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Runtime;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Vcmb\Component\BreezingformsNG\Site\Configuration\FormConfiguration;
use Vcmb\Component\BreezingformsNG\Site\Table\FormTable;

/** Assembles the request-dependent state needed by the processor facade. */
final class ProcessorRuntimeContextInitializer
{
    public function __construct(
        private readonly RequestMetadataResolver $requestMetadataResolver,
        private readonly FormDisplayContextResolver $displayContextResolver,
        private readonly FormPathResolver $formPathResolver,
        private readonly SubmissionTimestampFactory $timestampFactory
    ) {
    }

    /** @param FormTable|\stdClass $formrow */
    public function initialize(
        CMSApplication $application,
        FormConfiguration $configuration,
        object $formrow,
        int $runMode,
        bool $inFrame,
        int $formId,
        int $page,
        string $sitePath,
        string $siteUrl
    ): ProcessorRuntimeContext {
        $requestMetadata = $this->requestMetadataResolver->resolve(
            $application->getInput()->server->getString('REMOTE_ADDR', ''),
            (string) $configuration->disable_ip === '1',
            (int) $configuration->getprovider !== 0,
            Text::_('COM_BREEZINGFORMSNG_PROCESS_UNKNOWN')
        );
        $displayContext = $this->displayContextResolver->resolve(
            $runMode,
            $inFrame,
            $formId,
            (int) $formrow->runmode,
            (bool) $formrow->published,
            (int) $formrow->prevmode,
            (int) $configuration->gridshow === 1,
            (int) $configuration->gridsize,
            $siteUrl
        );
        $formPaths = $this->formPathResolver->resolve(
            $page,
            (int) $formrow->pages,
            (string) $formrow->name,
            (string) $formrow->title,
            $displayContext->homepage,
            $sitePath,
            $siteUrl,
            (string) $configuration->images,
            (string) $configuration->uploads
        );

        return new ProcessorRuntimeContext(
            $requestMetadata,
            $this->timestampFactory->create((string) $application->get('offset')),
            $displayContext,
            $formPaths
        );
    }
}
