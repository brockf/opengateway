<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Control Panel | Login</title>
	<link href="<?=base_url();?>css/universal.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="<?=base_url();?>css/login.css" rel="stylesheet" type="text/css" media="screen" />
	<script type="text/javascript" src="<?=base_url();?>js/jquery-1.3.2.js"></script>
	<script type="text/javascript" src="<?=base_url();?>js/universal.js"></script>
</head>
<body>
	<?=get_notices();?>
	<div id="header">
		<?=$this->user->Get('username');?>
	</div>