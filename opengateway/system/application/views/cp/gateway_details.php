<?=$this->load->view('cp/header');?>
<h1><?=$form_title;?></h1>
<form class="form" id="form_plan" method="post" action="<?=$form_action;?>">
<input type="hidden" name="external_api" value="<?=$external_api;?>" />
<fieldset>
	<legend>Gateway Settings</legend>
	<ul class="form">
	<? foreach ($fields as $name => $field) { ?>
	<? if (!isset($values[$name])) { $values[$name] = ''; } ?>
		<li>
		<label for="<?=$name;?>"><?=$field['text'];?></label>
		<? if ($field['type'] == 'text') { ?>
			<input type="text" class="text required" name="<?=$name;?>" id="<?=$name;?>" value="<?=$values[$name];?>" />
		<? } elseif ($field['type'] == 'radio') { ?>
			<? foreach ($field['options'] as $value => $display) { ?>
				<input type="radio" id="<?=$name;?>" name="<?=$name;?>" class="required" value="<?=$value;?>" <? if ($values[$name] == $value) { ?>checked="checked"<? } ?> />&nbsp;<?=$display;?>&nbsp;&nbsp;&nbsp;
			<? } ?>
		<? } elseif ($field['type'] == 'select') { ?>
			<select id="<?=$name;?>" name="<?=$name;?>" class="required">
			<? foreach ($field['options'] as $value => $display) { ?>
				<option value="<?=$value;?>" <? if ($values[$name] == $value) { ?>selected="selected"<? } ?>><?=$display;?></option>
			<? } ?>
			</select>
		<? } ?>
		</li>
	<? } ?>
</fieldset>
<div class="submit">
	<input type="submit" name="go_gateway" value="Save Gateway" />
</div>
</form>
<?=$this->load->view('cp/footer');?>