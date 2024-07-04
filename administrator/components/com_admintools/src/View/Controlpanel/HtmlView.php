<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\View\Controlpanel;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Helper\ServerTechnology;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewLoadAnyTemplateTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewSystemPluginExistsTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ViewTaskBasedEventsTrait;
use Akeeba\Component\AdminTools\Administrator\Model\AdminpasswordModel;
use Akeeba\Component\AdminTools\Administrator\Model\ControlpanelModel;
use Akeeba\Component\AdminTools\Administrator\Model\MainpasswordModel;
use Akeeba\Component\AdminTools\Administrator\Model\UpdatesModel;
use Akeeba\Component\AdminTools\Administrator\Model\UsageStatisticsModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Throwable;

class HtmlView extends BaseHtmlView
{
	use ViewLoadAnyTemplateTrait;
	use ViewTaskBasedEventsTrait;
	use ViewSystemPluginExistsTrait;

	/**
	 * HTML of the processed CHANGELOG to display in the Changelog modal
	 *
	 * @var  string
	 */
	public $changeLog = '';

	/**
	 * Do I have to ask the user to provide a Download ID?
	 *
	 * @var  bool
	 */
	public $needsdlid = false;

	/**
	 * Is Joomla configuration ok? (log and tmp folders)
	 *
	 * @var  string
	 */
	public $jwarnings;

	/**
	 * Is this a pro version?
	 *
	 * @var  bool
	 */
	public $isPro;

	/**
	 * Should I display the security exceptions graphs?
	 *
	 * @var  bool
	 */
	public $showstats;

	/**
	 * Current user was blocked?
	 *
	 * @var  bool
	 */
	public $adminLocked;

	/**
	 * Do we have a valid password?
	 *
	 * @var  bool
	 */
	public $hasValidPassword;

	/**
	 * Is the Clean Temporary Directory feature available
	 *
	 * @var  bool
	 */
	public $enable_cleantmp;

	/**
	 * Is the Temporary and Log Folder Check feature available
	 *
	 * @var  bool
	 */
	public $enable_tmplogcheck;

	/**
	 * Is the Fix Permissions feature available
	 *
	 * @var  bool
	 */
	public $enable_fixperms;

	/**
	 * Is the Purge Sessions feature available
	 *
	 * @var  bool
	 */
	public $enable_purgesessions;

	/**
	 * Are the Database Tools features available
	 *
	 * @var  bool
	 */
	public $enable_dbtools;

	/**
	 * Is this a MySQL server
	 *
	 * @var  bool
	 */
	public $isMySQL;

	/**
	 * The extension ID of the System - Admin Tools plugin
	 *
	 * @var  int
	 */
	public $pluginid;

	/**
	 * The error string for the front-end secret word strength issue, blank if there is no problem
	 *
	 * @var  string
	 */
	public $frontEndSecretWordIssue;

	/**
	 * Proposed new secret word for the front-end file scanner feature
	 *
	 * @var  string
	 */
	public $newSecretWord;

	/**
	 * Is the .htaccess Maker feature supported on this server? 0 No, 1 Yes, 2 Maybe
	 *
	 * @var  int
	 */
	public $htMakerSupported;

	/**
	 * Is the NginX Conf Maker feature supported on this server? 0 No, 1 Yes, 2 Maybe
	 *
	 * @var  int
	 */
	public $nginxMakerSupported;

	/**
	 * Is the web.config Maker feature supported on this server? 0 No, 1 Yes, 2 Maybe
	 *
	 * @var  int
	 */
	public $webConfMakerSupported;

	/**
	 * The extension ID for Admin Tools
	 *
	 * @var  int
	 */
	public $extension_id;

	/**
	 * Do we need to run Quick Setup (i.e. not configured yet)?
	 *
	 * @var  bool
	 */
	public $needsQuickSetup = false;

	/**
	 * The fancy formatted changelog of the component
	 *
	 * @var  string
	 */
	public $formattedChangelog = '';

	public bool $debugAllPanels = false;

	/**
	 * Did the user manually changed the server configuration file (ie .htaccess)? If so, let's warn the user that he
	 * should use the custom rule fields inside the Makers or their settings could be lost.
	 *
	 * @var bool
	 */
	public $serverConfigEdited = false;

	/** @var int Update site ID */
	public $updateSiteId = 0;

	/**
	 * Main Control Panel task
	 *
	 * @return  void
	 */
	protected function onBeforeMain()
	{
		$cParams = ComponentHelper::getParams('com_admintools');
		$session = Factory::getApplication()->getSession();

		$this->populateSystemPluginExists();

		// Is this the Professional release?
		$this->isPro = (ADMINTOOLS_PRO ?? 0) == 1;

		// Should we show the stats and graphs?
		$this->showstats = $cParams->get('showstats', 1);

		// Load the models
		/** @var ControlpanelModel $controlPanelModel */
		$controlPanelModel = $this->getModel();

		/** @var AdminpasswordModel $adminPasswordModel */
		$adminPasswordModel = $this->getModel('Adminpassword');

		/** @var MainpasswordModel $masterPasswordModel */
		$masterPasswordModel = $this->getModel('Mainpassword');

		/** @var UpdatesModel $updatesModel */
		$updatesModel = $this->getModel('Updates');

		$relDate  = clone Factory::getDate(ADMINTOOLS_DATE ?? gmdate('Y-m-d'), 'UTC');
		$interval = time() - $relDate->toUnix();

		if ($interval > (60 * 60 * 24 * 180))
		{
			$this->oldVersion = true;
		}

		// Get the database type
		$dbType = $this->getModel()->getDbo()->getName();

		// Pass properties to the view
		$this->isMySQL              = stripos($dbType, 'mysql') !== false;
		$this->adminLocked          = $adminPasswordModel->isLocked();
		$this->hasValidPassword     = $masterPasswordModel->hasValidPassword();
		$this->enable_cleantmp      = $masterPasswordModel->accessAllowed('Cleantempdirectory');
		$this->enable_tmplogcheck   = $masterPasswordModel->accessAllowed('Checktempandlogdirectories');
		$this->enable_fixperms      = $masterPasswordModel->accessAllowed('Fixpermissions');
		$this->enable_purgesessions = $masterPasswordModel->accessAllowed('Databasetools');
		$this->enable_dbtools       = $masterPasswordModel->accessAllowed('Databasetools');
		$this->pluginid             = $controlPanelModel->getPluginID();

		$this->htMakerSupported      = ServerTechnology::isHtaccessSupported();
		$this->nginxMakerSupported   = ServerTechnology::isNginxSupported();
		$this->webConfMakerSupported = ServerTechnology::isWebConfigSupported();
		$this->serverConfigEdited    = $controlPanelModel->serverConfigEdited();
		$this->extension_id          = (int) $controlPanelModel->getState('extension_id', 0);
		$this->formattedChangelog    = $this->formatChangelog();
		$this->needsdlid             = $controlPanelModel->needsDownloadID();
		$this->needsQuickSetup       = $controlPanelModel->needsQuickSetupWizard();
		$this->updateSiteId          = $updatesModel->getUpdateSiteIds()[0];

		// Pro version secret word setup
		if (defined('ADMINTOOLS_PRO') && ADMINTOOLS_PRO)
		{
			$this->jwarnings               = $controlPanelModel->checkJoomlaConfiguration($this->debugAllPanels);
			$this->frontEndSecretWordIssue = $controlPanelModel->getFrontendSecretWordError();
			$this->newSecretWord           = $session->get('admintools.cpanel.newSecretWord', null);
		}

		try
		{
			$this
				->getModel('UsageStatistics')
				->collectStatistics();
		}
		catch (Throwable $e)
		{
			// This is allowed to fail gracefully.
		}

		$webAssetManager = $this->document->getWebAssetManager();
		$webAssetManager
			->useScript('com_admintools.control_panel');

		// Pro version, control panel graphs (only if we enabled them in config options)
		if (defined('ADMINTOOLS_PRO') && ADMINTOOLS_PRO && $this->showstats)
		{
			$webAssetManager
				->useScript('com_admintools.chart_moment_adapter');
		}

		// Push translations
		Text::script('COM_ADMINTOOLS_DATABASETOOLS_LBL_PURGESESSIONS_WARN', true);

		// Initialize some Javascript variables used in the view
		$this->document->addScriptOptions('admintools.Controlpanel.myIP', $controlPanelModel->getVisitorIP());

		// Set the toolbar title
		if (ADMINTOOLS_PRO)
		{
			ToolbarHelper::title(Text::_('COM_ADMINTOOLS_TITLE_DASHBOARD_PRO') . ' <small>' . ADMINTOOLS_VERSION . '</small>', 'admintools');
		}
		else
		{
			ToolbarHelper::title(Text::_('COM_ADMINTOOLS_TITLE_DASHBOARD_CORE') . ' <small>' . ADMINTOOLS_VERSION . '</small>', 'admintools');
		}

		ToolbarHelper::preferences('com_admintools');

		ToolbarHelper::help(null, false, 'https://www.akeeba.com/documentation/admin-tools-joomla/using-the-component.html#control-panel');

	}

	protected function formatChangelog($onlyLast = false)
	{
		$ret   = '';
		$file  = JPATH_ADMINISTRATOR . '/components/com_admintools/CHANGELOG.php';
		$lines = @file($file);

		if (empty($lines))
		{
			return $ret;
		}

		array_shift($lines);

		foreach ($lines as $line)
		{
			$line = trim($line);

			if (empty($line))
			{
				continue;
			}

			$type = substr($line, 0, 1);

			switch ($type)
			{
				case '=':
					continue 2;
					break;

				case '+':
					$ret .= "\t" . '<li><span class="badge bg-success">Added</span> ' . htmlentities(trim(substr($line, 2))) . "</li>\n";
					break;

				case '-':
					$ret .= "\t" . '<li><span class="badge bg-dark">Removed</span> ' . htmlentities(trim(substr($line, 2))) . "</li>\n";
					break;

				case '~':
				case '^':
					$ret .= "\t" . '<li><span class="badge bg-secondary">Changed</span> ' . htmlentities(trim(substr($line, 2))) . "</li>\n";
					break;

				case '*':
					$ret .= "\t" . '<li><span class="badge bg-danger">Security</span> ' . htmlentities(trim(substr($line, 2))) . "</li>\n";
					break;

				case '!':
					$ret .= "\t" . '<li><span class="badge bg-warning text-dark">Important</span> ' . htmlentities(trim(substr($line, 2))) . "</li>\n";
					break;

				case '#':
					$ret .= "\t" . '<li><span class="badge bg-info text-dark">Fixed</span> ' . htmlentities(trim(substr($line, 2))) . "</li>\n";
					break;

				default:
					if (!empty($ret))
					{
						$ret .= "</ul>";
						if ($onlyLast)
						{
							return $ret;
						}
					}

					if (!$onlyLast)
					{
						$ret .= "<h4>$line</h4>\n";
					}
					$ret .= "<ul class=\"akeeba-changelog\">\n";

					break;
			}
		}

		return $ret;
	}
}