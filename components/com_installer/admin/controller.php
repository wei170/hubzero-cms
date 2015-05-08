<?php
/**
 * @package		Joomla.Administrator
 * @subpackage	com_installer
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License, see LICENSE.php
 */

defined('_JEXEC') or die;

/**
 * Installer Controller
 *
 * @package		Joomla.Administrator
 * @subpackage	com_installer
 * @since		1.5
 */
class InstallerController extends JControllerLegacy
{
	/**
	 * Method to display a view.
	 *
	 * @param	boolean			If true, the view output will be cached
	 * @param	array			An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return	JController		This object to support chaining.
	 * @since	1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT . '/helpers/installer.php';

		// Get the document object.
		$document = App::get('document');

		// Set the default view name and format from the Request.
		$vName   = Request::getCmd('view', 'install');
		$vFormat = $document->getType();
		$lName   = Request::getCmd('layout', 'default');

		// Get and render the view.
		if ($view = $this->getView($vName, $vFormat))
		{
			$ftp = JClientHelper::setCredentialsFromRequest('ftp');
			$view->assignRef('ftp', $ftp);

			// Get the model for the view.
			$model = $this->getModel($vName);

			// Push the model into the view (as default).
			$view->setModel($model, true);
			$view->setLayout($lName);

			// Push document object into the view.
			$view->assignRef('document', $document);
			// Load the submenu.
			InstallerHelper::addSubmenu($vName);
			$view->display();
		}

		return $this;
	}
}
