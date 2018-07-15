WH = WH || {};

WH.imageFeedback = function() {
	// Add feedback link to article images
	var link = "<a class='rpt_img' href='#'><span class='rpt_img_ico'></span>Helpful?</a>";
	$('div.mwimg:not(.techicon,.summarysection)').prepend(link);

	$('.rpt_img').on('click', function(e) {
		e.preventDefault();
		var rpt_img = this;
		mw.loader.using( ['jquery.ui.dialog'], function () {
			e.preventDefault();

			var msg = 'This image:';
			var inputs = '<p class="rpt_margin_5px"><input type="radio" name="voteType" value="good" checked> Is helpful<input class="rpt_input_spacing" type="radio" name="voteType" value="bad"> Needs improvement</p>';
			inputs += '<p class="rpt_margin_20px">Please provide as much information as you can.<textarea name="rpt_reason" class="rpt_reason input_med"></textarea>';
			var buttons = '<p class="rpt_controls"><input type="button" class="button primary rpt_button" value="Submit"></input><a href="#" class="rpt_cancel">Cancel</a></s></p>';
			$("#dialog-box").html('<p>' + msg + '</p>' + inputs + '' + buttons);
			$("#dialog-box").dialog( {
				modal: true,
				title: "Send Image Feedback",
				width: 400,
				position: 'center',
				closeText: 'x'
			});

			$('.rpt_button').on('click', function(e) {
				var reason = $('.rpt_reason').val();
				if (reason.length) {
					var url = $(rpt_img).parent().children('a.image').first().attr('data-href').split(/[?#]/)[0];
					$.post('/Special:ImageFeedback', {imgUrl: url, aid: wgArticleId, 'reason': reason, 'voteType' : $('input[name=voteType]:checked').val()});
					$('#dialog-box').dialog('close');
				}
				return false;
			});
			$('.rpt_cancel').on('click', function(e) {
				e.preventDefault();
				$('#dialog-box').dialog('close');
				return false;
			});
			return false;
		});
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
