<?=$this->load->view('cp/header');?>
<h1>Dashboard</h1>
<ul class="dashboard">
	<li>
		<a class="dash_charge" href="<?=site_url('transactions/create');?>">New Charge</a>
	</li>
	<li>
		<a class="dash_records" href="<?=site_url('transactions');?>">Records</a>
	</li>
	<li>
		<a class="dash_emails" href="<?=site_url('settings/emails');?>">Emails</a>
	</li>
	<li>
		<a class="dash_settings" href="<?=site_url('settings/gateways');?>">Gateways</a>
	</li>
</ul>
<div style="clear:both"></div>
<? if (isset($no_revenue)) { ?>
<p>You do not have any revenue yet.  When you do, we'll display a nice revenue graph here.</p>
<? } else { ?>
<img src="<?=site_url('writeable/rev_chart_' . $this->user->Get('client_id') . '.png');?>" alt="Revenue Chart" style="width:100%; height: 260px" />
<? } ?>
<?=$this->load->view('cp/footer');?>