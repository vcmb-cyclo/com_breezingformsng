<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Helper\ServerTechnology;
use Akeeba\Component\AdminTools\Administrator\Helper\Storage;
use Akeeba\Component\AdminTools\Administrator\Scanner\Complexify;
use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\ParameterType;
use Akeeba\Plugin\System\AdminTools\Extension\AdminTools;
use Joomla\Utilities\IpHelper as Ip;
use RuntimeException;
use Throwable;

#[\AllowDynamicProperties]
class ControlpanelModel extends BaseDatabaseModel
{
	/**
	 * The extension ID of the System - Admin Tools plugin
	 *
	 * @var  int
	 */
	static $pluginId = null;

	/**
	 * Get the extension ID of the System - Admin Tools plugin
	 *
	 * @return  int
	 */
	public function getPluginID(): ?int
	{
		if (is_null(static::$pluginId))
		{
			// type name params id
			$plugin = PluginHelper::getPlugin('system', 'admintools');

			static::$pluginId = empty($plugin) ? null : $plugin->id;
		}

		// Joomla! 5 does not return the plugin IDs for disabled plugins. MAXIMUM EFFORT!
		if (is_null(static::$pluginId))
		{
			$db               = $this->getDatabase();
			$query            = $db->getQuery(true)
				->select($db->quoteName('extension_id'))
				->from($db->quoteName('#__extensions'))
				->where(
					[
						$db->quoteName('type') . ' = ' . $db->quote('plugin'),
						$db->quoteName('folder') . ' = ' . $db->quote('system'),
						$db->quoteName('element') . ' = ' . $db->quote('admintools'),
					]
				);

			try
			{
				static::$pluginId = $db->setQuery($query)->loadResult() ?: null;
			}
			catch (Throwable $e)
			{
				static::$pluginId = null;
			}
		}

		return static::$pluginId;
	}

	/**
	 * Makes sure our system plugin is really the very first system plugin to execute
	 *
	 * @return  void
	 */
	public function reorderPlugin()
	{
		// Get our plugin's ID
		$id = $this->getPluginID();

		// The plugin is not enabled, there's no point in continuing
		if (!PluginHelper::isEnabled('system', 'admintools') || empty($id))
		{
			return;
		}

		// Get a list of ordering values per ID
		$db = $this->getDatabase();

		$query         = $db->getQuery(true)
			->select([
				$db->qn('extension_id'),
				$db->qn('ordering'),
			])
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('plugin'))
			->where($db->qn('folder') . ' = ' . $db->q('system'))
			->order($db->qn('ordering') . ' ASC');
		$orderingPerId = $db->setQuery($query)->loadAssocList('extension_id', 'ordering');

		$orderings   = array_unique(array_values($orderingPerId));
		$minOrdering = reset($orderings);
		$myOrdering  = $orderingPerId[$id];

		reset($orderings);
		$sharedOrderings = 0;

		foreach ($orderingPerId as $order)
		{
			if ($order > $myOrdering)
			{
				break;
			}

			if ($order == $myOrdering)
			{
				$sharedOrderings++;
			}
		}

		// Do I need to reorder the plugin?
		if (($myOrdering > $minOrdering) || ($sharedOrderings > 1))
		{
			$query = $db->getQuery(true)
				->update($db->qn('#__extensions'))
				->set($db->qn('ordering') . ' = ' . $db->q($minOrdering - 1))
				->where($db->qn('extension_id') . ' = ' . $db->q($id));
			$db->setQuery($query);
			$db->execute();

			// Reset the Joomla! plugins cache
			Factory::getApplication()->bootComponent('com_admintools')->getCacheCleanerService()->clearGroups(['com_plugins']);
		}
	}

	/**
	 * Does the user need to enter a Download ID in the component's Options page?
	 *
	 * @return  bool
	 */
	public function needsDownloadID(): bool
	{
		// Do I need a Download ID?
		if (!ADMINTOOLS_PRO)
		{
			return false;
		}

		/** @var UpdatesModel $updateModel */
		$updateModel = $this->getMVCFactory()->createModel('Updates', 'Administrator', ['ignore_request' => true]);
		$dlid        = $updateModel->sanitizeLicenseKey($updateModel->getLicenseKey());

		return !$updateModel->isValidLicenseKey($dlid);
	}

	/**
	 * Checks all the available places if we just blocked our own IP?
	 *
	 * @param   string  $externalIp  Additional IP address to check
	 *
	 * @return  bool
	 */
	public function isMyIPBlocked($externalIp = null): bool
	{
		if ((defined('ADMINTOOLS_PRO') ? ADMINTOOLS_PRO : 0) != 1)
		{
			return false;
		}

		// First let's get the current IP of the user
		$ipList = [$this->getVisitorIP(), $externalIp];
		$ipList = array_filter($ipList, function ($x) {
			return !empty($x);
		});

		// Check if the IP appears verbatim in the automatic IP block, history of automatic IP blocks or IP deny list
		$db     = $this->getDatabase();
		$tables = [
			'#__admintools_ipautoban',
			'#__admintools_ipautobanhistory',
			'#__admintools_ipblock',
		];

		foreach ($tables as $table)
		{
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName($table))
				->whereIn($db->quoteName('ip'), $ipList, ParameterType::STRING);

			if (($db->setQuery($query)->loadResult() ?: 0) > 0)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Update the cached live site's URL for the front-end scheduling feature
	 *
	 * @return  void
	 */
	public function updateMagicParameters()
	{
		$cParams = ComponentHelper::getParams($this->option);

		$cParams->set('siteurl', Uri::root());

		Factory::getApplication()->bootComponent('com_admintools')->getComponentParametersService()->save($cParams);
	}

	/**
	 * Performs some checks about Joomla configuration (log and tmp path correctly set)
	 *
	 * @return  string|bool  Warning message. Boolean FALSE if no warning is found.
	 */
	public function checkJoomlaConfiguration(bool $forceDisplayForDebug = false)
	{
		if ($forceDisplayForDebug)
		{
			return "This is a dummy string used for testing.";
		}

		// Get the absolute path to the site's root
		$absoluteRoot = @realpath(JPATH_ROOT);
		$siteRoot     = empty($absoluteRoot) ? JPATH_ROOT : $absoluteRoot;

		// First of all, do we have a VALID log folder?
		$logDir = Factory::getApplication()->get('log_path');

		if (!$logDir || !@is_writable($logDir))
		{
			return Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_JCONFIG_INVALID_LOGDIR');
		}

		if ($siteRoot == $logDir)
		{
			return Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_JCONFIG_LOGDIR_SITEROOT');
		}

		// Do we have a VALID tmp folder?
		$tempDir = Factory::getApplication()->get('tmp_path');

		if (!$tempDir || !@is_writable($tempDir))
		{
			return Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_JCONFIG_INVALID_TMPDIR');
		}

		if ($siteRoot == $tempDir)
		{
			return Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_JCONFIG_TMPDIR_SITEROOT');
		}

		return false;
	}

	/**
	 * Do I need to show the Quick Setup Wizard?
	 *
	 * @return  bool
	 */
	public function needsQuickSetupWizard(): bool
	{
		$params = Storage::getInstance();

		return $params->getValue('quickstart', 0) == 0;
	}

	/**
	 * Get the most likely visitor IP address, reported by the server
	 *
	 * @return  string
	 */
	public function getVisitorIP(): string
	{
		$internalIP = Ip::getIp();

		if ((strpos($internalIP, '::') === 0) && (strstr($internalIP, '.') !== false))
		{
			$internalIP = substr($internalIP, 2);
		}

		return $internalIP;
	}

	/**
	 * Is the System - Admin Tools plugin installed?
	 *
	 * @return  bool
	 *
	 * @since  4.3.0
	 */
	public function isPluginInstalled(): bool
	{
		return !empty($this->getPluginID());
	}

	/**
	 * Is the System - Admin Tools plugin currently loaded?
	 *
	 * @return  bool
	 *
	 * @since   4.3.0
	 */
	public function isPluginLoaded(): bool
	{
		return class_exists(AdminTools::class, false);
	}

	/**
	 * Is the Admintools.php file renamed?
	 *
	 * @return  bool
	 *
	 * @since   4.3.0
	 */
	public function isMainPhpDisabled(): bool
	{
		$file      = JPATH_PLUGINS . '/system/admintools/services/provider.php';
		$folder    = dirname($file);
		$hasFolder = @file_exists($folder) && @is_dir($folder);
		$hasFile   = @file_exists($file) && @is_file($file);

		if ($hasFolder && !$hasFile)
		{
			return true;
		}

		return false;
	}

	/**
	 * Rename the disabled Admintools.php file back to its proper, main.php, name.
	 *
	 * @return  bool
	 *
	 * @since   4.3.0
	 */
	public function reenableMainPhp(): bool
	{
		$altName = $this->getRenamedMainPhp();

		if (!$altName)
		{
			return false;
		}

		$to     = JPATH_PLUGINS . '/system/admintools/services/provider.php';
		$folder = dirname($to);
		$from   = $folder . '/' . $altName;

		if (!@rename($from, $to))
		{
			$res = File::copy($from, $to) && File::delete($from);
		}

		return $res ?? true;
	}

	/**
	 * Get the file name under which Admintools.php has been renamed to
	 *
	 * @return  string|null
	 *
	 * @since   4.3.0
	 */
	public function getRenamedMainPhp(): ?string
	{
		$possibleNames = [
			'provider-disable.php',
			'provider.php.bak',
			'provider.bak.php',
			'provider.bak',
			'-provider.php',
		];

		$folder = JPATH_PLUGINS . '/system/admintools/services';

		foreach ($possibleNames as $baseName)
		{
			if (@file_exists($folder . '/' . $baseName))
			{
				return $baseName;
			}
		}

		return null;
	}

	/**
	 * Delete old log files (with a .log extension) always. If the logging feature is disabled (either the text debug
	 * log or logging in general) also delete the .php log files.
	 *
	 * @since  5.1.0
	 */
	public function deleteOldLogs()
	{
		$logpath = Factory::getApplication()->get('log_path');
		$files   = [
			$logpath . DIRECTORY_SEPARATOR . 'admintools_blocked.log',
			$logpath . DIRECTORY_SEPARATOR . 'admintools_blocked.log.1',
		];

		$wafParams = Storage::getInstance();
		$textLogs  = $wafParams->getValue('logfile', 0);
		$allLogs   = $wafParams->getValue('logbreaches', 1);

		if (!$textLogs || !$allLogs)
		{
			$files = array_merge($files, [
				$logpath . DIRECTORY_SEPARATOR . 'admintools_blocked.php',
				$logpath . DIRECTORY_SEPARATOR . 'admintools_blocked.1.php',

			]);
		}

		foreach ($files as $file)
		{
			File::delete($file);
		}
	}

	/**
	 * Checks if the current contents of the server configuration file (ie .htaccess) match with the saved one.
	 */
	public function serverConfigEdited(): bool
	{
		// Core version? No need to continue
		if (!defined('ADMINTOOLS_PRO') || !ADMINTOOLS_PRO)
		{
			return false;
		}

		// User decided to ignore any warning about manual edits
		$cParams = ComponentHelper::getParams($this->option);

		if (!$cParams->get('serverconfigwarn', 1))
		{
			return false;
		}

		$modelTech = '';

		if (ServerTechnology::isNginxSupported() == 1)
		{
			$modelTech = 'Nginxconfmaker';
			$checkLine = "Security Enhanced & Highly Optimized NginX Configuration File for Joomla!";
		}
		elseif (ServerTechnology::isWebConfigSupported() == 1)
		{
			$modelTech = 'Webconfigmaker';
			$checkLine = "Security Enhanced & Highly Optimized .web.config File for Joomla!";
		}
		elseif (ServerTechnology::isHtaccessSupported() == 1)
		{
			$modelTech = 'Htaccessmaker';
			$checkLine = "Security Enhanced & Highly Optimized .htaccess File for Joomla!";
		}
		else
		{
			// Can't understand the Server Technology we're on, let's stop here
			return false;
		}

		try
		{
			/** @var ServerconfigmakerModel $serverModel */
			$serverModel = $this->getMVCFactory()->createModel($modelTech, 'Administrator');
		}
		catch (Exception $e)
		{
			return false;
		}

		$serverFile = $serverModel->getConfigFileName(true);

		if (!file_exists($serverFile))
		{
			return false;
		}

		$actualContents = file_get_contents($serverFile);

		if ($actualContents === false)
		{
			return false;
		}

		// Check if the file was generated by Admin Tools
		if (strpos($actualContents, $checkLine) === false)
		{
			return false;
		}

		if (strpos($actualContents, 'automatically generated by Admin Tools') === false)
		{
			return false;
		}

		$currentContents = $serverModel->makeConfigFile();

		// Is the hash of current file different from the saved one? If so, warn the user
		return ($serverModel->getConfigHash($actualContents) != $serverModel->getConfigHash($currentContents));
	}

	/**
	 * Check the strength of the Secret Word for front-end and remote scans. If it is insecure return the reason it
	 * is insecure as a string. If the Secret Word is secure return an empty string.
	 *
	 * @return  string
	 */
	public function getFrontendSecretWordError(): string
	{
		$params = ComponentHelper::getParams($this->option);

		// Is frontend backup enabled?
		$febEnabled = $params->get('frontend_enable', 0) != 0;

		if (!$febEnabled)
		{
			return '';
		}

		$secretWord = $params->get('frontend_secret_word', '');

		try
		{
			Complexify::isStrongEnough($secretWord);
		}
		catch (RuntimeException $e)
		{
			// Ah, the current Secret Word is bad. Create a new one if necessary.
			$newSecret = Factory::getApplication()->getSession()->get('admintools.cpanel.newSecretWord', null);

			if (empty($newSecret))
			{
				$newSecret = UserHelper::genRandomPassword(32);
				Factory::getApplication()->getSession()->set('admintools.cpanel.newSecretWord', $newSecret);
			}

			return $e->getMessage();
		}

		return '';
	}

}