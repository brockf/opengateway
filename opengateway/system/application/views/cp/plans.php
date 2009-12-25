<?=$this->load->view('cp/header');?>
<h1>Recurring plans</h1>
<?=$this->dataset->TableHead();?>
<?
if (!empty($this->dataset->data)) {
	foreach ($this->dataset->data as $row) {
	?>
		<tr>
			<td><?=$row['id'];?></td>
			<td><?=$row['name'];?></td>
			<td><?=$row['amount'];?></td>
			<td><?=$row['interval'];?> days</td>
			<td><?=$row['free_trial'];?> days</td>
			<td><a href="<?=dataset_link('customers/index',array('plan_id' => $row['id']));?>"><?=$row['num_customers'];?> customers</a></td>
		</tr>
	<?
	}
}
else {
?>
<tr><td colspan="6">Empty data set.</td></tr>
<?
}	
?>
<?=$this->dataset->TableClose();?>
<?=$this->load->view('cp/footer');?>