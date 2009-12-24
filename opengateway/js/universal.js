$(document).ready(function() {
	$('#notices div').animate({opacity: 1.0},2000).fadeOut('slow');
	
	$('#topnav li.parent').hover(function () {
		$(this).children('ul.children').slideDown(100);
	}, function () {
		$(this).children('ul.children').slideUp(100);
	});
});