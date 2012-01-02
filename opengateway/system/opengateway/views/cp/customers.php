<?=$this->load->view(branded_view('cp/header'));?>
<h1>Customer database</h1>
<?=$this->dataset->TableHead();?>
<?
if (!empty($this->dataset->data)) {
	foreach ($this->dataset->data as $row) {
	?>
		<tr rel="<?=$row['id'];?>">
			<td><input type="checkbox" name="check_<?=$row['id'];?>" value="1" class="action_items" /></td>
			<td><?=$row['id'];?></td>
			<td><?=$row['first_name'];?></td>
			<td><?=$row['last_name'];?></td>
			<td><?=$row['email'];?></td>
			<td><?

if (isset($row['plans'])) {
	foreach ($row['plans'] as $plan) {
		if ($plan['status'] != 'inactive') {
			?><?=$plan['name'];?><br /><?
		}
	}
}
			
?></td>
		<td class="options"><a href="<?=site_url('customers/edit/' . $row['id']);?>">edit</a></td>
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
<?=$this->load->view(branded_view('cp/footer'));?>