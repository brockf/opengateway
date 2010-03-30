<?=$this->load->view(branded_view('cp/header'));?>
<h1>API Access</h1>
<p class="api_definition">Your API Identifier and Secret Key are your access credentials when accessing the billing engine
via the Application Programming Interface (API).  If you are a developer, you will need to use these in your
API requests.</p>
<p class="warning"><span>Keep your API ID and Secret Key secret.  Store it safely and do not share it with anyone who does not need to know it.
If you believe it has been compromised, generate new access information below.</span></p>
<div class="api_keys">
	<h3>Current Access Information</h3>
	<span class="key_name">API Identifier</span><span class="key_value"><?=$api_id;?></span>
	<span class="key_name">Secret Key</span><span class="key_value"><?=$secret_key;?></span>
	<a id="generate_new_api" href="<?=site_url('settings/regenerate_api');?>">Generate New Access Information</a>
</div>
<?=$this->load->view(branded_view('cp/footer'));?>