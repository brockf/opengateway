$(document).ready(function(){
	
	// Hook into our coupon type selector so we can 
	// show or hide options based on coupon type selected.
	$("#row_coupon_type select").change(function(){
		var coupon_type = $("#row_coupon_type select :selected").val();
				
		switch (coupon_type)
		{
			case '1':		// Price Reduction
			case '2':
			case '3':
				$(".free-trial").css("display", "none");
				$(".reduction").fadeIn();
				break;
			case '4': 	// Free Trial
				$(".reduction").css("display", "none");
				$(".free-trial").fadeIn();
				break; 
		}
	});
	
	// Trigger our coupon type change event so everything looks correct
	$("#row_coupon_type select").trigger("change");
});