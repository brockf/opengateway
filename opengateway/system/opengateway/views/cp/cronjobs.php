<?=$this->load->view(branded_view('cp/header'));?>

<h1>System Cronjobs</h1>

<?php if (!isset($dates)) : ?>
<p class="warning"><span>Your cronjob appears to have never been run! This is not good - we need this script to run at least once per day to take care of all the automated tasks involving subscriptions, like auto-charging subscriptions. You must configure the cronjobs below to run at least once per day, or ask your system administrator to do the same.</span></p>
<?php endif; ?>

<p><b>Notifications Cronjob Last Run:</b> <?php echo $dates->cron_last_run_notifications ? date('D M j, Y g:i A', strtotime($dates->cron_last_run_notifications)) : 'Never' ?></p>
<p><b>Subscriptions Cronjob Last Run:</b> <?php echo $dates->cron_last_run_subs ? date('D M j, Y g:i A', strtotime($dates->cron_last_run_subs)) : 'Never' ?></p>
<br/>
<p><b>Cronjob Commands for *nix Servers:</b></p>

<p>
	*/5 * 	* 	* 	* wget -q -O /dev/null <?=rtrim($cp_link, '/');?>/cron/sendnotifications/<?=$cron_key;?> >/dev/null 2>&1
	<br/>5	5 	* 	* 	* wget -q -O /dev/null <?=rtrim($cp_link, '/');?>/cron/subscriptionmaintenance/<?=$cron_key;?> >/dev/null 2>&1
</p>

<p>These commands execute the following scripts every 5 minutes. However, the cronjob actions themselves won't unnecessarily repeat themselves. For example, subscription maintenance will only occur once per day.</p>


<?=$this->load->view(branded_view('cp/footer'));?>