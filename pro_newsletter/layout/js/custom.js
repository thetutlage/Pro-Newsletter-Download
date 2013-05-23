$(function(){
	alert('sd');
	// header drop down functionality //
	$('.navbar-inner li.dropdown').mouseover(function(){
		$('.dropdown-menu').hide();
		$(this).find('.dropdown-menu').show();
	});

	$('.navbar-inner li.dropdown').mouseout(function(){
		$('.dropdown-menu').hide();
		return false;
	});

	$('.syntax a.dropdown-toggle').click(function(){
		$(this).closest('div.syntax').find('.dropdown-menu').toggle();
		return false;
	});
});