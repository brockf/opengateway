<?=$this->load->view(branded_view('cp/header'));?>
<h1>Recurring Charges</h1>
<?=$this->dataset->TableHead();?>
<?
if (!empty($this->dataset->data)) {
	foreach ($this->dataset->data as $row) {
	?>
		<tr>
			<td><?=$row['id'];?></td>
			<td class="<? if ($row['status'] == 'active') { ?>ok<? } else { ?>failed<? } ?>">&nbsp;</td>
			<td><?=$row['start_date'];?></td>
			<td><?=$row['last_charge_date'];?></td>
			<td><?=$row['next_charge_date'];?></td>
			<td><?=$this->config->item('currency_symbol');?><?=$row['amount'];?></td>
			<td><? if (isset($row['customer'])) { ?><?=$row['customer']['last_name'];?>, <?=$row['customer']['first_name'];?><? } ?></td>
			<td><? if (isset($row['plan']['name'])) { ?><?=$row['plan']['name'];?><? } ?></td>
			<td class="options"><a href="<?=site_url('transactions/recurring/' . $row['id']);?>">details</a></td>
		</tr>
	<?
	}
}
else {
?>
<tr><td colspan="9">Empty data set.</td></tr>
<?
}	
?>
<?=$this->dataset->TableClose();?>
<?=$this->load->view(branded_view('cp/footer'));?>