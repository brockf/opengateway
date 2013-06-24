<?=$this->load->view(branded_view('install/header'), array('complete' => TRUE));?>
<h1>Install Complete!</h1>
<p class="error"><b>Do not refresh this page!  Now that OpenGateway is installed, the installer will be completely disabled.</b></p>
<p><strong>Congratulations!  You have successfully uploaded and configured your OpenGateway billing engine.</strong></p>
<p>Important instructions, credentials and links will follow.</p>
<h2>Setup your cronjobs</h2>
<p>Automated processes like emails and recurring charges require the daily execution of two cronjobs.  Please use
a crontab manager (either in your cPanel/Plesk control panel or via SSH) to setup the following crontabs, exactly as so:</p>
<ul>
	<li>*/5 * 	* 	* 	* wget -q -O /dev/null <?=rtrim($cp_link, '/');?>/cron/sendnotifications/<?=$cron_key;?> >/dev/null 2>&1</li>
	<li>5	5 	* 	* 	* wget -q -O /dev/null <?=rtrim($cp_link, '/');?>/cron/subscriptionmaintenance/<?=$cron_key;?> >/dev/null 2>&1</li>
</ul>
<h2>Control Panel &amp; API Links</h2>
<ul>
	<li>Your control panel is accessible at: <a href="<?=$cp_link;?>"><strong><?=$cp_link;?></strong></a></li>
	<li>You should send API POST requests to: <a href="<?=$api_link;?>"><strong><?=$api_link;?></strong></a> (use "https://" when necessary)</li>
</ul>
<h2>Your Account Credentials</h2>
<p>You are a <b>Primary Administrator</b> so you have complete control over the server.  This information should be kept private and changed
frequently to keep your billing engine secure.</p>
<ul>
	<li>Username: <strong><?=$client['username'];?></strong></li>
	<li>Password: <strong><?=$password;?></strong></li>
	<li>API Identifier: <strong><?=$client['api_id'];?></strong></li>
	<li>API Secret Key: <strong><?=$client['secret_key'];?></strong></li>
</ul>
<p><a href="<?=$cp_link;?>">Login to your Control Panel now to setup gateways, client accounts, emails, and more</a>.</p>
<?=$this->load->view(branded_view('install/footer'));?>