<?php
	$text ='<h2>Coupon Types</h2>
	
	<p><b>Initial Charge Price Reduction</b> applies the price reduction to the initial charge only. Any recurring charges are full price.</p>
	
	<p><b>Recurring Price Reduction</b> applies the price reduction to all charges, <i>except the initial charge</i>.</p>
	
	<p><b>Total Price Reduction</b> applies the price reduction to all charges, both the initial charge and all recurring charges.</p>
	
	<p><b>Free Trial</b> allows a member to try out your product/service for a limited number of days before they are charged their initial charge.</p>';

	$this->navigation->SidebarNote($text, 'note help');
?>

<?= $this->load->view(branded_view('cp/header'), array('head_files' => '<script type="text/javascript" src="' . branded_include('js/form.coupon.js') . '"></script>')); ?>

<h1><?=$form_title;?></h1>

<form class="form validate" id="form_coupon" method="post" action="<?=$form_action;?>">

    <fieldset>
        <legend>Coupon Information</legend>

        <ul class="form">
            <li style="list-style: none"><input type="hidden" id="coupon_id" name="coupon_id" value="<?= isset($coupon) ? $coupon['id'] : '' ?>"></li>

            <li ><label  for="coupon_name">Coupon Name</label><input type="text" class="text required" style="width:250px" name="coupon_name" rel="" id="coupon_name" value="<?= isset($coupon) ? $coupon['name'] : '' ?>"></li>

            <li>
                <div class="help">
                    Something for you to recognize the coupon by.
                </div>
            </li>

            <li><label  for="coupon_code">Coupon Code</label><input type="text" class="text required" style="width:250px" name="coupon_code" rel="" id="coupon_code" value="<?= isset($coupon) ? $coupon['code'] : '' ?>"></li>

            <li>
                <div class="help">
                    The code the customer must enter.
                </div>
            </li>

            <li><label  for="coupon_start_date">Start Date</label>
            <input type="text" class="text datepick required mark_empty" rel="yyyy-mm-dd" style="width:8em" name="coupon_start_date" rel="" id="coupon_start_date" value="<?= isset($coupon) ? $coupon['start_date'] : date('Y-m-d') ?>"></li>

            <li>
            	<label  for="coupon_end_date">Expiry Date</label>
	            <input type="text" class="text datepick mark_empty" rel="yyyy-mm-dd" style="width:8em" name="coupon_end_date" rel="" id="coupon_end_date" value="<?= (isset($coupon) and $coupon['end_date'] != FALSE) ? $coupon['end_date'] : '' ?>">
	            &nbsp;&nbsp;<input id="no_expiry" type="checkbox" name="no_expiry" value="1" <? if (!isset($coupon) or $coupon['end_date'] == FALSE) { ?>checked="checked"<? } ?> /> No expiry
	        </li>

            <li>
            	<label  for="coupon_max_uses">Maximum Uses</label>
            	<input type="text" class="text" style="width:6em" name="coupon_max_uses" rel="" id="coupon_max_uses" value="<?= isset($coupon) ? $coupon['max_uses'] : '' ?>">
            	&nbsp;&nbsp;<input id="no_limit" type="checkbox" name="no_limit" value="1" <? if (!isset($coupon) or $coupon['max_uses'] == FALSE) { ?>checked="checked"<? } ?> /> Unlimited usage
            </li>

            <li>
                <div class="help">
                    The maximum number of customers that can use the coupon.
                </div>
            </li>

            <li ><label  for="coupon_customer_limit">One Per Customer?</label>
            <input type="checkbox" id="coupon_customer_limit" name="coupon_customer_limit"  value="1" <?= isset($coupon) && $coupon['customer_limit'] == 1  ? 'checked="checked"' : '' ?>></li>

            <li>
                <div class="help">
                    Check to limit each customer to a single use.
                </div>
            </li>

            <li id="row_coupon_type"><label  for="coupon_type_id">Coupon Type</label><select name="coupon_type_id" >
			<?php foreach ($coupon_types as $type) :?>
				<option value="<?php echo $type->coupon_type_id ?>" <?= isset($coupon) && $coupon['type_id'] == $type->coupon_type_id ? 'selected="selected"' : '' ?>><?php echo $type->coupon_type_name; ?></option>
			<?php endforeach; ?>			    
            </select></li>
           
        </ul>
    </fieldset>

    <fieldset id="fs_reduction">
        <legend>Price Reduction</legend>

        <ul class="form coupon_reduction">
            <li class="reduction"><label  for="coupon_reduction_type">Reduction Type</label><select name="coupon_reduction_type" >
                <option value="0" <?= isset($coupon) && $coupon['reduction_type'] == 0 ? 'selected="selected"' : '' ?>>Percent</option>
                <option value="1" <?= isset($coupon) && $coupon['reduction_type'] == 1 ? 'selected="selected"' : '' ?>>Fixed Amount</option>
            </select></li>

            <li class="reduction"><label  for="coupon_reduction_amt">Reduction Amount</label><input type="text" class="text" style="width:6em" name="coupon_reduction_amt" rel="" id="coupon_reduction_amt" value="<?= isset($coupon) ? $coupon['reduction_amt'] : '' ?>"></li>

			<li class="free-trial"><label  for="coupon_trial_length">Free Trial Length</label>
			<input type="text" class="text mark_empty" style="width:6em" name="coupon_trial_length" rel="in days" id="coupon_trial_length" value="<?= isset($coupon) ? $coupon['trial_length'] : '' ?>"></li>

            <li>
                <div class="help">
                    The amount of the discount.
                </div>
            </li>

            <li class="plan_select">
            	<label  for="plans[]">Subscription Plans</label>
            	<select name="plans[]"  multiple="multiple">
	            	<option value="0" <? if (!isset($coupon) or empty($coupon['plans'])) { ?>selected="selected"<? } ?>>All plans and recurring charges</option>
	                <?php foreach ($plans as $plan) :?>
						<option value="<?php echo $plan['id'] ?>" <?= isset($coupon['plans']) && in_array($plan['id'], $coupon['plans']) ? 'selected="selected"' : '' ?>><?php echo $plan['name']; ?></option>
					<?php endforeach; ?>
	            </select>
            </li>
            
            <li class="plan_select">
            	<div class="help">
            		If you don't select any plans, or select "All plans", this coupon will be able to be used with any plan.
            	</div>
            </li>
       
        </ul>
    </fieldset>

<div class="submit">
	<input type="submit" class="button" name="add_coupon" value="Save Coupon" />
</div>
</form>

<?= $this->load->view(branded_view('cp/footer')); ?>