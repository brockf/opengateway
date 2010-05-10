<?php
/**
* Install Controller 
*
* Installs OpenGateway by 1) generating config details and setting up the DB file and, 2) Creating the first admin account
*
* @version 1.0
* @author Electric Function, Inc.
* @package OpenGateway

*/
class Install extends Controller {

	function Install()
	{
		parent::Controller();
		
		define("_CONTROLPANEL","1");
				
		// no one should access this if OpenGateway is installed, unless they are looking at the completion page
		if ($this->router->fetch_method() != 'complete') {
			if (file_exists(APPPATH . 'config/database.php')) {
				// the database file exists, but is there an admin user?
				$this->db->select('client_id');
				$clients = $this->db->get('clients');
				if ($clients->num_rows() > 0) {
					// yes there's an admin user
					// we're setup!
					show_error('This OpenGateway server has already been installed.  This file, /system/opengateway/controllers/install.php, can be deleted.');
					die();
				}
			}
		}
	}
	
	function index() {
		$this->load->helper('file');
		$this->load->helper('url');
		$this->load->helper('string');
		
		// check for submission
		if ($this->input->post('base_url') != '') {
			// we have a submission
			
			// validate MySQL info
			$valid_mysql = FALSE;
			if ($dbh = @mysql_connect($this->input->post('db_host'),$this->input->post('db_user'),$this->input->post('db_pass')))
		    {
		        if (@mysql_select_db($this->input->post('db_name'), $dbh))
		        {
		        	$valid_mysql = TRUE;
		        }
		    }
		    
		    if ($valid_mysql == FALSE) {
		    	$error_mysql = TRUE;
		    }
		    
			$base_url = $this->input->post('base_url');
			$cron_key = $this->input->post('cron_key');
			$encryption_key = $this->input->post('encryption_key');
			
			if (empty($base_url) or empty($cron_key) or empty($encryption_key)) {
				$error_empty_site = TRUE;
			}
			
			if (!strstr($base_url,'http://')) {
				$error_base_url = TRUE;
			}
			
			// no errors? let's write to config files
			if (!isset($error_empty_site) and !isset($error_base_url) and !isset($error_mysql)) {
				// all good!
				
				// read in current config
				$config_file = read_file(APPPATH . 'config/config.php');
				
				// swap in variables
				$config_file = preg_replace('/\$config\[\'base_url\'\](.*?)\=(.*?)\"(.*?)\"/','$config[\'base_url\'] = "' . $base_url . '"',$config_file);
				$config_file = preg_replace('/\$config\[\'cron_key\'\](.*?)\=(.*?)\'(.*?)\'/','$config[\'cron_key\'] = \'' . $cron_key . '\'',$config_file);
				$config_file = preg_replace('/\$config\[\'encryption_key\'\](.*?)\=(.*?)\"(.*?)\"/','$config[\'encryption_key\'] = "' . $encryption_key . '"',$config_file);
				
				if ($this->input->post('ssl_cert') == '1') {
					$config_file = str_ireplace('$config[\'ssl_active\'] = FALSE;','$config[\'ssl_active\'] = TRUE;',$config_file);
				}
				
				// write config file
				write_file(APPPATH . 'config/config.php',$config_file,'w');
				
				// create database file
				$database_file = read_file(APPPATH . 'config/database.format.php');
				
				$database_file = preg_replace('/\$db\[\'default\'\]\[\'hostname\'\](.*?)\=(.*?)\"(.*?)\"/','$db[\'default\'][\'hostname\'] = "' . $this->input->post('db_host') . '"',$database_file);
				$database_file = preg_replace('/\$db\[\'default\'\]\[\'username\'\](.*?)\=(.*?)\"(.*?)\"/','$db[\'default\'][\'username\'] = "' . $this->input->post('db_user') . '"',$database_file);
				$database_file = preg_replace('/\$db\[\'default\'\]\[\'password\'\](.*?)\=(.*?)\"(.*?)\"/','$db[\'default\'][\'password\'] = "' . $this->input->post('db_pass') . '"',$database_file);
				$database_file = preg_replace('/\$db\[\'default\'\]\[\'database\'\](.*?)\=(.*?)\"(.*?)\"/','$db[\'default\'][\'database\'] = "' . $this->input->post('db_name') . '"',$database_file);
				
				// write database file
				write_file(APPPATH . 'config/database.php',$database_file,'w');
				
				// import initial database structure
				// note - all update files will be run before the next step loads (because auto_updater will be invoked)
				$structure = read_file(APPPATH . 'updates/install.php');
				$structure = str_replace('<?php if (!defined(\'BASEPATH\')) exit(\'No direct script access allowed\'); ?>','',$structure);
				
				// break into newlines
				$structure = explode("\n",$structure);
				
				// run mysql queries
				$query = "";
				$querycount++;
				foreach ($structure as $sql_line)
				{
					if (trim($sql_line) != "" and substr($sql_line,0,2) != "--")
					{
						$query .= $sql_line;
						if (substr(trim($query), -1, 1) == ";")
						{
							// this query is finished, execute it
						    if (@mysql_query($query, $dbh))
						    {
						    	$query = "";
						    	$querycount++;
						    }
						    else {
						    	show_error('There was a critical error importing the initial database structure.  Please contact support.<br /><br />Query:<br /><br />' . $query);
						    	die();
						    }
						}
					}
				}
				
				// send to administrator account setup
				if (strstr(current_url(),'/index')) {
					$forward_url = str_replace('/index','/admin',current_url());
				}
				else {
					$forward_url = rtrim(current_url(),'/') . '/admin'; 
				}
				
				// send to admin step
				header('Location: ' . $forward_url);
				die();
			}
		}
		
		// which folders/files should be writeable?
		$file_permissions = array(
							str_replace('system/','',BASEPATH) . 'writeable',
							APPPATH . 'config',
							APPPATH . 'config/config.php'
							);
					
		$file_permission_errors = array();		
		foreach ($file_permissions as $file) {
			if (!is_writable($file)) {
				$file_permission_errors[] = array(
												'file' => $file,
												'folder' => (is_dir($file)) ? TRUE : FALSE
											);
			}
		}
		
		// get domain name
		$domain = ($this->input->post('base_url')) ? $this->input->post('base_url') : rtrim(str_replace(array('install','index'),'',current_url()), '/') . '/';
		
		// default values
		$db_user = ($this->input->post('db_user')) ? $this->input->post('db_user') : '';
		$db_host = ($this->input->post('db_host')) ? $this->input->post('db_host') : 'localhost';
		$db_pass = ($this->input->post('db_pass')) ? $this->input->post('db_pass') : '';
		$db_name = ($this->input->post('db_name')) ? $this->input->post('db_name') : '';
		
		// generate random keys
		$cron_key = random_string('unique');
		$encryption_key = random_string('unique');
		
		$vars = array(
					'file_permission_errors' => $file_permission_errors,
					'domain' => $domain,
					'cron_key' => $cron_key,
					'encryption_key' => $encryption_key,
					'error_mysql' => (isset($error_mysql) and !empty($error_mysql)) ? TRUE : FALSE,
					'error_empty_site' => (isset($error_empty_site) and !empty($error_empty_site)) ? TRUE : FALSE,
					'error_base_url' => (isset($error_base_url) and !empty($error_base_url)) ? TRUE : FALSE,
					'db_user' => $db_user,
					'db_host' => $db_host,
					'db_name' => $db_name,
					'db_pass' => $db_pass
				);
		
		$this->load->view(branded_view('install/configuration.php'), $vars);
	}
	
	function admin () {
		$vars = array(
				);
		
		$this->load->view(branded_view('install/admin.php'), $vars);
	}
}