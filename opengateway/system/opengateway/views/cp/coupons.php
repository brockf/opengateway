<?=$this->load->view(branded_view('cp/header'));?>
<h1>Manage Coupons</h1>

<?=$this->dataset->TableHead();?>
<?php if (!empty($this->dataset->data)) : ?>
	<?php foreach ($this->dataset->data as $row) :?>
		<tr>
			<td><input type="checkbox" name="check_<?=$row['coupon_id'];?>" value="1" class="action_items" /></td>
			<td><?=$row['coupon_id'];?></td>
			<td><a href="<?=site_url('coupons/edit/' . $row['coupon_id']);?>"><?=$row['coupon_name'];?></a></td>
			<td><?= $row['coupon_code'] ?></td>
			<td><?= date('Y-m-d', strtotime($row['coupon_start_date'])) .' - '. date('Y-m-d', strtotime($row['coupon_end_date']));?></td>
			<td><?= $coupon_options[$row['coupon_type_id']] ?></td>
		</tr>
	<?php endforeach; ?>
<?php else : ?>
	<tr>
		<td colspan="6">No coupons match your filters.</td>
	</tr>
<?php endif; ?>

<?=$this->dataset->TableClose();?>

<?=$this->load->view(branded_view('cp/footer'));?>