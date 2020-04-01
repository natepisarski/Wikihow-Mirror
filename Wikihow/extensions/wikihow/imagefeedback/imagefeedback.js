WH = WH || {};

WH.imageFeedback = function() {
	if($(window).width() < WH.largeScreenMinWidth) return;
	// Add feedback link to article images
	var link = "<a class='rpt_img' href='#'><span class='rpt_img_ico'></span>Helpful?</a>";
	$('div.mwimg').each(function(){
		if($(this).closest('.techicon, .summarysection').length == 0) {
			$(this).prepend(link);
		}
	});

	$('.rpt_img').on('click', function(e) {
		e.preventDefault();
		var rpt_img = this;
		$.get('/Special:BuildWikihowModal?modal=image_feedback', $.proxy(function(data) {
			$.modal(data, {
				zIndex: 100000007,
				maxWidth: 450,
				minWidth: 450,
				overlayCss: { "background-color": "#000" }
			});
		},this));

		//x or skip
		$(document).on("click", '#wh_modal_close, .rpt_cancel', function(e) {
			e.preventDefault();
			$.modal.close();
		});

		$(document).on("click", '.rpt_button', function(e) {
			var reason = $('.rpt_reason').val();
			if (reason.length) {
				var url;
				var $video = $( rpt_img ).parent().find( 'video' );
				if ( $video.length ) {
					// Extract wikiname from URL like /image/x/xx/{{NAME}}/scaler-params
					url = '/Image:' + $video.attr('data-poster').split( '/' )[5];
				} else {
					url = $(rpt_img).parent().children('a.image').attr("href").substring(1);
				}
				console.log( url );
				$.post('/Special:ImageFeedback', {imgUrl: url, aid: wgArticleId, 'reason': reason, 'voteType' : $('input[name=voteType]:checked').val()});
				$.modal.close();
			}
			return false;
		});
		return false;
	});

	var timer = 0;
	$('div.mwimg').hover(function() {
		var img = this;
		timer = setTimeout(function(){$(img).find('.rpt_img').fadeIn();}, 500);
	}, function() {
		clearTimeout(timer);
		$(this).find('.rpt_img').hide();
	});
};
if (document.readyState == 'complete') {
	WH.imageFeedback();
} else {
	$(window).load(WH.imageFeedback);
}
