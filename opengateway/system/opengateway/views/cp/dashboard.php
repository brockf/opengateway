<?=$this->load->view(branded_view('cp/header'));?>
<h1>Dashboard</h1>
<div class="activity_log">
	<h2 class="colour">Recent Payment Activity</h2>
<? if (!empty($log)) { ?>
	<? foreach ($log as $line) { ?>
		<p><?=$line;?>.</p>
	<? } ?>
<? } else { ?>
 <p class="soft">No recent payment activity.</p>
<? } ?>
</div>
<div class="buttons">
	<h2 class="colour">Quick Navigation</h2>
	<ul class="dashboard">
		<li>
			<a class="dash_charge" href="<?=site_url('transactions/create');?>">New Charge</a>
		</li>
		<li>
			<a class="dash_records" href="<?=site_url('transactions');?>">Browse Records</a>
		</li>
		<li>
			<a class="dash_emails" href="<?=site_url('settings/emails');?>">Manage Emails</a>
		</li>
		<li>
			<a class="dash_settings" href="<?=site_url('settings/gateways');?>">Payment Gateways</a>
		</li>
	</ul>
</div>
<div style="clear:both"></div>
<? if (isset($no_chart)) { ?>
<p class="soft">You do not have enough revenue for a chart, yet.  When you do, we'll display a nice revenue graph here.</p>
<? } else { ?>
<img src="<?=site_url('writeable/rev_chart_' . $this->user->Get('client_id') . '.png');?>" alt="Revenue Chart" style="width:100%; height: 260px" />
<? } ?>
<?=$this->load->view(branded_view('cp/footer'));?>