<?=$this->load->view(branded_view('cp/header'));?>
<table class="dataset" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<td colspan="2">Details for Recurring Charge #<?=$id;?></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td style="width: 25%" class="label">Amount</td>
			<td style="width: 75%"><?=$this->config->item('currency_symbol');?><?=$amount;?></td>
		</tr>
		<tr>
			<td class="label">Charge Interval</td>
			<td><?=$interval;?> days</td>
		</tr>
		<tr>
			<td class="label">Status</td>
			<td><? if  ($status ==  'active') { ?><img src="<?=branded_include('images/ok.png');?>" alt="active" /><? } ?> 
				<? if ($status == 'inactive') { ?><img src="<?=branded_include('images/failed.png');?>" alt="inactive" /><? } ?>
				<?=$status;?> <? if ($status == "active") { ?>(<a href="<?=site_url('transactions/cancel_recurring/' . $id);?>">cancel recurring</a>)<? } ?></td>
		</tr>
		<tr>
			<td class="label">Start Date</td>
			<td><?=$start_date;?></td>
		</tr>
		<tr>
			<td class="label">End Date</td>
			<td><?=$end_date;?></td>
		</tr>
		<tr>
			<td class="label">Last Charge Date</td>
			<td><?=$last_charge_date;?> (<a href="<?=dataset_link('transactions/index',array('recurring_id' => $id));?>">view all charges</a>)</td>
		</tr>
		<? if ($status == 'active') { ?>
		<tr>
			<td class="label">Next Charge Date</td>
			<td><?=$next_charge_date;?></td>
		</tr>
		<? } else { ?>
		<tr>
			<td class="label">Cancel Date</td>
			<td><?=$cancel_date;?></td>
		</tr>
		<? } ?>
		<tr>
			<td class="label">Processing Gateway</td>
			<td><a href="<?=site_url('settings/edit_gateway/' . $gateway['gateway_id']);?>"><?=$gateway['alias'];?></a></td>
		</tr>
	</tbody>
	<? if (isset($customer)) { ?>
	<thead>
		<tr>
			<td colspan="2">Customer Information (<a href="<?=site_url('customers/edit/' . $customer['id']);?>">edit</a>)</td>
		</tr>
	</thead>
	<tbody>
		<? if (!empty($customer['first_name'])) { ?>
		<tr>
			<td class="label">Name</td>
			<td><?=$customer['first_name'];?> <?=$customer['last_name'];?></td>
		</tr>
		<? } ?>
		<? if (!empty($customer['email'])) { ?>
		<tr>
			<td class="label">Email</td>
			<td><?=$customer['email'];?></td>
		</tr>
		<? } ?>
		<? if (!empty($customer['company'])) { ?>
		<tr>
			<td class="label">Company</td>
			<td><?=$customer['company'];?></td>
		</tr>
		<? } ?>
		<? if (!empty($customer['address_1'])) { ?>
		<tr>
			<td class="label">Address</td>
			<td>    <?=$customer['address_1'];?>
					<? if (!empty($customer['address_2'])) { ?><br /><?=$customer['address_2'];?><? } ?>
					<? if (!empty($customer['city'])) { ?><br /><?=$customer['city'];?><? } ?><? if (!empty($customer['state'])) { ?>, <?=$customer['state'];?><? } ?>
					<? if (!empty($customer['country'])) { ?><br /><?=$customer['country'];?><? } ?>
					<? if (!empty($customer['postal_code'])) { ?><br /><?=$customer['postal_code'];?><? } ?>
					</p>
			</td>
		</tr>
		<? } ?>
		<? if (!empty($customer['phone'])) { ?>
		<tr>
			<td class="label">Phone</td>
			<td><?=$customer['phone'];?></td>
		</tr>
		<? } ?>
		<? if (!empty($customer['internal_id'])) { ?>
		<tr>
			<td class="label">Internal ID</td>
			<td><?=$customer['internal_id'];?></td>
		</tr>
		<? } ?>
	</tbody>	
	<? } ?>
</table>

<?=$this->load->view(branded_view('cp/footer'));?>