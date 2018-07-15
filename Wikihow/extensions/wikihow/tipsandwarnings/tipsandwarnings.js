(function($) {
	var url = '/Special:TipsAndWarnings';
	var clicked = [];
	$(document).on('click', '.addtip', function(e) {
		e.preventDefault();
		
		$(this).hide();
		$('.tip_waiting').show();
		
		newTip = $.trim($(this).parent().find('textarea').val());
		
		if(newTip != "") {
		
			var data = {'aid' : wgArticleId, 'tip' : newTip};
			$.get(url, data, function(data){
				//add the new tip in
				$("#tips ul").append("<li><div>" + newTip + "<div class='clearall'></div></div></li>");
				
				//remove the input field
				$(".addTipElement").remove();
			}, "json");
		
		}
	});

})(jQuery);
