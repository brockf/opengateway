<?=$this->load->view('cp/header', array('head_files' => '<script type="text/javascript" src="' . site_url('js/form.address.js') . '"></script>
<script type="text/javascript" src="' . site_url('js/form.transaction.js') . '"></script>'));?>
<h1>New Transaction</h1>
<form class="form" id="form_client" method="post" action="transactions/post">
<fieldset>
	<legend>Customer Information</legend>
	<ul class="form">
		<li>
			<label for="first_name">Name</label>
			<input class="text" type="text" id="first_name" name="first_name" />&nbsp;&nbsp;<label style="display:none" for="last_name">Last Name</label><input class="text" type="text" id="last_name" name="last_name" />
		</li>
		<li>
			<label for="company">Company</label>
			<input type="text" class="text" id="company" name="company" />
		</li>
		<li>
			<label for="address_1">Street Address</label>
			<input type="text" class="text" name="address_1" id="address_1" />
		</li>
		<li>
			<label for="address_2">Address Line 2</label>
			<input type="text" class="text" name="address_2" id="address_2" />
		</li>
		<li>
			<label for="city">City</label>
			<input type="text" class="text" name="city" id="city" />
		</li>
		<li>
			<label for="Country">Country</label>
			<select id="country" name="country"><? foreach ($countries as $country) { ?><option value="<?=$country['iso2'];?>"><?=$country['name'];?></option><? } ?></select>
		</li>
		<li>
			<label for="state">Region</label>
			<input type="text" class="text" name="state" id="state" /><select id="state_select" name="state_select"><? foreach ($states as $state) { ?><option value="<?=$state['code'];?>"><?=$state['name'];?></option><? } ?></select>
		</li>
		<li>
			<label for="postal_code">Postal Code</label>
			<input type="text" class="text" name="postal_code" id="postal_code" />
		</li>
		<li>
			<label for="phone">Phone</label>
			<input type="text" class="text" id="phone" name="phone" />
		</li>
	</ul>
</fieldset>
<div class="submit">
	<input type="submit" name="go_transation" value="Submit Transaction" />
</div>
</form>
<?=$this->load->view('cp/footer');?>