(function($,mw) {

if ($('#page_helpfulness_box').length) {

	$('#page_helpfulness_box').on("click", '.phr_ratings_show_link', function(e) {
		e.preventDefault();
		$('.phr_ratings_old').toggle();
		var $a = $(this).find('a');
		$a.html($a.text() == 'hide past ratings' ? 'show past ratings' : 'hide past ratings');
	});

	$(window).load(function() {
		if ($('.phr_accuracy .phr_ratings_old').length) {
			$('.phr_accuracy .phr_ratings_old li').first().append("<a href='' id='phr_ratings_undo_clear'>undo</a>");
			$('.phr_accuracy .phr_ratings_old li').first().append("<div id='dialog-confirm'></div>");
		}

	});

	$('#page_helpfulness_box').on("click", '#phr_ratings_undo_clear', function(e) {
		e.preventDefault();
		var r = confirm("Do you want to restore the most recently cleared ratings?");
		if (r == true) {
			postData = {'action':'undolastclear', 'pageId':wgArticleId};
			$.post('/Special:PageHelpfulness', postData, function(result) {
				// alternately we can display some better formatted message
				alert('restored ' + result['restored'] + ' ratings');
				location.reload();
			}, 'json');
		}
	});

	$('#page_helpfulness_box').html('Loading...');
	var type = "article";
	if ( window.location.pathname.match(/^\/Sample\//) ) {
		type = "sample";
	}

	var target = '';
	if (type == "sample") {
		target = wgSampleName;
	} else if (typeof wgPageHelpfulnessArticleId != 'undefined') {
		target = wgPageHelpfulnessArticleId;
	} else {
		target = wgTitle;
	}

	getData = {'action':'ajaxstats', 'target':target, 'type':type};
	$.get('/Special:PageHelpfulness', getData, function(data) {
			var result = (data && data['body']) ? data['body'] : 'Could not retrieve stats';
			$('#page_helpfulness_box').html(result);
			if (data && data['error']) {
				console.log(data['error']);
			}
			// set color of window
			if ($("#page_helpfulness_box").length) {
				var hue = $('.phr_accuracy').attr('rating');
				if (hue) {
					var scale = Math.abs(hue - 50) / 50;
					var maxSat = 100 - parseInt(scale * 63);
					var sat = maxSat - 21;
					var light = 60;
					var maxLight = 90 - parseInt(scale * 11);
					var light = maxLight - 21;
					var incr = 3;
					$("#page_helpfulness_box").css({"backgroundColor": "hsl("+hue+", " + sat + "%, "+light+"%)"});
					var interval = setInterval(function(){
						if (light < maxLight) {
							light = light + incr;
						}
						if (sat < maxSat) {
							sat = sat + incr;
						}
						if (sat >= maxSat && light >= maxLight) {
							clearInterval(interval);
						}
						$("#page_helpfulness_box").css({"backgroundColor": "hsl("+hue+", " + sat + "%, "+light+"%)"});
					}, 30);
				}

				// Inject Method Helpfulness widget
				if ($('#page_helpfulness_box').hasClass('smhw')) {
					var methodHelpfulnessDiv = $('<div/>', {
						id: 'method_helpfulness_box'
					});
					var firstDiv = $('#page_helpfulness_box>div:nth-child(2)');
					if (firstDiv.length) {
						firstDiv.after(methodHelpfulnessDiv);
					} else {
						$('#page_helpfulness_box').append(methodHelpfulnessDiv);
					}

					$('#page_helpfulness_box').removeClass('smhw');
				}
			}
		}, 'json');
}

})(jQuery, mediaWiki);
