<?=$this->load->view(branded_view('cp/header'));?>
<h1>Charge Details</h1>
<? if (isset($recurring_id)) { ?>
<a class="charge_recurring" href="<?=site_url('transactions/recurring/' . $recurring_id);?>">This charge is related to recurring charge #<?=$recurring_id;?>.</a>
<? } ?>

<table class="dataset" cellpadding="0" cellspacing="0">
	<thead>
		<tr>
			<td colspan="2">Details for Charge #<?=$id;?></td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td style="width: 25%" class="label">Amount</td>
			<td style="width: 75%"><?=$this->config->item('currency_symbol');?><?=$amount;?><? if ($refunded == "0" and $status == "ok") { ?> (<a href="<?=site_url('transactions/refund/'  . $id);?>">issue refund</a>)<? } ?></td>
		</tr>
		<tr>
			<td class="label">Coupon</td>
			<td><? if (!empty($coupon)) { ?><?=$coupon;?><? } ?></td>
		</tr>
		<tr>
			<td class="label">Transaction Date</td>
			<td><?=$date;?></td>
		</tr>
		<tr>
			<td class="label">Status</td>
			<?
				if ($refunded == "1" or $status == "failed") {
					$status_image = "failed";
				}
				else {
					$status_image = "ok";
				}
				
				if ($refunded == "1") {
					$status = "refunded on " . $refund_date;
				}
				elseif ($type == 'recurring_repeat' and $status == 'failed') {
					$status = 'recurring repeat failed';
				}
				
			?>
			<td><img src="<?=branded_include('images/' . $status_image . '.png');?>" alt="" />&nbsp;<?=$status;?></td>
		</tr>
		<tr>
			<td class="label">Credit Card</td>
			<td><? if (!empty($card_last_four)) { ?>**** <?=$card_last_four;?><? } ?></td>
		</tr>
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
	<? if (isset($details) and !empty($details)) { ?>
	<thead>
		<tr>
			<td colspan="2">Authorization Codes</td>
		</tr>
	</thead>
	<tbody>
	<? foreach ($details as $name => $value) { ?>
		<? if (!empty($value) and $name != "order_authorization_id") { ?>
		<tr>
			<td><?=$name;?></td>
			<td><?=$value;?></td>
		</tr>
		<? } ?>
	<? } ?>
	</tbody>
	<? } ?>
</table>
<?=$this->load->view(branded_view('cp/footer'));?>