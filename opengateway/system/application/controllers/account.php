<?php
/**
* Account Controller 
*
* Update account details, logout
*
* @version 1.0
* @author Brock Ferguson
* @package OpenGateway

*/
class Account extends Controller {

	function Account()
	{
		parent::Controller();
		
		// perform control-panel specific loads
		CPLoader();
	}
	
	/**
	* Logout
	*
	* Logout the user, return to login page
	*
	* @return bool True, with redirect
	*/
	function logout() {
		$this->user->Logout();
		
		redirect('dashboard/login');
		return true;
	}
}