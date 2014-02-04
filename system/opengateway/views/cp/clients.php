<?=$this->load->view(branded_view('cp/header'));?>
<h1>Clients</h1>
<? if ($this->user->Get('client_type_id') == '1') { ?>
<p class="warning"><span>As a <b><?=$this->user->Get('client_type');?></b>, you have the ability to Create, Update, Suspend, Unsuspend, and Delete client accounts.  Please do
so with care.  You are the parent of all the client accounts you create.  You only have permission to modify
the client accounts that you have created.</span></p>
<? } elseif ($this->user->Get('client_type_id') == '3') { ?>
<p class="warning"><span>As an <b><?=$this->user->Get('client_type');?></b>, you have the ability to Create, Update, Suspend, Unsuspend,
and Delete client accounts across the entire system.  These accounts can be Service Provider accounts (with permissions to create End User accounts),
or standalone End User accounts which do not have Client creation privileges.  Please do so with care.</span></p>
<? } ?>
<?=$this->dataset->TableHead();?>
<?
if (!empty($this->dataset->data)) {
	foreach ($this->dataset->data as $row) {
	?>
		<tr rel="<?=$row['id'];?>">
			<td><input type="checkbox" name="check_<?=$row['id'];?>" value="1" class="action_items" /></td>
			<td><?=$row['id'];?></td>
			<td><?=$row['username'];?></td>
			<td><?=$row['last_name'];?>, <?=$row['first_name'];?></td>
			<td><?=$row['email'];?></td>
			<td><? if ($row['suspended'] == '1') { ?><span class="suspended"><img src="<?=branded_include('images/failed.png');?>" alt="Suspended" /> Suspended</span><? } else { ?>Active<? } ?></td>
			<td class="options"><a href="<?=site_url('clients/edit/' . $row['id']);?>">edit</a></td>
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