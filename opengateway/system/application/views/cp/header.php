<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Control Panel</title>
	<link href="<?=base_url();?>css/universal.css" rel="stylesheet" type="text/css" media="screen" />
	<script type="text/javascript" src="<?=base_url();?>js/jquery-1.3.2.js"></script>
	<script type="text/javascript" src="<?=base_url();?>js/universal.js"></script>
</head>
<body>
	<?=get_notices();?>
	<div id="header">
		<div id="logo">&nbsp;</div>
		<ul id="topnav">
			<li><a href="<?=site_url('dashboard');?>">Dashboard</a></li>
			<li class="active"><a href="<?=site_url('transactions');?>">Transactions</a></li>
			<li><a href="<?=site_url('Customers');?>">Customers</a></li>
			<li><a href="<?=site_url('plans');?>">Recurring Plans</a></li>
			<li><a href="<?=site_url('clients');?>">Clients</a></li>
			<li class="parent">
				<a href="<?=site_url('settings');?>">Settings</a>
				<ul class="children">
					<li><a href="<?=site_url('settings/emails');?>">Emails</a></li>
					<li><a href="<?=site_url('settings/gateways');?>">Gateways</a></li>
					<li><a href="<?=site_url('settings/api');?>">API Keys</a></li>
				</ul>
				<div style="clear:both"></div>
			</li>
		</ul>
		<div id="account">
			<div id="loggedin"><?=$this->user->Get('first_name')?> <?=$this->user->Get('last_name')?></div>
			<ul id="account_menu">
				<li><a id="logout" href="<?=site_url('account/logout');?>">Logout</a></li>
				<li><a id="account_link" href="<?=site_url('account/');?>">Update Account</a></li>
				<li><a id="support" href="<?=$this->config->item('support_url');?>">Get Support</a></li>
			</ul>
		</div>
		<div style="clear: both"></div>
	</div>
	<div id="wrapper">
		<div id="sidebar">
			testst
		</div>
		<div id="content">
			<div id="box-top-right"></div>
			<div id="box-bottom-left"></div>
			<div id="box-bottom-right"></div>
			<div id="box-content">