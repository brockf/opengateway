<?=$this->load->view(branded_view('cp/header'));?>
<h1>Charge Details</h1>
<? if (isset($recurring_id)) { ?>
<div id="charge_recurring">
This charge is related to recurring charge #<a href="<?=site_url('transactions/recurring/' . $recurring_id);?>"><?=$recurring_id;?></a>.
</div>
<? } ?>
<div id="charge_info">
	<? if (isset($details) and is_array($details)) { ?>
	<div id="charge_details">	
		<? foreach ($details as $name => $value) { ?>
		<? if (!empty($value)) { ?>
		<p><b><?=$name;?></b><br />
		<?=$value;?></p>
		<? } ?>
		<? } ?>
	</div>
	<? } ?>
<p><b>Charge ID</b><br />
<?=$id;?></p>
<p><b>Timestamp</b><br />
<?=$date;?></p>
<p><b>Amount</b><br />
<?=$amount;?></p>
<p><b>Credit Card</b><br />
**** <?=$card_last_four;?></p>
<p><b>Status</b><br />
<?=$status;?> <img src="<?=site_url('images/' . $status . '.png');?>" alt="<?=$status;?>" /></p>
<p><b>Gateway</b><br />
<a href="<?=site_url('settings/edit_gateway/' . $gateway['gateway_id']);?>"><?=$gateway['name'];?></a></p>
</div>
<? if (isset($customer)) { ?>
<div id="charge_customer">
<? if (!empty($customer['first_name'])) { ?>
	<p><b>Name</b><br />
	<?=$customer['first_name'];?> <?=$customer['last_name'];?></p>
<? } ?>
<? if (!empty($customer['address_1'])) { ?>
	<p><b>Address</b><br />
	<?=$customer['address_1'];?>
	<? if (!empty($customer['address_2'])) { ?>
	<br /><?=$customer['address_2'];?><? } ?>
	<br /><?=$customer['city'];?>, <?=$customer['state'];?>
	<br /><?=$customer['country'];?>
	<br /><?=$customer['postal_code'];?></p>
	<? if (!empty($customer['phone'])) { ?>
	<p><b>Phone</b><br />
	<?=$customer['phone'];?></p>
	<? } ?>
	<? if (!empty($customer['email'])) { ?>
	<p><b>Email</b><br />
	<a href="mailto:<?=$customer['email'];?>"><?=$customer['email'];?></a></p>
	<? } ?>
<? } ?>
<p><a href="<?=site_url('customers/edit/' . $customer['id']);?>">Edit Customer Record</a></p>
</div>

<? } ?>
<?=$this->load->view(branded_view('cp/footer'));?>