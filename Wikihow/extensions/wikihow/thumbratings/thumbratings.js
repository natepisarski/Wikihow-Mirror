WH.ThumbRatings = (function($) {

	var controller = '/Special:ThumbRatings';
	var clicked = [];

	$(document).on('click', 'a[class^=trvote_]', function(e) {
		e.preventDefault();
		var className = $(this).attr('class').split(' ')[0];
		var vals = className.split('_');
		if ($.inArray(vals[2], clicked) == -1) {
			clicked.push(vals[2]);

			var data = {'aid' : wgArticleId, 'hash' : vals[2], 'vote' : vals[1], 'type' : vals[3]};
			$.get(controller, data);

			if (typeof ga == 'function') {
				var action = vals[3] + '-' + vals[1];
				ga('send', 'event', 'm-thumbrating', action, wgTitle);
			}

			showResponse(className, vals);
		}
	});

	function showResponse(className, vals) {
		var msg = vals[1] == 'up' ? getUpMsg() : getDownMsg();
		$('a.'+className).parent().html( msg );
	}

	function getUpMsg() {
		var msgs = ['Thanks!'];

		return msgs[Math.floor(Math.random() * msgs.length)];
	}

	function getDownMsg() {
		return getUpMsg();
	}

	function hideAll() {
		$('.trvote_box').hide();
	}

	return {
		hideAll: hideAll
	};

})(jQuery);
