<?=$this->load->view(branded_view('install/header'));?>
<h1>Administrator Account</h1>
<p>Your MySQL database and configuration files have been setup.</p>
<p>We will now create the primary administrator account for your OpenGateway server.</p>
<form class="form" method="post" action="">
	<fieldset>
		<legend>Account Information</legend>
		<ol>
			<li>
				<label for="base_url">Base Server URL</label>
				<input type="text" name="base_url" id="base_url" class="text required" value="<?=$domain;?>" />
			</li>
			<li>
				<label for="cron_key">Cron Key (auto-generated)</label>
				<input type="text" name="cron_key" id="cron_key" class="text required" value="<?=$cron_key;?>" />
			</li>
			<li>
				<label for="encryption_key">Encryption Key (auto-generated)</label>
				<input type="text" name="encryption_key" id="encryption_key" class="text required" value="<?=$encryption_key;?>" />
			</li>
			<li>
				<label for="ssl_cert">SSL Certificate</label>
				<input type="checkbox" name="ssl_cert" id="ssl_cert" value="1" checked="checked" />&nbsp;I have an SSL certificate installed for my (sub)domain.  Force all API requests
				with credit card info to be made via HTTPS secure connections.  (Highly recommended).
			</li>
		</ol>
	</fieldset>
	<div class="submit"><input type="submit" name="continue" id="continue" value="Create Account" /></div>
</form>
<? } ?>
<?=$this->load->view(branded_view('install/footer'));?>