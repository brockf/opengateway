<?php

/**
* Is Installed
*
* This function determines whether OpenGateway has successfully been installed.
* It's useful when determining what autoload items to load and whether to show
* the install wizard.
*
* @return boolean TRUE if installed, FALSE if not installed
*
*/

function is_installed () {
	if (is_defined("_INSTALL_STATUS")) {
		return _INSTALL_STATUS;
	}
	
	$not_installed = TRUE;
	
	if (!file_exists(APPPATH . 'config/database.php')) {
		$not_installed = FALSE;
	}
	
	if (!file_exists(APPPATH . 'config/config.php')) {
		$not_installed = FALSE;
	}
	
	define("_INSTALL_STATUS",$not_installed);
	
	return _INSTALL_STATUS;
}