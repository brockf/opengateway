<?php

/**
* Install Redirect
*
* If the system is not installed, the user is redirect to the install controller.
*
* @return boolean TRUE if installed, FALSE if not installed
*
*/

function install_redirect () {
	$not_installed = FALSE;
	
	if (!file_exists(APPPATH . 'config/database.php')) {
		$not_installed = TRUE;
	}
	
	if ($not_installed == TRUE) {
		if ($this->router->fetch_class() != 'install') {
			show_error('Your OpenGateway is not installed.  Visit yourdomain.com/install to install the server.');
			die();
		}
	}
}