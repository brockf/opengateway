<?=$this->load->view(branded_view('cp/header')); ?>
<h1>Update Credit Card</h1>
<form class="form" id="form_update_cc" method="post" action="<?=site_url('transactions/post_update_cc');?>">
<input type="hidden" name="recurring_id" value="<?=$recurring['id'];?>" />
<div id="transaction_amount">
	<fieldset>
		<legend>Payment Information</legend>
		<ul class="form">
			<li>
				<label>Recurring ID #</label>
				<?=$recurring['id'];?>
			</li>
			<li>
				<label>Customer</label>
				<a href="<?=site_url('customers/edit/' . $recurring['customer']['id']);?>"><?=$recurring['customer']['first_name'];?> <?=$recurring['customer']['last_name'];?></a>
			</li>
			<li>
				<label>Amount</label>
				<?=$this->config->item('currency_symbol');?><?=$recurring['amount'];?>
			</li>
			<li>
				<label>Interval</label>
				<?=$recurring['interval'];?> days
			</li>
			<li>
				<label>Start Date</label>
				<?=$recurring['start_date'];?>
			</li>
			<li>
				<label>Last Charge Date</label>
				<?=$recurring['last_charge_date'];?>
			</li>
			<li>
				<label>Next Charge Date</label>
				<?=$recurring['next_charge_date'];?>
			</li>
		</ul>
	</fieldset>
</div>

<? if (is_array($plans)) { ?>
<div>
	<fieldset>
		<legend>Recurring</legend>
		<ul class="form">			
			<li>
				<label>Recurring Plan</label>
				<select name="recurring_plan">
				<option value="0">no plan</option>
				<? foreach ($plans as $plan) { ?>
				<option value="<?=$plan['id'];?>" <? if ($recurring['plan']['id'] == $plan['id']) { ?>selected="selected"<? } ?>><?=$plan['name'];?></option>
				<? } ?>
				</select>
			</li>
		</ul>
	</fieldset>
</div>
<? } ?>

<div id="transaction_cc">
	<fieldset>
		<legend>Credit Card Information</legend>
		<ul class="form">
			<li>
				<label for="cc_number" class="full">Credit Card Number</label>
			</li>
			<li>
				<input type="text" class="text full required number" id="cc_number" name="cc_number" />
			</li>
			<li>
				<label for="cc_name" class="full">Credit Card Name</label>
			</li>
			<li>
				<input type="text" class="text full required" id="cc_name" name="cc_name" />
			</li>
			<li>
				<label for="cc_expiry" class="full">Credit Card Expiry</label>
			</li>
			<li>
				<select name="cc_expiry_month">
					<? for ($i = 1; $i <= 12; $i++) {
					       $month = str_pad($i, 2, "0", STR_PAD_LEFT);
					       $month_text = date('M',strtotime('2010-' . $month . '-01')); ?>
					<option value="<?=$month;?>"><?=$month;?> - <?=$month_text;?></option>
					<? } ?>
				</select>
				&nbsp;&nbsp;
				<select name="cc_expiry_year">
					<?
						$now = date('Y');
						$future = $now + 15;
						for ($i = $now; $i <= $future; $i++) {
						?>
					<option value="<?=$i;?>"><?=$i;?></option>
						<?
						}
					?>
				</select>
			</li>
			<li>
				<label for="cc_security" class="full">Credit Card Security Code</label>
			</li>
			<li>
				<input type="text" class="text full number" id="cc_security" name="cc_security" />
			</li>
		</ul>
	</fieldset>
</div>
<div id="transaction_gateway">
	<fieldset>
		<legend>Gateway</legend>
		<ul class="form">
			<li>
				<label for="gateway">Payment Gateway</label> 
				<select name="gateway">
					<? foreach ($gateways as $gateway) { ?>
						<option value="<?=$gateway['id'];?>" <? if ($recurring['gateway_id'] == $gateway['id']) {?> selected="selected"<? } ?>><?=$gateway['gateway'];?></option>
					<? } ?>
				</select>
			</li>
		</ul>
	</fieldset>
</div>
<div class="transaction submit">
	<input type="submit" name="go_update_cc" value="Update Credit Card" />
</div>
</form>
<?=$this->load->view(branded_view('cp/footer'));?>