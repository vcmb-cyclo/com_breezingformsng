#########<br />
Constat Eddy<br />
#########<br />
<br />
Installation de BreezingForms 5.0.0-RC1 -> OK<br />
Mise à jour automatique vers RC2 -> OK<br />
Désactivation du plusgin de compatibilité -> Provoque une erreur "0 Class "JPlugin" not found"<br />
<br />
Rapport d'erreur:<br />
<br />
# 	Function 	Location<br />
1 	() 	JROOT/plugins/content/breezingforms/breezingforms.php:31<br />
2 	require_once() 	JROOT/libraries/src/Extension/ExtensionManagerTrait.php:217<br />
3 	Joomla\CMS\Application\CMSApplication->loadPluginFromFilesystem() 	JROOT/libraries/src/Extension/ExtensionManagerTrait.php:160<br />
4 	Joomla\CMS\Application\CMSApplication->loadExtension() 	JROOT/libraries/src/Extension/ExtensionManagerTrait.php:99<br />
5 	Joomla\CMS\Application\CMSApplication->bootPlugin() 	JROOT/libraries/src/Plugin/PluginHelper.php:232<br />
6 	Joomla\CMS\Plugin\PluginHelper::import() 	JROOT/libraries/src/Plugin/PluginHelper.php:192<br />
7 	Joomla\CMS\Plugin\PluginHelper::importPlugin() 	JROOT/libraries/src/MVC/Model/FormBehaviorTrait.php:193<br />
8 	Joomla\CMS\MVC\Model\ListModel->preprocessForm() 	JROOT/libraries/src/MVC/Model/FormBehaviorTrait.php:115<br />
9 	Joomla\CMS\MVC\Model\ListModel->loadForm() 	JROOT/libraries/src/MVC/Model/ListModel.php:431<br />
10 	Joomla\CMS\MVC\Model\ListModel->getFilterForm() 	JROOT/libraries/src/MVC/View/AbstractView.php:159<br />
11 	Joomla\CMS\MVC\View\AbstractView->get() 	JROOT/administrator/components/com_installer/src/View/Manage/HtmlView.php:66<br />
12 	Joomla\Component\Installer\Administrator\View\Manage\HtmlView->display() 	JROOT/administrator/components/com_installer/src/Controller/DisplayController.php:75<br />
13 	Joomla\Component\Installer\Administrator\Controller\DisplayController->display() 	JROOT/libraries/src/MVC/Controller/BaseController.php:730<br />
14 	Joomla\CMS\MVC\Controller\BaseController->execute() 	JROOT/libraries/src/Dispatcher/ComponentDispatcher.php:143<br />
15 	Joomla\CMS\Dispatcher\ComponentDispatcher->dispatch() 	JROOT/libraries/src/Component/ComponentHelper.php:361<br />
16 	Joomla\CMS\Component\ComponentHelper::renderComponent() 	JROOT/libraries/src/Application/AdministratorApplication.php:150<br />
17 	Joomla\CMS\Application\AdministratorApplication->dispatch() 	JROOT/libraries/src/Application/AdministratorApplication.php:195<br />
18 	Joomla\CMS\Application\AdministratorApplication->doExecute() 	JROOT/libraries/src/Application/CMSApplication.php:306<br />
19 	Joomla\CMS\Application\CMSApplication->execute() 	JROOT/administrator/includes/app.php:58<br />
20 	require_once() 	JROOT/administrator/index.php:32  
