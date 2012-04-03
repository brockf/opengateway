<?=$this->load->view(branded_view('cp/header'));?>

<style>
	td.filter select { width: 75px }
</style>

<h1>Latest transactions</h1>
<?=$this->dataset->TableHead();?>
<?
if (!empty($this->dataset->data)) {
	foreach ($this->dataset->data as $row) {
	?>
		<tr>
			<td><a href="<?=site_url('transactions/charge/' . $row['id']);?>"><?=$row['id'];?></a></td>
			<td class="<? if ($row['refunded'] == '1') { ?>refunded<? } else { ?><?=$row['status'];?><? } ?>">&nbsp;</td>
			<td><?=$row['date'];?></td>
			<td><?=$this->config->item('currency_symbol');?><?=$row['amount'];?></td>
			<td><? if (isset($row['customer'])) { ?><?=$row['customer']['last_name'];?>, <?=$row['customer']['first_name'];?><? } ?></td>
			<td><? if (!empty($row['card_last_four'])) { ?>****<?=$row['card_last_four'];?><? } ?></td>
			<td><?=$gateways[$row['gateway_id']];?></td>
			<td class="options"><? if (isset($row['recurring_id'])) { ?><a href="<?=site_url('transactions/recurring/' . $row['recurring_id']);?>"><?=$row['recurring_id'];?></a><? } ?></td>
		</tr>
	<?
	}
}
else {
?>
<tr><td colspan="8">Empty data set.</td></tr>
<?
}	
?>
<?=$this->dataset->TableClose();?>

<div class="total">
	<h2>Total Amount</h2>
	<p><?=$this->config->item('currency_symbol');?><?=money_format("%!^i",$total_amount);?></p>
</div>
<?=$this->load->view(branded_view('cp/footer'));?>