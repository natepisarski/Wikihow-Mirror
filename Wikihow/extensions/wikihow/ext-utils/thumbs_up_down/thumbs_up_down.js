/*
* UI handlers for thumbs up / thumbs down functionality
 */
(function($) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.ThumbsUpDown = {
		VOTE_UP_EVENT: 'thumbs_up_down/up_vote',
		VOTE_DOWN_EVENT: 'thumbs_up_down/down_vote',

		init: function() {
			this.formatCounts();
			this.initListeners();
		},

		formatCounts: function() {
			$('.wh_count').each(function(i, el) {
				var count = parseInt($(el).html());
				// We only show counts up to 9999. After that we show
				// 9999+
				if (count > 9999) {
					$(el).html('9999+');
				}
			});
			//alert('implement formatcounts');
		},

		initListeners: function() {
			$(document).on('click', '.wh_vote_up, .wh_vote_down', function(e) {
				e.preventDefault();

				var $container = $(this).closest('.wh_vote_container');
				if ($container.hasClass('clicked')) {
					// Already submitted a vote - so ignore this one
					return;
				}

				var eventType = WH.ThumbsUpDown.VOTE_DOWN_EVENT;
				if ($(this).hasClass('wh_vote_up')) {
					eventType = WH.ThumbsUpDown.VOTE_UP_EVENT;
					$container.addClass('up');
				}

				if ($(this).hasClass('wh_vote_down')) {
					$container.addClass('down');
				}

				WH.ThumbsUpDown.increment(this);
				$container.addClass('clicked');
				$(this).addClass('clicked');
				var elem = this;
				$.publish(eventType, [elem]);
			});
		},

		increment: function(container) {
			var $count = $(container).find('.wh_count').first();
			var currentVal = parseInt($count.html());
			// We only show counts up to 9999. After that we show
			// 9999+
			if (currentVal != 9999) {
				$count.html(currentVal + 1);
			}


		}
	};

	WH.ThumbsUpDown.init();
}(jQuery));
