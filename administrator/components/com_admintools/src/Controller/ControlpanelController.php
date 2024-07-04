<?php
/**
 * @package   admintools
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AdminTools\Administrator\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\AdminTools\Administrator\Helper\ServerTechnology;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerCustomACLTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerEventsTrait;
use Akeeba\Component\AdminTools\Administrator\Mixin\ControllerReusableModelsTrait;
use Akeeba\Component\AdminTools\Administrator\Model\ControlpanelModel;
use Akeeba\Component\AdminTools\Administrator\Model\MainpasswordModel;
use Akeeba\Component\AdminTools\Administrator\Model\ServerconfigmakerModel;
use Akeeba\Component\AdminTools\Administrator\Model\UnblockipModel;
use Akeeba\Component\AdminTools\Administrator\Model\UpdatesModel;
use Akeeba\Component\AdminTools\Administrator\Model\UpgradeModel;
use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\UserHelper;
use RuntimeException;

class ControlpanelController extends BaseController
{
	use ControllerEventsTrait;
	use ControllerCustomACLTrait;
	use ControllerReusableModelsTrait;

	/**
	 * Dummy method to test the custom error page.
	 *
	 * @throws RuntimeException Always thrown :)
	 */
	public function helloerror()
	{
		throw new RuntimeException('This is an error');
	}

	public function onBeforeMain()
	{
		/** @var ControlpanelModel $model */
		$model = $this->getModel();

		// Update the magic parameters
		$model->updateMagicParameters();

		// Delete the old log files if logging is disabled
		$model->deleteOldLogs();

		// Refresh the update site definitions if required.
		/** @var UpdatesModel $updateModel */
		$updateModel = $this->getModel('Updates', 'Administrator');
		$updateModel->refreshUpdateSite();

		// Make sure all of my extensions are assigned to my package.
		/** @var UpgradeModel $upgradeModel */
		$upgradeModel = $this->getModel('Upgrade', 'Administrator');
		$upgradeModel->init();
		$upgradeModel->adoptMyExtensions();

		// Reorder the Admin Tools plugin if necessary
		if (ComponentHelper::getParams('com_admintools')->get('reorderplugin', 1))
		{
			$model->reorderPlugin();
		}

		// Pass models to the view
		$view = $this->getView();
		$view->setModel($this->getModel('Adminpassword', 'Administrator'));
		$view->setModel($this->getModel('Mainpassword', 'Administrator'));
		$view->setModel($this->getModel('UsageStatistics', 'Administrator'));

		if (defined('ADMINTOOLS_PRO') && ADMINTOOLS_PRO)
		{
			$view->setModel($this->getModel('Blockedrequestslogs', 'Administrator', ['ignore_request' => true]));
		}

		$view->setModel($this->getModel('Updates', 'Administrator'));

		// Special toggle to display all panels
		$view->debugAllPanels = $this->input->getBool('_tp', false);
	}

	public function login()
	{
		/** @var MainpasswordModel $model */
		$model    = $this->getModel('Mainpassword', 'Administrator');
		$password = $this->input->get('userpw', '', 'raw');
		$model->setUserPassword($password);

		$url = Route::_('index.php?option=com_admintools', false);
		$this->setRedirect($url);
	}

	public function selfblocked()
	{
		$externalIP = $this->input->getString('ip', '');

		/** @var ControlpanelModel $model */
		$model  = $this->getModel();
		$result = $model->isMyIPBlocked($externalIP);

		echo json_encode([
			'blocked' => $result
		]);

		$this->app->close();
	}

	public function unblockme()
	{
		$unblockIP[] = [$this->input->getString('ip', '')];

		/** @var ControlpanelModel $model */
		$model       = $this->getModel();
		$unblockIP[] = $model->getVisitorIP();

		/** @var UnblockipModel $unblockModel */
		$unblockModel = $this->getModel('Unblockip', 'Administrator');
		$unblockModel->unblockIP($unblockIP);

		$redirectUrl = Route::_('index.php?option=com_admintools', false);
		$this->setRedirect($redirectUrl, Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_IP_UNBLOCKED'), 'success');
	}

	public function endRescue()
	{
		$session = $this->app->getSession();

		$session->set('com_admintools.rescue_timestamp', null);
		$session->set('com_admintools.rescue_username', null);

		$returnUrl = Route::_('index.php?option=com_admintools', false);
		$this->setRedirect($returnUrl);
	}

	public function changelog()
	{
		$view = $this->getView();
		$view->setLayout('changelog');

		$this->display(true);
	}

	public function renameMainPhp()
	{
		$this->checkToken('get');

		/** @var ControlpanelModel $model */
		$model = $this->getModel();
		$model->reenableMainPhp();

		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$returnUrl = $customURL ?: Route::_('index.php?option=com_admintools&view=Controlpanel', false);

		$this->setRedirect($returnUrl);
	}

	/**
	 * Put a flag inside component configuration so user won't be warned again if he manually edits any server
	 * configuration file. He can enable it again by changing its value inside the component options
	 */
	public function ignoreServerConfigWarn()
	{
		$cParams = ComponentHelper::getParams('com_admintools');
		$cParams->set('serverconfigwarn', 0);

		$this->app->bootComponent('com_admintools')->getComponentParametersService()->save($cParams);

		$returnUrl = Route::_('index.php?option=com_admintools&view=Controlpanel', false);

		$this->setRedirect($returnUrl);
	}

	public function regenerateServerConfig()
	{
		$classModel = '';
		$returnUrl  = Route::_('index.php?option=com_admintools&view=Controlpanel', false);

		if (ServerTechnology::isHtaccessSupported())
		{
			$classModel = 'Htaccessmaker';
		}
		elseif (ServerTechnology::isNginxSupported())
		{
			$classModel = 'Nginxconfmaker';
		}
		elseif (ServerTechnology::isWebConfigSupported())
		{
			$classModel = 'Webconfigmaker';
		}

		if (!$classModel)
		{

			$this->setRedirect($returnUrl, Text::_('COM_ADMINTOOLS_CONTROLPANEL_ERR_SERVERCONFIGWARN_REGENERATE'), 'error');

			return;
		}

		/** @var ServerconfigmakerModel $model */
		$model = $this->getModel($classModel, 'Administrator');

		$model->writeConfigFile();

		$this->setRedirect($returnUrl, Text::_('COM_ADMINTOOLS_CONTROLPANEL_LBL_SERVERCONFIGWARN_REGENERATED'), 'success');
	}

	/**
	 * Reset the Secret Word for remote PHP file change scanning
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function resetSecretWord()
	{
		$this->checkToken('get');

		$newSecret = $this->app->getSession()->get('admintools.cpanel.newSecretWord', null);

		if (empty($newSecret))
		{
			$newSecret = UserHelper::genRandomPassword(32);
			$newSecret = $this->app->getSession()->set('admintools.cpanel.newSecretWord', $newSecret);
		}

		$cParams = ComponentHelper::getParams('com_admintools');
		$cParams->set('frontend_secret_word', $newSecret);
		$this->app->bootComponent('com_admintools')->getComponentParametersService()->save($cParams);

		$msg     = Text::sprintf('COM_ADMINTOOLS_CONTROLPANEL_LBL_FESECRETWORD_RESET', $newSecret);
		$msgType = null;

		$returnUrl = Route::_('index.php?option=com_admintools&view=Controlpanel', false);
		$this->setRedirect($returnUrl, $msg, $msgType);
	}

	public function serverConfigMaker()
	{
		if (ServerTechnology::isWebConfigSupported())
		{
			$view = 'webconfigmaker';
		}
		elseif (ServerTechnology::isNginxSupported())
		{
			$view = 'nginxconfmaker';
		}
		else
		{
			$view = 'htaccessmaker';
		}

		$this->setRedirect(sprintf('index.php?option=com_admintools&view=%s', $view));
	}
}