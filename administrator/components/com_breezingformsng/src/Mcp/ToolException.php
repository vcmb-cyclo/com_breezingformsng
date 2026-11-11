<?php

/**
 * @package BreezingFormsNG
 * @copyright Copyright (C) 2026 by XDA+GIL
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Vcmb\Component\BreezingformsNG\Administrator\Mcp;

\defined('_JEXEC') or die;

/**
 * A tool that could not do what was asked, for a reason the model can read
 * and act on: its message is returned to the client, so it never holds
 * internal details.
 */
final class ToolException extends \RuntimeException
{
}
