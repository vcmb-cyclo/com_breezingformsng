<?php
/**
 * @package     BreezingForms
 * @author      Markus Bopp
 * @link        http://www.crosstec.de
 * @copyright   Copyright (C) 2024 by XDA+GIL
 * @license     GNU/GPL
 */

defined('_JEXEC') or die;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input;
use Joomla\CMS\MVC\Controller\BaseController;

class BreezingformsController extends BaseController
{
	/**
	 * The Application. Redeclared to show this class requires a web application.
	 *
	 * @var    CMSWebApplicationInterface
	 * @since  5.0.0
	 */
	protected $app;

	/**
	 * The context for storing internal data, e.g. record.
	 *
	 * @var    string
	 * @since  5.0.0
	 */
	protected $context;

	/**
	 * The URL option for the component.
	 *
	 * @var    string
	 * @since  5.0.0
	 */
	protected $option;

	/**
	 * @var		string	The default view.
	 * @since	5.0.0
	 */
	protected $default_view = '';

	/**
	 * Constructor.
	 *
	 * @param   array                        $config       An optional associative array of configuration settings.
	 *                                                     Recognized key values include 'name', 'default_task',
	 *                                                     'model_path', and 'view_path' (this list is not meant to be
	 *                                                     comprehensive).
	 * @param   ?MVCFactoryInterface         $factory      The factory.
	 * @param   ?CMSWebApplicationInterface  $app          The Application for the dispatcher
	 * @param   ?Input                       $input        Input
	 *
	 * @since   5.0.0
	 */
	public function __construct(
		$config = [],
		MVCFactoryInterface $factory = null,
		?CMSWebApplicationInterface $app = null,
		?Input $input = null
	) {
		parent::__construct($config, $factory, $app, $input);
	}



	/**
	 * Method to display a view.
	 *
	 * @param	boolean			If true, the view output will be cached
	 * @param	array			An array of safe url parameters and their variable types, for valid values see {@link InputFilter::clean()}.
	 *
	 * @return	JController		This object to support chaining.
	 * @since	1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		parent::display();
		return $this;
	}
}
