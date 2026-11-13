<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Api\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;
use Vcmb\Component\BreezingformsNG\Administrator\Mcp\AuditReportFilter;
use Vcmb\Component\BreezingformsNG\Administrator\Service\AreaAccess;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseAuditService;
use Vcmb\Component\BreezingformsNG\Administrator\Service\DatabaseRepairService;

/**
 * The database audit of the About screen, and the repairs of its orphan rows,
 * through the Joomla API (token of the user, "API login" permission):
 *
 * - GET    api/index.php/v1/breezingformsng/audit
 * - DELETE api/index.php/v1/breezingformsng/audit/orphan-elements
 * - DELETE api/index.php/v1/breezingformsng/audit/orphan-values[/<element id>]
 *
 * The routes are not public: the API application authenticates the request
 * before this controller runs. Each action then asks for the same right as
 * its button of the About screen.
 */
class AuditController extends BaseController
{
    /** The audit report, filtered as it is for an AI client: no secret, no absolute path. */
    public function report(): void
    {
        $user = $this->requireUser();

        if (!$user->authorise('core.manage', 'com_breezingformsng')) {
            $this->respond(403, null, 'You are not allowed to use BreezingForms NG.');

            return;
        }

        $report = $this->getAuditService()->run();

        $this->respond(200, AuditReportFilter::filter($report, JPATH_ROOT));
    }

    /** Deletes the elements whose form no longer exists. */
    public function deleteOrphanElements(): void
    {
        if (!$this->canRepair()) {
            return;
        }

        $this->respond(200, ['deleted' => $this->getRepairService()->deleteOrphanElements()]);
    }

    /** Deletes the values whose element no longer exists: one element, or all of them. */
    public function deleteOrphanValues(): void
    {
        if (!$this->canRepair()) {
            return;
        }

        $rawId = $this->input->get('element', null, 'raw');

        if ($rawId !== null && (!is_scalar($rawId) || !preg_match('/\A[0-9]+\z/', (string) $rawId))) {
            $this->respond(400, null, 'Invalid element ID.');

            return;
        }

        $elementId = $rawId === null ? null : (int) $rawId;

        $this->respond(200, [
            'element' => $elementId,
            'deleted' => $this->getRepairService()->deleteOrphanValues($elementId),
        ]);
    }

    private function canRepair(): bool
    {
        if (AreaAccess::canRepair($this->requireUser())) {
            return true;
        }

        $this->respond(403, null, 'You are not allowed to repair the BreezingForms NG database.');

        return false;
    }

    private function requireUser(): \Joomla\CMS\User\User
    {
        $user = $this->requireApplication()->getIdentity();

        if ($user === null || $user->guest) {
            throw new \RuntimeException('The API application authenticates the user before this controller.');
        }

        return $user;
    }

    private function requireApplication(): CMSApplication
    {
        $app = $this->app;

        if (!$app instanceof CMSApplication) {
            throw new \RuntimeException('The audit endpoints run in the API application.');
        }

        return $app;
    }

    private function getAuditService(): DatabaseAuditService
    {
        return new DatabaseAuditService(
            Factory::getContainer()->get(DatabaseInterface::class),
            (string) $this->requireApplication()->get('tmp_path', JPATH_ROOT . '/tmp')
        );
    }

    private function getRepairService(): DatabaseRepairService
    {
        return new DatabaseRepairService(
            Factory::getContainer()->get(DatabaseInterface::class),
            (string) $this->requireApplication()->get('tmp_path', JPATH_ROOT . '/tmp')
        );
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function respond(int $status, ?array $data, string $message = ''): void
    {
        $app = $this->requireApplication();
        $app->setHeader('status', (string) $status, true);
        $app->setHeader('Cache-Control', 'no-store', true);
        $app->setHeader('X-Content-Type-Options', 'nosniff', true);
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);

        echo json_encode(
            ['success' => $status < 400, 'messages' => $message === '' ? [] : [$message], 'data' => $data],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
