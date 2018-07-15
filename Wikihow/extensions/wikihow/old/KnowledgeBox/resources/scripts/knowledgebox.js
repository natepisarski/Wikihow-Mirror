(function (mw, $) {
	"use strict";
	window.WH = WH || {};
	window.WH.knowledgebox = function () {};

	window.WH.knowledgebox.prototype = {
		kbToolURL: '/Special:KnowledgeBox',
		uagent: navigator.userAgent.toLowerCase(),

		isMobileDomain: window.location.hostname.match(/\bm\./) != null,
		mobileDevice: undefined,
		mobile: undefined,
		goodBrowser: undefined,

		activeTransition: false,
		editBoxExpanded: false,

		isMobileDevice: function () {
			if (typeof this.mobileDevice === 'undefined') {
				if (this.uagent.match(/iphone|ipad|android|silk|webos|ipod|blackberry|windows phone|opera mini|iemobile/i)) {
					this.mobileDevice = true;
				} else {
					this.mobileDevice = false;
				}
			}

			return this.mobileDevice;
		},

		isMobile: function () {
			if (typeof this.mobile === 'undefined') {
				this.mobile = this.isMobileDomain || this.isMobileDevice();
			}

			return this.mobile;
		},

		isGoodBrowser: function () {
			if (typeof this.goodBrowser === 'undefined') {
				this.goodBrowser =
					($.browser.msie && parseInt($.browser.version) >= 9)
					|| (($.browser.firefox || $.browser.mozilla) && parseInt($.browser.version) >= 5)
					|| ($.browser.chrome && parseInt($.browser.version) >= 19)
					|| ($.browser.opera && parseInt($.browser.version) >= 15)
					|| ($.browser.safari && parseInt($.browser.version) >= 6);
			}

			return this.goodBrowser;
		},

		/**
		 * MS are a bit sneaky with the uagent of newer IEs
		 */
		isNewIE: function () {
			return (this.uagent.indexOf('trident') != -1)
				? parseInt(this.uagent.match(/rv(?::|\s)?(\d+)/)[1])
				: false;
		},

		isEdge: function () {
			return (this.uagent.indexOf('edge/') != -1);
		},

		isWeirdMSBrowser: function () {
			return $.browser.msie
				|| this.isNewIE() && this.isNewIE() < 12
				|| this.isEdge();
		},

		currentID: 0,
		spentTopicIds: {}, // Mimic a set of unique values using obj

		closeSubmitSection: function () {
			if (this.activeTransition) return;

			this.activeTransition = true;

			$('.kb-submit-section')
				.slideUp(400)
				.animate(
					{ opacity: 0 },
					{ queue: false, duration: 400 }
				);

			$('.kb-arrow').remove();

			$('.kb-box').each(function () {
				var e = $(this);

				setTimeout(function () {
					if (e.hasClass('kb-dead-box')) {
						e.removeClass('kb-visible kb-opaque')
							.addClass('kb-hidden kb-transparent');
						e.find('.kb-bottom-wrapper')
							.addClass('kb-noclick');
						return false;
					}

					e.find('.kb-bottom-stripe').hide();
					e.find('.kb-bottom-wrapper').show();
					e.removeClass('kb-hidden kb-transparent')
						.addClass('kb-visible kb-opaque')
						.animate(
							{ opacity: 1 },
							{ queue: false, duration: 400 }
						);
					e.animate(
						{ height: '200px' },
						{ queue: false, duration: 200 }
					);
					e.find('.kb-top')
						.animate(
							{ height: '100%' },
							{ queue: false, duration: 200 }
						);
					e.find('.kb-bottom')
						.animate(
							{ height: '17%' },
							{ queue: false, duration: 200 }
						);
				}, 200);
			});

			var kb = this;

			setTimeout(function () {
				kb.activeTransition = false;
				kb.editBoxExpanded = false;
				$('.kb-bottom-wrapper').removeClass('kb-noclick');
			}, 500);
		},

		focusTextBox: function () {
			$('#kb-fake-content-box').hide();
			$('#kb-content').val('');
			$('.kb-content-box').show();
			$('#kb-content').focus();
		},

		unfocusTextBox: function () {
			if (this.isWeirdMSBrowser()) return;
			var newContent = $.trim($('#kb-content').val());
			if (newContent == '') {
				$('.kb-content-box').hide();
				$('#kb-fake-content-box').show();
			}
		},

		updateIDSet: function () {
			var kb = this;
			$('.kb-box').each(function () {
				kb.spentTopicIds[$(this).data('id')] = true;
			});
		},

		getSpentIDs: function () {
			var spentIDs = [];
			for (var p in this.spentTopicIds) {
				spentIDs.push(p);
			}
			return spentIDs;
		},

		thanksFlyby: function (extraDelay) {
			// TODO: Turn into mw message.
			var thanksText = 'Thanks!';
			var thanksElem = $("<div class='kb-thanks'>" + thanksText + "</div>");

			$('#knowledgebox').append(thanksElem);
			thanksElem.hide().show('fast');
			setTimeout(function () {
				if (typeof extraDelay === 'undefined' || !extraDelay) {
					extraDelay = 0;
				}
				var thanksAnimDuration = 600 + extraDelay;

				thanksElem.animate(
					{ top: '10%', opacity: 0 },
					{ queue: false, duration: thanksAnimDuration }
				);
				setTimeout(function () {
					thanksElem.remove();
				}, thanksAnimDuration);
			}, 2500);
		},

		swapOutBox: function (kbId, newTopic) {
			var box = $('#kb-box-' + kbId);
			box.addClass('kb-dead-box');

			box.animate(
				{ opacity: 0, height: '0' },
				{ queue: false, duration: 200 }
			);

			if (typeof newTopic !== 'undefined' && newTopic !== false) {
				box.removeClass('kb-dead-box');
				setTimeout(function () {
					box.attr('id', 'kb-box-' + newTopic['id']);
					box.data({
						id: newTopic['id'],
						aid: newTopic['aid'],
						topic: newTopic['topic'],
						phrase: newTopic['phrase'],
						thumbUrl: newTopic['thumbUrl'],
						thumbAlt: newTopic['thumbAlt']
					});
					box.find('.kb-top-topic').html(newTopic['topic'] + '?');
				}, 450);
			} else {
				box.removeClass('kb-opaque kb-visible')
					.addClass('kb-hidden kb-transparent');
			}
		},

		swapOutArticle: function (box) {
			this.activeTransition = true;

			var kbId = box.data('id');

			this.updateIDSet();

			var data = {
				'spentIDs': JSON.stringify(this.getSpentIDs())
			};

			var botwrapper = box.find('.kb-bottom-wrapper');
			botwrapper.addClass('kb-noclick');

			var kb = this;

			$.post(this.kbToolURL, data, function(result) {
				var kbTopic = false;
				if (result['kbTopic']) {
					kbTopic = result['kbTopic'];
				}

				kb.swapOutBox(kbId, kbTopic);
				setTimeout(function () {
					kb.activeTransition = false;
					kb.closeSubmitSection();
					kb.activeTransition = true;
				}, 200);

				setTimeout(function () {
					// If there's no remaining active columns, remove knowledgebox entirely
					if ($('.kb-box').not('.kb-dead-box').length === 0) {
						$('.kb-submit-section').remove();
						$('#knowledgebox')
							.slideUp('slow')
							.queue(function () {
								$(this).remove();
								$(this).dequeue();
							});
					}
					kb.activeTransition = false;
					botwrapper.removeClass('kb-noclick');
				}, 800);

				// For our good old friend IE8:
				if ($.browser.msie) {
					setTimeout(function () {
						$('.kb-dead-box').css({ opacity: 0, visibility: 'hidden' });
					}, 1000);
				}
			}, 'json');
		},

		submitContent: function () {
			var addBtn = $('#kb-add');
			addBtn.hide();
			$('#kb-content').attr('disabled', 'disabled');
			$('#kb-waiting').show();

			var newContent = $('#kb-content').val();
			var kbId = $('#kb-content-data').data('id');
			var kbAid = $('#kb-content-data').data('aid');
			var kbTopic = $('#kb-content-data').data('topic');
			var kbPhrase = $('#kb-content-data').data('phrase');
			var kbEmail = $('#kb-email').val();
			var kbName = $('#kb-name').val();

			// Remove leading and trailing spaces
			var reg = /^ +| +$/mg;
			var alteredContent = $.trim(newContent.replace(reg, ''));
			var trimmedEmail = $.trim(kbEmail.replace(reg, ''));
			var alteredName = $.trim(kbName.replace(reg, ''));

			// Ensure e-mail contains an '@' with characters on either side
			var alteredEmail = /.+@.+/.test(trimmedEmail) ? trimmedEmail : '';

			if (alteredContent == '') {
				alert('You have not entered any content.');
				
				$('#kb-content').removeAttr('disabled');
				$('#kb-waiting').hide();
				addBtn.show();
			} else {
				this.updateIDSet();

				var data = {
					aid: wgArticleId,
					kbId: kbId,
					kbAid: kbAid,
					kbTopic: kbTopic,
					kbContent: alteredContent,
					kbEmail: alteredEmail,
					kbName: alteredName,
					spentIDs: JSON.stringify(this.getSpentIDs())
				};

				var urlQuery =
					'?origin=' + encodeURIComponent(wgPageName)
					+ '&targetTopic=' + encodeURIComponent(kbTopic)
					+ '&targetPhrase=' + encodeURIComponent(kbPhrase);

				var kb = this;

				$.post(this.kbToolURL + urlQuery, data, function(result) {
					kb.activeTransition = true;
					addBtn.show();
					$('#kb-waiting').hide();
					$('#kb-content').removeAttr('disabled').val('');
					kb.unfocusTextBox();
					var kbTopic = false;
					if (result['kbTopic']) {
						kbTopic = result['kbTopic'];
					}
					kb.swapOutBox(kbId, kbTopic);
					for (var i = 0; i < 3; i++) {
						kb.thanksFlyby(i*70);
					}
					$('html,body').animate(
						{ scrollTop: $('#knowledgebox').offset().top - 64 },
						{ queue: false, duration: 500 }
					);
					setTimeout(function () {
						kb.activeTransition = false;
						kb.closeSubmitSection();
					}, 200);
					setTimeout(function () {
						// If there's no remaining active boxes, remove knowledgebox entirely
						if ($('.kb-box').not('.kb-dead-box').length === 0) {
							$('.kb-submit-section').remove();
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
		},
	};

	if (!(WH.knowledgebox.prototype.isGoodBrowser() || WH.knowledgebox.prototype.isNewIE())
			|| WH.knowledgebox.prototype.isMobile()) {
		// Disable for old browsers
		$('#knowledgebox').remove();
		$('#knowledgebox-submit').remove();
		return;
	} else if (!$('#knowledgebox').hasClass('kb-disabled')) {
		// Show for not-so-old browsers
		$('#knowledgebox').show();
	}

	if (WH.knowledgebox.prototype.isWeirdMSBrowser()) {
		// Simplify textbox and notched bar in all IEs from 11 and down.
		//$('.kb-notched-bar').addClass('kb-bot-bg'); // Unused
		//$('.kb-notch-l,.kb-notch-r').remove(); // Unused
		$('#kb-fake-content-box').remove();
		$('.kb-content-box').show();
	}

	if ($.browser.msie && parseInt($.browser.version) == 8) {
		// A few things are even more broken in IE8 -- simplify them
		$('.kb-close>div').remove();
		$('.kb-close').html('X').css('padding-top', '5px');
	}

	$(document).on('click', '#kb-add', function (e) {
		WH.knowledgebox.prototype.submitContent();
		return false;
	});

	$('#kb-submit-form').submit(function (e) {
		WH.knowledgebox.prototype.submitContent();
		return false;
	});

	$('#kb-fake-content-box').focus(WH.knowledgebox.prototype.focusTextBox);

	$('#kb-fake-content-box').on({'touchstart': WH.knowledgebox.prototype.focusTextBox});

	// Ugly, hacky workaround due to some browsers' bad support for pointer-events:none and focus/blur
	$('.kb-submit-section').on('click', function (e) {
		var tgt = $(e.target);
		if (tgt.is('.kb-tips-toggle-btn') || tgt.is('#kb-content')
				|| tgt.is('#kb-add') || tgt.is('#kb-fake-content-box')
				|| tgt.is('.kb-tips-header') || tgt.is('.kb-tips-details')
				|| tgt.is('.kb-form-bottom') || tgt.is('.kb-form-bottom>*')
				|| tgt.is('.kb-form-bottom>*>*')) {
			return;
		} else {
			WH.knowledgebox.prototype.unfocusTextBox();
		}
	});

	var showKBtips = true;
	var paddingTimeout;

	$('.kb-tips-toggle-btn').on('click', function (e) {
		if (showKBtips) {
			$('.kb-tips-vbar').show();
			$('.kb-tips-header,.kb-tips-details').slideUp(100);
			setTimeout(function () {
				$('.kb-tips-box')
					.animate(
						{ width: '77px' },
						{ queue: false, duration: 120 }
					);
			}, 80);
			paddingTimeout = setTimeout(function () {
				$('#kb-content.active')
					.removeClass('kb-pad-more')
					.addClass('kb-pad-less')
			}, 200);
			showKBtips = false;
		} else {
			if (typeof paddingTimeout !== undefined) {
				clearTimeout(paddingTimeout);
			}
			$('#kb-content.active')
				.removeClass('kb-pad-less')
				.addClass('kb-pad-more');
			$('.kb-tips-box')
				.animate(
					{ width: '177px' },
					{ queue: false, duration: 120 }
				);
			setTimeout(function () {
				$('.kb-tips-header,.kb-tips-details').slideDown(100);
				$('.kb-tips-vbar').hide();
			}, 100);
			showKBtips = true;
		}
	});

	$(document).on('click', '.kb-close', function(e) {
		e.preventDefault();

		WH.knowledgebox.prototype.closeSubmitSection();

		return false;
	});

	// User clicks 'Yes'. Expand and populate the submit section.
	$(document).on('click', '.kb-bottom-left-wrapper', function (e) {
		e.preventDefault();

		if (WH.knowledgebox.prototype.activeTransition || WH.knowledgebox.prototype.editBoxExpanded) return;

		WH.knowledgebox.prototype.activeTransition = true;
		WH.knowledgebox.prototype.editBoxExpanded = true;

		var box = $(this).parents('.kb-box');
		var wrapper = $(this).parents('.kb-bottom-wrapper');
		var kbId = box.data('id');
		var kbAid = box.data('aid');
		var kbTopic = box.data('topic');
		var kbPhrase = box.data('phrase');
		var kbThumburl = box.data('thumburl');
		var kbThumbalt = box.data('thumbalt');

		$('#kb-content-data').data({
			id: kbId,
			aid: kbAid,
			topic: kbTopic
		});

		$('.kb-submit-header-prompt-phrase').text(kbPhrase);

		$('.kb-real-image').css({ 'background-image': 'url('+kbThumburl+')' });
		$('.kb-submit-image-inner').attr('title', kbThumbalt);

		wrapper.hide();

		if (kbId != WH.knowledgebox.prototype.currentID) {
			$('#kb-content').val('');
			WH.knowledgebox.prototype.unfocusTextBox();
		}

		WH.knowledgebox.prototype.currentID = kbId;

		$('.kb-submit-section')
			.hide()
			.slideDown(400)
			.animate(
				{ opacity: 1 },
				{ queue: false, duration: 400 }
			);

		// Why are MS browsers so damn weird?
		if (WH.knowledgebox.prototype.isWeirdMSBrowser()) {
			$('.kb-content-box').show();
		}

		$('.kb-box').each(function () {
			if ($(this).hasClass('kb-dead-box')) {
				$(this)
					.removeClass('kb-opaque kb-visible')
					.addClass('kb-transparent kb-hidden');
				return;
			}

			$(this).animate(
				{ height: 200*.80/.95 + 'px' },
				{ queue: false, duration: 200 }
			);
		});

		$('.kb-bottom-wrapper').addClass('kb-noclick');

		box.find('.kb-bottom')
			.animate(
				{ height: '5%' },
				{ queue: false, duration: 200 }
			);

		box.find('.kb-bottom-stripe').show();

		setTimeout(function () {
			var arrow = $('<div class="kb-arrow"></div>');
			box.find('.kb-box-inner').append(arrow);
			arrow.fadeIn('fast');
		}, 200);

		$('.kb-box').each(function () {
			var e = $(this);

			if (e.hasClass('kb-dead-box')) {
				$(this)
					.removeClass('kb-opaque kb-visible')
					.addClass('kb-transparent kb-hidden')
				return;
			}

			if (e.data('id') != kbId) {
				e.removeClass('kb-hidden kb-visible')
					.addClass('kb-visible kb-opaque')
					.animate(
						{ opacity: 0 },
						{ queue: false, duration: 400 })
					.queue(function () {
						$(this)
							.removeClass('kb-visible kb-opaque')
							.addClass('kb-hidden kb-transparent');
						$(this).dequeue();
					});
			}
		});

		setTimeout(function () {
			WH.knowledgebox.prototype.activeTransition = false;
		}, 500);

		return false;
	});

	// User clicks 'No'. Swap out the topic with a new one.
	$(document).on('click', '.kb-bottom-right-wrapper', function (e) {
		e.preventDefault();

		if (WH.knowledgebox.prototype.activeTransition) {
			return false;
		}

		WH.knowledgebox.prototype.activeTransition = true;

		var box = $(this).parents('.kb-box');

		WH.knowledgebox.prototype.swapOutArticle(box);

		return false;
	});
}(mediaWiki, jQuery));

