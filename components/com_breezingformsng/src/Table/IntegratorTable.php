<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

/** Joomla table mapping for Integrator rules used by content history. */
final class IntegratorTable extends Table
{
    /** @var int|string|null */
    public $id = null;

    /** @var int|string|null */
    public $form_id = null;

    public function __construct(DatabaseInterface $db)
    {
        parent::__construct('#__facileforms_integrator_rules', 'id', $db);
    }
}
