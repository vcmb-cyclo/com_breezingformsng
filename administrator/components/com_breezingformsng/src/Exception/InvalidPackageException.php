<?php

namespace Vcmb\Component\BreezingformsNG\Administrator\Exception;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

final class InvalidPackageException extends \RuntimeException
{
    public const INVALID = 'invalid';
    public const JSON = 'json';
    public const VERSION = 'version';
    public const READ = 'read';

    public function __construct(?\Throwable $previous = null, private readonly string $reason = self::INVALID)
    {
        $key = $reason === self::READ
            ? 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_FILE_READ_ERROR'
            : 'COM_BREEZINGFORMSNG_PACKAGE_TRANSFER_INVALID_FILE';
        parent::__construct(Text::_($key), 0, $previous);
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
