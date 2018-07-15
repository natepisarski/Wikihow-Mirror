var kbToolURL = "/Special:KnowledgeBox";

(function($) {
	var uagent = navigator.userAgent.toLowerCase();

	var isMobileDomain = window.location.hostname.match(/\bm\./) != null;
	var mobileDevice = null;
	var mobile = null;
	var goodBrowser = null;

	var activeTransition = false;
	var editBoxExpanded = false;

	function isMobileDevice() {
		if (null == mobileDevice) {
			if (uagent.match(/iphone|ipad|android|silk|webos|ipod|blackberry|windows phone|opera mini|iemobile/i)) {
				mobileDevice = true;
			} else {
				mobileDevice = false;
			}
		}

		return mobileDevice;
	}

	function isMobile() {
		if (null == mobile) {
			mobile = isMobileDomain || isMobileDevice();
		}

		return mobile;
	}

	function isGoodBrowser() {
		if (null == goodBrowser) {
			goodBrowser =
				($.browser.msie && parseInt($.browser.version) >= 8)
				|| (($.browser.firefox || $.browser.mozilla) && parseInt($.browser.version) >= 5)
				|| ($.browser.chrome && parseInt($.browser.version) >= 14)
				|| ($.browser.opera && parseInt($.browser.version) >= 15)
				|| ($.browser.safari && parseInt($.browser.version) >= 5);
		}

		return goodBrowser;
	}

	/*
	 * MS are a bit sneaky with the uagent of newer IEs
	 */
	function isNewIE() {
		return (uagent.indexOf('trident') != -1)
			? parseInt(uagent.match(/rv(?::|\s)?(\d+)/)[1])
			: false;
	}

	if (!(isGoodBrowser() || isNewIE()) || isMobile()) {
		// Disable for old browsers
		$('#knowledgebox').remove();
		return;
	} else {
		// Show for not-so-old browsers
		$('#knowledgebox').show();
	}

	if ($.browser.msie || isNewIE() && isNewIE() < 12) {
		// Simplify textbox and notched bar in all IEs from 11 and down.
		$('.kbnotchedbar').addClass('kbbotbg');
		$('.kbnotchl,.kbnotchr').remove();
		$('#kbcontentbox').remove();
		$('.kbnewcontent').show();
	}

	if ($.browser.msie && parseInt($.browser.version) == 8) {
		// A few things are even more broken in IE8 -- simplify them
		$('.kbclose>div').remove();
		$('.kbclose').html('X').css('padding-top', '5px');
	}

	var currentAid = 0;
	var spentTopicIds = {}; // Mimic a set of unique values using obj

	var closeEditBox = function () {
		if (activeTransition) return;

		activeTransition = true;

		$('.kbedit_section')
			.slideUp(400)
			.animate(
				{ opacity: 0 },
				{ queue: false, duration: 400 }
			);

		$('.kbarrow').hide();

		$('.kbcol').each(function () {
			var e = $(this);

			setTimeout(function () {
				if (e.hasClass('kbdeadcol')) {
					e.removeClass('kbvisible kbopaque').addClass('kbhidden kbtransparent');
					e.find('kbbotwrapper').addClass('kbnoclick');
					return;
				}
				e.find('.kbbotstripe').hide();
				e.find('.kbbotwrapper').show();
				e.removeClass('kbhidden kbtransparent')
					.addClass('kbvisible kbopaque')
					.animate(
						{ opacity: 1 },
						{ queue: false, duration: 400 }
				);
				e.animate(
					{ height: '200px' },
					{ queue: false, duration: 200 }
				);
				e.find('.kbtop')
					.animate(
						{ height: '100%' },
						{ queue: false, duration: 200 }
					);
				e.find('.kbbot')
					.animate(
						{ height: '17%' },
						{ queue: false, duration: 200 }
					);
			}, 200);
		});

		setTimeout(function () {
			activeTransition = false;
			editBoxExpanded = false;
			$('.kbbotwrapper').removeClass('kbnoclick');
		}, 500);
	};

	var focusTextBox = function () {
		$('#kbcontentbox').hide();
		$('#kbcontent').val('');
		$('.kbnewcontent').show();
		$('#kbcontent').focus();
	};

	var unfocusTextbox = function () {
		if ($.browser.msie || isNewIE() && isNewIE() < 12) return;
		var newContent = $.trim($('#kbcontent').val());
		if (newContent == '') {
			$('.kbnewcontent').hide();
			$('#kbcontentbox').show();
		}
	};

	var updateIDSet = function () {
		$('.kbcol').each(function () {
			spentTopicIds[$(this).attr('id')] = true;
		});
	};

	var getSpentIDs = function () {
		var spentIDs = [];
		for (var p in spentTopicIds) {
			spentIDs.push(p);
		}
		return spentIDs;
	};

	var thanksFlyby = function () {
		var thanksText = $('#kbthankstext').val();
		var thanksElem = $("<div class='kbthanks'>" + thanksText + "</div>");
		$('#knowledgebox').append(thanksElem);
		thanksElem.hide().show('fast');
		setTimeout(function () {
			thanksElem.animate(
				{ top: '10%', opacity: 0 },
				{ queue: false, duration: 600 }
			);
		}, 3000);
		setTimeout(function () {
			thanksElem.remove();
		}, 3600);
	};

	var swapOutColumn = function (kbid, newTopic) {
		var col = $('.kbcol[id="' + kbid + '"]');
		col.addClass('kbdeadcol');

		col.animate(
			{ opacity: 0, height: '0' },
			{ queue: false, duration: 200 }
		);

		setTimeout(function () {
			// Remove RateTool hijack hack
			if (col.hasClass('kbhijack')) {
				col.removeClass('kbhijack');
				col.find('.kbtellus').removeClass('kbnodisplay');
				col.find('.kbhijackprompt').remove();
			}
		}, 200);

		if (newTopic !== undefined && newTopic !== false) {
			col.removeClass('kbdeadcol');
			setTimeout(function () {
				col.attr('id', newTopic['aid']);
				col.find('.kbctopic').html(newTopic['topic']);
				col.find('.kbttopic').html(newTopic['topic'] + '?');
				col.find('.kbcphrase').html(newTopic['phrase']);
				col.find('.kbcthumb').attr('src', newTopic['thumburl']);
			}, 450);
		} else {
			col.removeClass('kbopaque kbvisible')
				.addClass('kbhidden kbtransparent');
		}
	};

	var swapOutArticle = function (col) {
		var kbAid = col.attr('id');

		updateIDSet();

		var data = {
			'spentIDs': JSON.stringify(getSpentIDs())
		};

		var botwrapper = col.find('.kbbotwrapper');
		activeTransition = true;
		botwrapper.addClass('kbnoclick');

		$.post(kbToolURL, data, function(result) {
			var kbTopic = false;
			if (result['kbTopic']) {
				kbTopic = result['kbTopic'];
			}
			swapOutColumn(kbAid, kbTopic);
			setTimeout(function () {
				activeTransition = false;
				closeEditBox();
				activeTransition = true;
			}, 200);
			setTimeout(function () {
				// If there's no remaining active columns, remove knowledgebox entirely
				if ($('.kbcol').not('.kbdeadcol').length === 0) {
					$('.kbedit_section').remove();
					$('#knowledgebox')
						.slideUp('slow')
						.queue(function () {
							$(this).remove();
							$(this).dequeue();
						});
				}
				activeTransition = false;
				botwrapper.removeClass('kbnoclick');
			}, 800);

			// For our good old friend IE8:
			if ($.browser.msie) {
				setTimeout(function () {
					$('.kbdeadcol').css({ opacity: 0, visibility: 'hidden' });
				}, 1000);
			}
		}, 'json');

	};

	var submitContent = function() {
		addBtn = $('#kbadd');
		addBtn.hide();
		$('#kbcontent').attr('disabled', 'disabled');
		$('#kbwaiting').show();

		var newContent = $('#kbcontent').val();
		var kbAid = $('#kbaid').val();
		var kbTopic = $('#kbtopic').val();
		var kbPhrase = $('#kbheaderphrase').text();
		var kbEmail = $('#kbemail').val();
		var kbName = $('#kbname').val();
		
		// Remove leading and trailing spaces
		var reg = /^ +| +$/mg;
		var alteredContent = $.trim(newContent.replace(reg, ''));
		var trimmedEmail = $.trim(kbEmail.replace(reg, ''));
		var alteredName = $.trim(kbName.replace(reg, ''));

		// Ensure e-mail contains an '@' with characters on either side
		var alteredEmail = /.+@.+/.test(trimmedEmail) ? trimmedEmail : '';

		if (alteredContent == '') {
			alert('You have not entered any content.');

			$('#kbcontent').removeAttr('disabled');
			$('#kbwaiting').hide();
			addBtn.show();
		} else {
			updateIDSet();

			var data = {'aid': wgArticleId,
						'kbAid': kbAid,
						'kbTopic': kbTopic,
						'kbContent': alteredContent,
						'kbEmail': alteredEmail,
						'kbName': alteredName,
						'spentIDs': JSON.stringify(getSpentIDs())};

			var urlQuery =
				'?origin=' + encodeURIComponent(wgPageName)
				+ '&targetTopic=' + encodeURIComponent(kbTopic)
				+ '&targetPhrase=' + encodeURIComponent(kbPhrase);

			$.post(kbToolURL + urlQuery, data, function(result) {
				activeTransition = true;
				addBtn.show();
				$('#kbwaiting').hide();
				$('#kbcontent').removeAttr('disabled').val('');;
				unfocusTextbox();
				var kbTopic = false;
				if (result['kbTopic']) {
					kbTopic = result['kbTopic'];
				}
				swapOutColumn(kbAid, kbTopic);
				thanksFlyby();
				$('html,body').animate(
					{ scrollTop: $('#knowledgebox').offset().top - 64 },
					{ queue: false, duration: 500 }
				);
				setTimeout(function () {
					activeTransition = false;
					closeEditBox();
				}, 200);
				setTimeout(function () {
					// If there's no remaining active columns, remove knowledgebox entirely
					if ($('.kbcol').not('.kbdeadcol').length === 0) {
						$('.kbedit_section').remove();
						$('#knowledgebox')
							.slideUp('slow')
							.queue(function () {
								$(this).remove();
								$(this).dequeue();
							});
					}
				}, 800);

				// For our good old friend IE8:
				if ($.browser.msie) {
					setTimeout(function () {
						$('.kbdeadcol').css({ opacity: 0, visibility: 'hidden' });
					}, 1000);
				}
			}, 'json');

			// Prevent pop-up when leaving page
			window.onbeforeunload = function () {};
		}
	};

	$(document).on('click', '#kbadd', function(e) {
		submitContent();
		return false;
	});

	$('#kbform').submit(function (e) {
		submitContent();
		return false;
	});

	$('#kbcontentbox').focus(function() {
		focusTextBox();
	});

	$('#kbcontentbox').on({'touchstart': function() {
		focusTextBox();
	}});

	// Hacky workaround due to some browsers' bad support for pointer-events:none and focus/blur
	$('.kbedit_section').on('click', function (e) {
		var tgt = $(e.target);
		if (tgt.is('.kbtipstogglebtn') || tgt.is('#kbcontent')
				|| tgt.is('#kbadd') || tgt.is('#kbcontentbox')
				|| tgt.is('.kbtipsheader') || tgt.is('.kbtipsdetails')
				|| tgt.is('.kbformbot') || tgt.is('.kbformbot>*')
				|| tgt.is('.kbformbot>*>*')) {
			return;
		} else {
			unfocusTextbox();
		}
	});

	var showKBtips = true;
	var paddingTimeout;

	$('.kbtipstogglebtn').on('click', function (e) {
		if (showKBtips) {
			$('.kbtipsvbar').show();
			$('.kbtipsheader,.kbtipsdetails').slideUp(100);
			setTimeout(function () {
				$('.kbtipsbox')
					.animate(
						{ width: '77px' },
						{ queue: false, duration: 120 }
					);
			}, 80);
			paddingTimeout = setTimeout(function () {
				$('#kbcontent.active')
					.removeClass('kbpadmore').addClass('kbpadless');
			}, 200);
			showKBtips = false;
		} else {
			if (paddingTimeout !== undefined) {
				clearTimeout(paddingTimeout);
			}
			$('#kbcontent.active')
				.removeClass('kbpadless').addClass('kbpadmore');
			$('.kbtipsbox')
				.animate(
					{ width: '177px' },
					{ queue: false, duration: 120 }
				);
			setTimeout(function() {
				$('.kbtipsheader,.kbtipsdetails').slideDown(100);
				$('.kbtipsvbar').hide();
			}, 100);
			showKBtips = true;
		}
	});

	$(document).on('click', '.kbclose', function(e) {
		e.preventDefault();

		closeEditBox();

		return false;
	});

	$(document).on('click', '.kbbotleftwrapper', function(e) {
		e.preventDefault();

		if (activeTransition || editBoxExpanded) return;

		activeTransition = true;
		editBoxExpanded = true;

		var col = $(this).parents('.kbcol');
		var wrapper = $(this).parents('.kbbotwrapper');
		var kbid = col.attr('id');
		var kbtopic = col.find('.kbctopic').html();
		var kbphrase = col.find('.kbcphrase').html();
		var kbthumburl = col.find('.kbcthumb').attr('src');

		// Hack to hijack the box for RateTool
		//NOTE: RateTool is deprecated; if this comes back, gotta use a different url [sc]
		if (col.hasClass('kbhijack')) {
			window.location.href = '/Special:RateTool';
			return false;
		}
		// End hack, resume as normal

		$('#kbaid').val(kbid);
		$('#kbtopic').val(kbtopic);
		$('#kbheaderphrase').html(kbphrase);
		$('.kb-actual-image').css({ 'background-image': 'url('+kbthumburl+')' });

		wrapper.hide();

		if (kbid != currentAid) {
			$('#kbcontent').val('');
			unfocusTextbox();
		}

		currentAid = kbid;

		$('.kbedit_section').hide()
							.slideDown(400)
							.animate(
								{ opacity: 1 },
								{ queue: false, duration: 400 }
							);

		$('.kbcol').each(function () {
			if ($(this).hasClass('kbdeadcol')) {
				$(this).removeClass('kbopaque kbvisible')
					.addClass('kbtransparent kbhidden');
				return;
			}

			$(this).animate(
				{ height: 200*.80/.95 + 'px' },
				{ queue: false, duration: 200 }
			);
		});

		$('.kbbotwrapper').addClass('kbnoclick');

		col.find('.kbbot')
			.animate(
				{ height: '5%' },
				{ queue: false, duration: 200 }
			);

		col.find('.kbbotstripe').show();

		setTimeout(function () {
			col.find('.kbarrow').fadeIn('fast');
		}, 200);

		$('.kbcol').each(function () {
			var e = $(this);

			if (e.hasClass('kbdeadcol')) {
				$(this).removeClass('kbopaque kbvisible')
					.addClass('kbtransparent kbhidden');
				return;
			}

			if (e.attr('id') != kbid) {
				e.removeClass('kbhidden kbtransparent')
					.addClass('kbvisible kbopaque')
					.animate(
						{ opacity: 0 },
						{ queue: false, duration: 400 })
					.queue(function () {
						$(this).removeClass('kbvisible kbopaque')
							.addClass('kbhidden kbtransparent');
						$(this).dequeue();
					});
			}
		});

		setTimeout(function () {
			activeTransition = false;
		}, 500);

		return false;
	});

	$(document).on('click', '.kbbotrightwrapper', function(e) {
		e.preventDefault();

		if (activeTransition) {
			return false;
		}

		var col = $(this).parents('.kbcol');

		swapOutArticle(col);

		return false;
	});

})(jQuery);
