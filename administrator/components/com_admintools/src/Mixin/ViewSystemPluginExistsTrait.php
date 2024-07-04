<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Mixin;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Model\ControlpanelModel;
use Akeeba\Plugin\System\AdminTools\Utility\RescueUrl;
use Joomla\CMS\Plugin\PluginHelper;

trait ViewSystemPluginExistsTrait
{
	/**
	 * Does the system plugin exist?
	 *
	 * @var  bool
	 */
	public $pluginExists = false;

	/**
	 * Is the system plugin enabled?
	 *
	 * @var  bool
	 */
	public $pluginActive = false;

	/**
	 * Is the plugin currently loaded?
	 *
	 * @var  bool
	 */
	public $pluginLoaded = false;

	/**
	 * Is main.php renamed to something else?
	 *
	 * @var  bool
	 */
	public $isMainPhpDisabled = false;

	/**
	 * What is the plugin's main.php file currently renamed to?
	 *
	 * @var  string
	 */
	public $mainPhpRenamedTo = false;

	/**
	 * Is Rescue Mode activated?
	 *
	 * @var  bool
	 */
	public $isRescueMode = false;

	protected function populateSystemPluginExists()
	{
		/** @var ControlpanelModel $cPanelModel */
		$cPanelModel = $this->getModel('Controlpanel') ?: $this->getModel();

		// Does the plugin exist in the filesystem?
		$this->pluginExists = @is_dir(JPATH_ROOT . '/plugins/system/admintools');

		if (!$this->pluginExists)
		{
			return;
		}

		// Is the plugin enabled in the database?
		$this->pluginActive = PluginHelper::isEnabled('system', 'admintools');

		if (!$this->pluginActive)
		{
			return;
		}

		// Is Rescue Mode enabled?
		$this->isRescueMode = class_exists(RescueUrl::class, true) ? RescueUrl::isRescueMode() : false;

		if ($this->isRescueMode)
		{
			return;
		}

		// Is the plugin currently loaded
		$this->pluginLoaded = $cPanelModel->isPluginLoaded();

		if ($this->pluginLoaded)
		{
			return;
		}

		// Is main.php renamed?
		$this->isMainPhpDisabled = $cPanelModel->isMainPhpDisabled();

		if (!$this->isMainPhpDisabled)
		{
			return;
		}

		// What is main.php renamed to?
		$this->mainPhpRenamedTo = $cPanelModel->getRenamedMainPhp();
	}

}