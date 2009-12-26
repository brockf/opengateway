<?=$this->load->view('cp/header');?>
<h1>Manage emails</h1>
<?=$this->dataset->TableHead();?>
<?
if (!empty($this->dataset->data)) {
	foreach ($this->dataset->data as $row) {
	?>
		<tr>
			<td><input type="checkbox" name="check_<?=$row['id'];?>" value="1" class="action_items" /></td>
			<td><?=$row['id'];?></td>
			<td><?=$row['trigger'];?></td>
			<td><?=$row['to_address'];?></td>
			<td><?=$row['email_subject'];?> days</td>
			<td><?=$row['format'];?> days</td>
			<td><? if (isset($options[$row['plan_id']])) { ?><?=$options[$row['plan_id']];?><? } ?></td>
		</tr>
	<?
	}
}
else {
?>
<tr><td colspan="7">Empty data set.</td></tr>
<?
}	
?>
<?=$this->dataset->TableClose();?>
<?=$this->load->view('cp/footer');?>