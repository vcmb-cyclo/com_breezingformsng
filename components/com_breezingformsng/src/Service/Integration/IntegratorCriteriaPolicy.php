<?php

declare(strict_types=1);

namespace Vcmb\Component\BreezingformsNG\Site\Service\Integration;

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/** Shared allowlists for Integrator criteria that become SQL syntax. */
final class IntegratorCriteriaPolicy
{
    private const OPERATORS = ['=', '<>', '>', '<', '>=', '<=', '%...%', '%...', '...%'];

    public static function allowsOperator(string $operator): bool
    {
        return \in_array($operator, self::OPERATORS, true);
    }

    public static function allowsConnector(string $connector): bool
    {
        return \in_array($connector, ['AND', 'OR'], true);
    }
}
