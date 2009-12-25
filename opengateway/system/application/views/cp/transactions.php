<?=$this->load->view('cp/header');?>
<h1>Latest transactions</h1>
<?=$this->dataset->TableHead();?>
<?
if (!empty($this->dataset->data)) {
	foreach ($this->dataset->data as $row) {
	?>
		<tr>
			<td><?=$row['id'];?></td>
			<td class="<?=$row['status'];?>">&nbsp;</td>
			<td><?=$row['date'];?></td>
			<td><?=$row['amount'];?></td>
			<td><? if (isset($row['customer'])) { ?><?=$row['customer']['last_name'];?>, <?=$row['customer']['first_name'];?><? } ?></td>
			<td>****<?=$row['card_last_four'];?></td>
		</tr>
	<?
	}
}
else {
?>
<tr><td colspan="5">Empty data set.</td></tr>
<?
}	
?>
<?=$this->dataset->TableClose();?>
<?=$this->load->view('cp/footer');?>