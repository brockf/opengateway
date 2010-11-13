<?=$this->load->view(branded_view('cp/header'));?>
<h1>Recurring Plans</h1>
<?=$this->dataset->TableHead();?>
<?php
if (!empty($this->dataset->data)) {
	foreach ($this->dataset->data as $row) {
	?>
		<tr>
			<td><input type="checkbox" name="check_<?=$row['id'];?>" value="1" class="action_items" /></td>
			<td><?=$row['id'];?></td>
			<td><?=$row['name'];?></td>
			<td><? if ($row['amount'] == '0.00') { ?>free<? } else { ?><?=$row['amount'];?><? } ?></td>
			<td><?=$row['interval'];?> days</td>
			<td><? if ($row['free_trial'] == '0') { ?>none<? } else { ?><?=$row['free_trial'];?> days<? } ?></td>
			<td><a href="<?=dataset_link('customers/index',array('plan_id' => $row['id']));?>"><?=$row['num_customers'];?> customers</a></td>
			<td class="options"><a href="<?=site_url('plans/edit/' . $row['id']);?>">edit</a></td>
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
<?=$this->load->view(branded_view('cp/footer'));?>