jQuery.extend(WH, (function($) {

	function CheckMarks() {
		var messages = [];
		var numSteps = null;
		var randomizer = '/Special:Randomizer';

		function parseJSON() {
			if (typeof(JSON) == 'object') {
				messages = JSON.parse($('#chk_praise_data').text());
			}
		}

		function isLastStep(li) {
			var lastLi = $('#steps ol:last').children('li:last');
			return lastLi[0] == li[0];
		}

		function stepNum(li) {
			var ols = $('#steps:first ol');
			var stepNum = 0;
			var found = false;
			$.each(ols, function(i, ol) {
				$.each($(ol).children('li'),function (j, currentLi) {
					stepNum++;
					currentLi = currentLi;
					if (currentLi == li[0]) {
						found = true;
						return false;
					}
				});
				if (found) { return false; }
			});
			return stepNum;
		}

		function randomFromTo(from, to) {
		 	return Math.floor(Math.random() * (to - from + 1) + from);
		}

		function generateMessage(li) {
			var html = '';

			if (isLastStep(li) && !isIAppURL() && messages.last.length) {
				// Get the last step message
				var lastMessage = messages.last.pop();
				html = getMessageHtml(lastMessage, true);
			} else if (messages.msgs.length) {
				if (stepNum(li) == 1) {
					// Always show a message on the first step
					html = getMessageHtml(messages.msgs.pop(), false);
				} else {
					// 25% of the rest of the time show a message
					if (randomFromTo(1, 100) < 25) {
						html = getMessageHtml(messages.msgs.pop(), false);
					}
				}
			}
			return html;
		}

		function getMessageHtml(msg, lastStep) {
			var div = $('#chk_praise_content').clone();
			if (lastStep) {
				msg = msg + " How about <a href='" + randomizer + "'> another</a>?";
			}
			$(div).find('.chk_msg').html(msg);
			if (!lastStep) {
				$(div).find('.chk_img_final').hide();
			}
			return $(div).html();
		}

		function initEventListeners() {
			// CheckMark message close handler
			$('#steps').on('click', '.chk_close', function(e) {
				e.preventDefault();
				$(this).parents('.chk_praise').slideUp('slow');
			});

			// CheckMark click handlers
			$('.step_checkbox').on('click', function() {
				var li = $(this).parents('li');
				if ($(this).hasClass('step_checked')) {
					$(this).removeClass('step_checked');
					$(this).children('.checkwhite').css('background-image','url(/extensions/wikihow/mobile/images/checkmark_grey.svg), url(/extensions/wikihow/mobile/images/checkmark_grey.png)');
					//li.children('.step_content').removeClass('txt_strike');
					li.children('.step_content').fadeTo('slow', 1);
				}
				else {
					//li.children('.step_content').addClass('txt_strike');
					li.children('.step_content').fadeTo('slow', 0.3);
					if (!li.children('.chk_praise').length) {
						li.append(generateMessage(li));
						li.children('.chk_praise').slideDown('slow');
					}
					$(this).addClass('step_checked');
					$(this).children('.checkwhite').css('background-image','url(/extensions/wikihow/mobile/images/checkmark.svg), url(/extensions/wikihow/mobile/images/checkmark.png)');
					if (!isIAppURL()) trackCheck(li);
				}
				return false;
			});
		}

		function isIAppURL() {
			return location.href.match(/[?&]platform=/i) || location.href.match(/^file:\/\/\//);
		}

		function trackCheck(li) {
			var step = stepNum(li);
			var action = '';
			if (isLastStep(li)) {
				action = 'step-last';
			} else if (step == 1 || step == 2 || step == 3) {
				action = 'step-' + step;
			} else {
				action = 'step-other';
			}
			try {
				if (action.length && typeof ga == 'function') {
					ga('send', 'event', 'm-checks', action, wgTitle);
				}
			} catch(err) {}
		}

		this.init = function() {
			numSteps = $('#steps:first ol').children('li').size();
			parseJSON();
			initEventListeners();
		}
	}

	return {
		CheckMarks: new CheckMarks()
	};
})(jQuery));
