<?=$this->load->view(branded_view('cp/header'), array('head_files' => '<script type="text/javascript" src="' . branded_include('js/form.address.js') . '"></script>'));?>
<h1><?=$form_title;?></h1>
<form class="form" id="form_account" method="post" action="<?=site_url($form_action);?>">
<fieldset>
	<legend>System Information</legend>
	<ul class="form">
		<li>
			<label for="username">Username</label><?=$form['username'];?>
		</li>
		<li>
			<label for="email">Email Address</label>
			<input type="text" autocomplete="off" class="text required email mark_empty" rel="email@example.com" id="email" name="email" value="<?=$form['email'];?>" />
		</li>
		<li>
			<label for="password">Password</label>
			<input type="password" autocomplete="off" class="text" id="password" name="password" value="" />
		</li>
		<li>
			<label for="password2">Repeat Password</label>
			<input type="password" autocomplete="off" class="text" id="password2" name="password2" value="" />
		</li>
		<li>
			<div class="help">Leave password fields blank to keep your current password.</div>
		</li>
	</ul>
</fieldset>
<fieldset>
	<legend>Personal Information</legend>
	<ul class="form">
		<li>
			<label for="first_name">Name</label>
			<input class="text required mark_empty" class="First Name" type="text" id="first_name" name="first_name" value="<?=$form['first_name'];?>" />&nbsp;&nbsp;<label style="display:none" for="last_name">Last Name</label><input class="text required mark_empty" rel="Last Name" type="text" id="last_name" name="last_name" value="<?=$form['last_name'];?>" />
		</li>
		<li>
			<label for="company">Company</label>
			<input type="text" class="text required" id="company" name="company" value="<?=$form['company'];?>" />
		</li>
		<li>
			<label for="address_1">Street Address</label>
			<input type="text" class="text required" name="address_1" id="address_1" value="<?=$form['address_1'];?>" />
		</li>
		<li>
			<label for="address_2">Address Line 2</label>
			<input type="text" class="text" name="address_2" id="address_2" value="<?=$form['address_2'];?>" />
		</li>
		<li>
			<label for="city">City</label>
			<input type="text" class="text required" name="city" id="city" value="<?=$form['city'];?>" />
		</li>
		<li>
			<label for="Country">Country</label>
			<select id="country" name="country" class="required"><? foreach ($countries as $country) { ?><option <? if ($form['country'] == $country['iso2']) { ?> selected="selected" <? } ?> value="<?=$country['iso2'];?>"><?=$country['name'];?></option><? } ?></select>
		</li>
		<li>
			<label for="state">Region</label>
			<input type="text" class="text" name="state" id="state" value="<?=$form['state'];?>" /><select id="state_select" name="state_select"><? foreach ($states as $state) { ?><option <? if ($form['state'] == $state['code']) { ?> selected="selected" <? } ?> value="<?=$state['code'];?>"><?=$state['name'];?></option><? } ?></select>
		</li>
		<li>
			<label for="postal_code">Postal Code</label>
			<input type="text" class="text required" name="postal_code" id="postal_code" value="<?=$form['postal_code'];?>" />
		</li>
		<li>
			<label for="phone">Phone</label>
			<input type="text" class="text" id="phone" name="phone" value="<?=$form['phone'];?>" />
		</li>
		<li>
			<label for="timezone">Timezone</label>
			<?=timezone_menu($form['gmt_offset']);?>
		</li>
	</ul>
</fieldset>
<div class="submit">
	<input type="submit" name="go_account" value="<?=ucfirst($form_title);?>" />
</div>
</form>
<?=$this->load->view(branded_view('cp/footer'));?>