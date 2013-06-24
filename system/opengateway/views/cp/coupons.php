<?=$this->load->view(branded_view('cp/header'));?>
<h1>Manage Coupons</h1>

<?=$this->dataset->TableHead();?>
<?php if (!empty($this->dataset->data)) : ?>
	<?php foreach ($this->dataset->data as $row) :?>
		<tr>
			<td><input type="checkbox" name="check_<?=$row['id'];?>" value="1" class="action_items" /></td>
			<td><?=$row['id'];?></td>
			<td><a href="<?=site_url('coupons/edit/' . $row['id']);?>"><?=$row['name'];?></a></td>
			<td><?= $row['code'] ?></td>
			<? $end_date = ($row['end_date'] == FALSE) ? 'no expiry' : date('Y-m-d', strtotime($row['end_date'])); ?>
			<td><?= date('Y-m-d', strtotime($row['start_date'])) .' - ' . $end_date;?></td>
			<td><?= $coupon_options[$row['type_id']] ?></td>
		</tr>
	<?php endforeach; ?>
<?php else : ?>
	<tr>
		<td colspan="6">No coupons match your filters.</td>
	</tr>
<?php endif; ?>

<?=$this->dataset->TableClose();?>

<?=$this->load->view(branded_view('cp/footer'));?>