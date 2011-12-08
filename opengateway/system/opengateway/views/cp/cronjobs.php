<?=$this->load->view(branded_view('cp/header'));?>

<h1>System Cronjobs</h1>

<?php if (!isset($dates)) : ?>
<p class="warning"><span>Your cronjobs appear to have never been run! This is not good - we need these scripts to run at least once per day.  You must configure the cronjobs below to run at least once per day, or ask your system administrator to do the same.</span></p>
<?php endif; ?>

<p><b>Notifications Cronjob Last Run:</b> <?php echo $dates->cron_last_run_notifications ? date('D M j, Y g:i A', strtotime($dates->cron_last_run_notifications)) : 'Never' ?></p>
<p><b>Subscriptions Cronjob Last Run:</b> <?php echo $dates->cron_last_run_subs ? date('D M j, Y g:i A', strtotime($dates->cron_last_run_subs)) : 'Never' ?></p>

<p><b>Cronjob Commands for *nix Servers:</b></p>

<p>The following cronjobs should be setup in your web hosting control panel. If you are unsure how of how to do this, please contact your web host
or your server administrator.</p>

<pre class="code">
*/5 * * * * wget -q -O /dev/null <?=rtrim($cp_link, '/');?>/cron/sendnotifications/<?=$cron_key;?> >/dev/null 2>&1
5 5 * * * wget -q -O /dev/null <?=rtrim($cp_link, '/');?>/cron/subscriptionmaintenance/<?=$cron_key;?> >/dev/null 2>&1
</pre>

<?=$this->load->view(branded_view('cp/footer'));?>