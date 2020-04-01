( function ( mw, $ ) {
	window.WH = window.WH || {};

	window.WH.adminverifyreview = (function() {
		var toolUrl = '/Special:AdminVerifyReview';
		var indexCookieKey = "avr_index";
		var totalCookieKey = "avr_total";
		var disabled = false;
		var lastResult = null;
		var index = 0;
		var offset = 0;
		var itemsPerRequest = 50;
		var first = true;
		var totalRequested = 0;
		var cookieOptions = {
			expires: 365,
			path: '/',
			domain: '.' + mw.config.get('wgCookieDomain')
		};

		function getCookie(key) {
			return $.cookie(key, Number, cookieOptions) || 0;
		}

		function saveCookie(key, val) {
			$.cookie(key, val, cookieOptions);
		}

		function updateIndex(val) {
			if (val <= 0) {
				$('.avr_prev').css("background-color", "#EEE");
				val = 0;
			} else {
				$('.avr_prev').css("background-color", "#FFF");
			}
			var total = $('.avr_page').length;
			var totalDb = $('#avric').text();

			if (val >= total - 1 && total >= totalDb) {
				$('.avr_next').css("background-color", "#EEE");
			} else {
				$('.avr_next').css("background-color", "#FFF");
			}

			val += 1;

			var tot = $('#avric').text();

			if (tot == 0) {
				val = 0;
			}
			var pos = val + '/' + tot;

			$('#avrip').text(pos);
		}

		spinner = $.createSpinner({size: 'small', type: 'inline'});

		function displayResult(result) {
			lastResult = result;

			if (!result || !result['data']) {
				return;
			}
		}

		function disableInputs() {
			disabled = true;
			$('#avr_results').html('');
			$('#avr_body .button').css("cursor", "default");
			$('#avr_body .button').css("background-color", "#EEE");

			$('#avr_items').before(spinner);
		}

		function enableInputs() {
			disabled = false;
			spinner.remove();
			$('#avr_body .button').css("cursor", "pointer");
			$('#avr_body .button').css("background-color", "#FFF");
		}

		function removeCurrent() {
			$('.avr_page').eq(index).remove();
			var total = $('.avr_page').length;
			$('#avric').text($('#avric').text() - 1);
			if (total == 0) {
				var totalDb = $('#avric').text();
				if ( total < totalDb ) {
					handleClick('more');
				}
				return;
			}
			if (index == total) {
				index = index - 1;
				updateIndext(index);
			}
			$('.avr_page').eq(index).show();
			var current = $('.avr_page').eq(index);
			current.show();
			saveCookie(indexCookieKey, index);
		}

		function showNext() {
			var total = $('.avr_page').length;
			var totalDb = $('#avric').text();

			if (first == true) {
				index = getCookie(indexCookieKey);
				updateIndex(index);
			}
			if (index >= total - 1) {
				if ( total < totalDb ) {
					page = 0;
					handleClick('more');
				} else {
					$('#avr_results').text('you have reached the end of items to review');
				}
				return;
			}
			$('.avr_page').eq(index).hide();
			if (first == true) {
				first = false;
			} else {
				index = index + 1;
				updateIndex(index);
			}
			var current = $('.avr_page').eq(index);
			current.show();
			var h = current.find('.avr_article').height();
			if ( h > 40 ) {
				current.find('.avr_article').css('font-size', 24);
			}
			saveCookie(indexCookieKey, index);
		}

		function showPrev() {
			if (index <= 0) {
				updateIndex(index);
				return;
			}
			$('.avr_page').eq(index).hide();
			index = index - 1;
			updateIndex(index);
			var current = $('.avr_page').eq(index);
			current.show();
			saveCookie(indexCookieKey, index);
		}

		function addResults(results) {
			if (!results) {
				var total = $('.avr_page').length;
				var totalDb = $('#avric').text();
				if (totalRequested > total && totalRequested > totalDb)  {
					$('#avric').text(total);
				}

				if (total + offset <= totalDb) {
					saveCookie(totalCookieKey, total);
					offset += itemsPerRequest;
					handleClick('more');
				} else {
					// somehow we have less items that we think we should..
					// at least set the index so we can see the results
					index = total - 1;
					updateIndex(index);
					var current = $('.avr_page').eq(index);
					current.show();
					saveCookie(indexCookieKey, index);
				}
				return;
			}
			var $results = $(results);
			$results.find('.avr_article').each( function() {
				$(this).after($('.avr_buttons').first().clone().removeClass('hidden'));
				$(this).parent().append($('.avr_buttons').first().clone().removeClass('hidden'));
			});
			$('#avr_items').append($results);

			saveCookie(totalCookieKey, $('.avr_page').length);
			offset += itemsPerRequest;
			showNext();
		}

		function handleClick(action) {
			if (disabled == true) {
				return;
			}

			if ( action == 'avr_next' ) {
				showNext();
				return;
			}
			if ( action == 'avr_prev' ) {
				showPrev();
				return;
			}

			disableInputs();

			var pageId = $('.avr_page').eq(index).data('pageid');
			var revIdOld = $('.avr_page').eq(index).data('revid-old');
			var revIdNew = $('.avr_page').eq(index).data('revid-new');
			var itemCount = itemsPerRequest;

			if ( action == 'avr_first' ) {
				action = 'more';
			}

			var data = {
				action: action,
				pageid: pageId,
				revidold: revIdOld,
				revidnew: revIdNew,
				itemCount: itemCount,
				offset: offset
			};

			$.post(toolUrl, data, function (result) {
				debugResult(result);
				enableInputs();
				if (action == 'more') {
					totalRequested += itemCount;
					addResults(result['more']);
				} else {
					removeCurrent();
				}
			}, "json").fail( function(xhr, textStatus, errorThrown) {
				//$('#ed_results').html("<p>the import failed</p>");
				$('#avr_results').append(xhr.responseText);
				$('#avr_results').wrap("<pre></pre>");
				enableInputs();
			});
			return false;
		}

		function setupClickHandling() {
			$('#avr_body').on('click', '.avr_show_int', function (e) {
				e.preventDefault();
				$(this).parent().nextAll('.avr_int_rev').toggle();
			});

			$('#avr_checkbox').on('change', 'input[type=checkbox]', function(e) {
				if (this.checked) {
					$('.avr_advanced').show();
				} else {
					$('.avr_advanced').hide();
				}
			});

			//for clicks on next /prev button which might get added to dom later on
			$('#avr_body').on('click', '.avr_button', function (e) {
				e.preventDefault();
				var id = $(this).data('id');
				handleClick(id);
			});

			$(document).keydown(function(e) {
				switch(e.which) {
					case 37: // left
					handleClick('avr_prev');
					break;

					case 39: // right
					handleClick('avr_next');
					break;

					default: return; // exit this handler for other keys
				}
				e.preventDefault(); // prevent the default action (scroll / move caret)
			});
		}


		function debugResult(result) {
			// adds debugging log data to the debug console if exists
			if (WH.consoleDebug) {
				WH.consoleDebug(result['debug']);
			}
		}
		return {
			init : function() {
				setupClickHandling();
				var totalDb = $('#avric').text();
				var tot = getCookie(totalCookieKey);
				var i = getCookie(indexCookieKey);
				if ( i >= totalDb ) {
					i = totalDb - 2;
				}
				if (i >= tot && tot > 0) {
					i = tot - 2;
				}
				saveCookie(indexCookieKey, i);
				updateIndex(i);

				handleClick('avr_first');
			},
			imgError: function(image) {
				$(image).attr('src', 'skins/WikiHow/images/80x80_user.png');
			},
		};
	}());

	$( function() {
		WH.adminverifyreview.init();
	});
}( mediaWiki, jQuery ) );

