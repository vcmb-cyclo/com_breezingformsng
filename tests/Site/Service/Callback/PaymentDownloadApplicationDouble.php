<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Tests\Site\Service\Callback;

use Joomla\CMS\Application\CMSApplication;

final class PaymentDownloadApplicationDouble extends CMSApplication
{
    /** @var list<string> */
    public array $messages = [];

    public bool $closed = false;

    public function enqueueMessage(string $message, string $type = 'message'): void
    {
        $this->messages[] = $message;
    }

    public function redirect(string $url, int $status = 303, bool $moved = true): void
    {
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
