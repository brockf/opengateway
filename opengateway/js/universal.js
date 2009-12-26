$(document).ready(function() {
	$('#notices div').animate({opacity: 1.0},2000).fadeOut('slow');
	
	$('#topnav li.parent').hover(function () {
		$(this).children('ul.children').slideDown(100);
	}, function () {
		$(this).children('ul.children').slideUp(100);
	});
	
	// table row colours
	
	$('table.dataset tr:even').addClass('odd');
	
	// handle row clicks/checkbox clicks
	$('table.dataset tbody tr').click(function(event) {
		if (event.target.type !== 'checkbox') {
			$(this).find('input.action_items').each(function () {
				if ($(this).is(':checked')) {
					$(this).parent().parent().removeClass('selected');
					$(this).attr('checked',false);
					return false;
				}
				else {
					$(this).parent().parent().addClass('selected');
					$(this).attr('checked','checked');
					return false;
				}
			});
		}
		else {
			$(this).find('input.action_items').each(function () {
				if ($(this).is(':checked')) {
					$(this).parent().parent().addClass('selected');
				}
				else {
					$(this).parent().parent().removeClass('selected');
				}
			});
		}
	});
	
	$('table.dataset #check_all').click(function() {
		if ($(this).is(':checked')) {
			$('input.action_items').each(function () {
				$(this).parent().parent().addClass('selected');
				$(this).attr('checked','checked');
			});
		}
		else {
			$('input.action_items').each(function () {
				$(this).parent().parent().removeClass('selected');
				$(this).attr('checked',false);
			});
		}
	});
	
	// filters
	
	$('input#reset_filters').click(function () {
		window.location.href = $('#base_url').html()+$('#class').html()+'/'+$('#method').html()+'/'+$('#page').html();
	});
	
	$('#dataset_form tr.filters input.text').each(function () {
		if ($(this).val() == '' || $(this).val() == 'filter results') {
			$(this).val('filter results');
			$(this).addClass('emptyfilter');
		}
	});
	
	$('#dataset_form tr.filters input.text').focus(function() {
		if ($(this).val() == 'filter results') {
			$(this).val('');
			$(this).removeClass('emptyfilter');
		}
	});
	
	Date.format = 'yyyy-mm-dd';
	$('#dataset_form input.date_start').datePicker({clickInput:true,startDate:'2009-01-01'});
	$('#dataset_form input.date_end').datePicker({clickInput:true,startDate:'2009-01-01'});
	
	$('#dataset_form input.date_start').bind(
		'dpClosed',
		function(e, selectedDates)
		{
			var d = selectedDates[0];
			if (d) {
				d = new Date(d);
				$('#dataset_form input.date_end').dpSetStartDate(d.addDays(1).asString());
			}
		}
		
	);
	$('#dataset_form input.date_end').bind(
		'dpClosed',
		function(e, selectedDates)
		{
			var d = selectedDates[0];
			if (d) {
				d = new Date(d);
				$('#dataset_form input.date_start').dpSetEndDate(d.addDays(-1).asString());
			}
		}
	);
	
	$('#dataset_form').submit(function () {
		var serialized_filters = $('#dataset_form tr.filters input.text, tr.filters select').serialize();
		
		$.post($('#base_url').html()+'dataset/prep_filters', { filters: serialized_filters },
		  function(data){
		    window.location.href = $('#base_url').html()+$('#class').html()+'/'+$('#method').html()+'/'+data+'/'+$('#page').html();
		  });
		return false;
	});
	
	$('#dataset_export_button').click(function () {
		var serialized_filters = $('#dataset_form tr.filters input.text, tr.filters select').serialize();
		
		$.post($('#base_url').html()+'dataset/prep_filters', { filters: serialized_filters },
		  function(data){
		    window.location.href = $('#base_url').html()+$('#class').html()+'/'+$('#method').html()+'/'+data+'/'+$('#page').html()+'/export';
		  });
		return false;
	});
	
	$('input.action_button').click(function () {
		var serialized_items = $('#dataset_form input.action_items').serialize();
		
		var link = $(this).attr('rel');
		var return_link = $('#current_url').html();
		
		$.post($('#base_url').html()+'dataset/prep_actions', { items: serialized_items, return_url: return_link },
		  function(data){
		    window.location.href = link+'/'+data;
		  });
		return false;
	});
});