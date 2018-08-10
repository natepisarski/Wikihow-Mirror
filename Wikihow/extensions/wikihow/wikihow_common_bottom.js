(function(mw, $) {
'use strict';

// ratings section
WH.ratings = {};

// DEPRECATED
// TODO: Remove this function
WH.ratings.ratingReason = function(reason, itemId, type, rating, name, email, detail, ratingId) {
	if (!reason && !detail) {
		return;
	}

	var postData = {
		'item_id': itemId,
		'type': type,
		'ratingId': ratingId,
		'rating': rating
	};
	var requestUrl = '/Special:RatingReason';
	if (detail && typeof detail != 'undefined') {
		postData.detail = detail;
	}
	if (reason && typeof reason != 'undefined') {
		postData.reason = reason;
	}
	if (name && typeof name != 'undefined') {
		postData.name = name;
	}
	if (email && typeof email != 'undefined') {
		postData.email = email;
	}
	if (type === 'article' && mw.config.get('wgArticleId')) {
		postData.page_id = mw.config.get('wgArticleId');
	}
	$.ajax({
		type: 'POST',
		url: requestUrl,
		data: postData
	}).done(function(data) {
		data = '<div class="' + type + '_rating_result">' + data + '</div>';
		$('#' + type + '_rating').html(data);
	});
};

WH.ratings.customizeForm = function () {
	if (typeof(WH.gdpr) != 'undefined' && !WH.gdpr.isEULocation()) {
		$('#ar_public_radio_yes').prop('checked',true);
	}

	$('#article_rating_header').hide();
};

WH.ratings.gRated = false;
WH.ratings.rateItem = function(r, itemId, type, source) {
	if (!WH.ratings.gRated) {
		if (window.mw) {
			mw.loader.using(
				['ext.wikihow.ratingreason.mh_style', 'ext.wikihow.ratingreason.mh_style.styles'],
				function() {
					WH.ratings.bindInputFields(WH.ratings.parentElem);
				}
			);
		}

		var postData = {
			'action': 'rate_page',
			'page_id': itemId,
			'rating': r,
			'type': type,
			'source': source
		};

		var ratingsData = '';
		var sample_rating = type == 'sample';
		var discuss_tab = WH.DiscussTab && source == 'discuss_tab';

		// [sc] turning off the method helpfulness form
		// var displayMethodHelpfulnessBottomForm = 	r == 1 &&
		// 																					WH.displayMethodHelpfulness &&
		// 																					!sample_rating &&
		// 																					!discuss_tab;
		var displayMethodHelpfulnessBottomForm = false;

		$.ajax({
			type: 'POST',
			url: '/Special:RateItem',
			data: postData
		}).done(function(data) {
			ratingsData = '<div class="article_rating_result">' + data + '</div>';

			if (WH.RatingSidebar) {
				if (source == 'sidebar') {
					WH.RatingSidebar.showResult(r);
					$('#article_rating').slideUp();
				}
				else if (source == 'desktop' || discuss_tab) {
					WH.RatingSidebar.disappear();
				}
			}

			var scrollDown = 	source != 'sidebar' &&
												!discuss_tab &&
												!sample_rating &&
												!displayMethodHelpfulnessBottomForm;

			if (scrollDown) {
				setTimeout(function () {
					$('#article_rating').html(ratingsData);
					$('#article_rating').css('max-width', 'none');
					$('body').scrollTo('#article_rating');
					WH.ratings.customizeForm();
				}, 1);
			}

			if (sample_rating) $('#sample_rating').html(ratingsData);

			if (discuss_tab) {
				$('#article_rating_modal').html(ratingsData);
				$('#article_rating').slideUp();
			}
		});

		if (displayMethodHelpfulnessBottomForm && source != 'sidebar') {
			// User clicked 'Yes' and the article has methods
			var helpfulnessData = {
				'action': 'cta',
				'type': 'bottom_form',
				'aid': mw.config.get('wgArticleId'),
				'methods': JSON.stringify(WH.methods),
				'platform': source
			};

			$.ajax({
				type: 'POST',
				url: '/Special:MethodHelpfulness',
				data: helpfulnessData,
				dataType: 'json'
			}).done(function(result) {
				var mh = $('<div>', {
					'class': 'article_rating_result'
				});

				if (window.mw) {
					mw.loader.using([result.resourceModule], function () {
						mh.html(result.cta);
						// A 1 ms delay seems to prevent flickering on some devices
						setTimeout(function () {
							$('#article_rating').html(mh);
							$('body').scrollTo('#article_rating');
						}, 1);
					});
				}
			}).fail(function (/*result*/) {
				$('#article_rating').html(ratingsData);
				$('#article_rating').css('max-width', 'none');
				$('body').scrollTo('#article_rating');
			});
		}
	}
	WH.ratings.gRated = true;
};

WH.displayMethodHelpfulness = false;
WH.methods = [];

WH.checkMethods = function() {
	var doMethodCheck = mw.config.get('wgContentLanguage') == 'en';

	if (doMethodCheck) {
		// Get method names
		var methodSelector = '#method-title-info>.mti-title';

		$(methodSelector).each(function () {
			WH.methods.push($(this).data('title'));
		});

		WH.displayMethodHelpfulness = WH.methods.length > 1;
	}
};

// update any external links on project pages from www.wikihow to m.wikihow
// if we are on mobile domain. this is done because some intl pages now have
// links to english wikihow about pages and cookie policy pages
// and this is simpler to do than to change fastlys rules for redirecting to mobile
WH.updateProjectLinks = function() {
	if (wgNamespaceNumber != 4) {
		return;
	}

	if (!window.WH.isMobile) {
		return;
	}

	$('.extiw').each(function() {
		var href = $(this).attr('href');
		if (href && href.indexOf('www.wikihow.com/wikiHow:') >= 0) {
			var newHref = href.replace('www.wikihow.com', 'm.wikihow.com');
			$(this).attr('href', newHref);
		}
	});
};

WH.injectMethodThumbs = function() {
	var tmplElem = $('.mh-method-thumbs-template');

	if (!tmplElem.length) {
		return;
	}

	if (!WH.displayMethodHelpfulness || !WH.methodThumbsCTAActive) {
		tmplElem.remove();
		return;
	}

	var platform = WH.isMobileDomain ? 'mobile' : 'desktop';

	// TODO: Remove this when/if we want a desktop CTA as well
	if (platform !== 'mobile') {
		tmplElem.remove();
		return;
	}

	var params = {
		questionText: mw.message('mhmt-question').plain()
	};

	var helpedText = mw.message('mhmt-helped').plain();
	var thanksText = mw.message('mhmt-thanks').plain();
	var cheerList = mw.message('mhmt-cheer').plain().split("\n");
	var oopsList = mw.message('mhmt-oops').plain().split("\n");

	$('.section.steps:not(:has(h2>span#Steps))').each(function (index) {
		params.methodIndex = index;
		params.currentMethod = WH.methods[index];
		params.cheerText = cheerList[Math.floor(Math.random()*cheerList.length)] + ' ' + helpedText;
		params.oopsText = oopsList[Math.floor(Math.random()*oopsList.length)] + ' ' + thanksText;
		var cta = Mustache.render(unescape(tmplElem.html()), params);
		$(this).find('.steps_list_2:last').append(cta);
	});

	tmplElem.remove();
};

/**
 * Late loading of printable styling for @media 'print' to prevent breaking
 * the site on IE9.
 */
WH.loadPrintModule = function () {
	if (!WH.isMobileDomain &&
		!($.browser.msie && $.browser.version < 10)
	) {
		mw.loader.using(['ext.wikihow.printable']);
		WH.bindPrintEvents();
	}
};

window.WH.beforePrintEventCount = 0;

WH.bindPrintEvents = function () {
	if (window.location.href.indexOf('printable=yes') > 0) {
		WH.maEvent('print_article', { category: 'print_article' }, false);
	}

	window.onbeforeprint = WH.beforePrint;

	var mediaQueryList = window.matchMedia('print');
	mediaQueryList.addListener(function (mql) {
		if (mql.matches) {
			WH.beforePrint();
		}
		// Add an else here if you want to handle after-print
	});
};

WH.beforePrint = function () {
	window.WH.beforePrintEventCount += 1;

	// if (window.WH.beforePrintEventCount == 1 && mw.config.get('wgArticleId') > 0) {
		// // Track in machinify
		// WH.maEvent('print_event', { category: 'print_article' }, false);
	// }

	return false;
};

$(document).ready(function() {
	if ($('.mwimg-caption-fade').length < 1) {
		return;
	}

	$(window).on('scroll', function fadeScrollHandler() {
		var fadedIn = false;

		$('.mwimg-caption-fade').each( function() {
			if ($(window).scrollTop() + 300 >= $(this).parent().offset().top) {
				var fadeTime = $(this).data('fadetime');
				$(this).fadeTo(fadeTime, 1);
				$(this).removeClass('mwimg-caption-fade');
				fadedIn = true;
			}
		});

		// if there are no more mwimg-caption-fade classes, unbind the handler
		if ($('.mwimg-caption-fade').length < 1) {
			$(window).off('scroll', fadeScrollHandler);
		}
	});
});

$(document).ready(function() {
	$('.s-help-wrap').on('click', function(event) {
		if ($(event.target).hasClass('firststeplink')) {
			$('html, body').animate({
				scrollTop: $(".section.steps:first").offset().top - 50
			}, 750);
			return false;
		}
	});

	// helpfulness text form feedback
	$('.s-help-feedback-wrap input').on('click', function(event) {
		var ratingId = $(this).data("rating-id");
		var text = $('.m-video-helpful-wrap textarea').val();

		// only submit if we have both of these
		if (!(ratingId && text)) {
			return;
		}
		var postData = {
			'type': 'itemratingreason',
			'ratingId': ratingId,
			'text': text
		};
		var requestUrl = '/Special:RateItem';
		var wrap = $(this).parent().parent();
		var finishPrompt = $(this).data('finish-prompt');
		$.post( requestUrl, postData, function(data) {
			wrap.find('.s-help-prompt').html(finishPrompt);
			wrap.find('.s-help-feedback-wrap').hide();
			wrap.animate( {
				height: "70px"
			}, 800);
		});
	});

	$('.m-video-helpful-wrap button, .s-help-wrap button').on('click', function(event) {
		var yes = $(this).data("value");
		var type = $(this).data('type');
		var promptText = $(this).data('prompt-text');
		var finishPromptText = $(this).data('finish-prompt');
		var textareaPrompt = $(this).data('textarea-prompt');
		var textFeedback = $(this).data('text-feedback');
		var postData = {
			'pageId': wgArticleId,
			'type': type,
			'rating': yes
		};
		var wrap = $(this).parent();
		var requestUrl = '/Special:RateItem';
		$.post( requestUrl, postData, function(data) {
			// hide the buttons
			wrap.find('button').hide();

			// set the feedback for text
			wrap.find('.s-help-textarea').attr('placeholder', textareaPrompt);

			// show the feedback form wrapper only if text feedback is active
			if (textFeedback) {
				$(wrap).find('.s-help-feedback-wrap').show();
				// pass forward data to the feedback form for when it is submitted
				$('.s-help-feedback-wrap input').data('rating-id', parseInt(data));
				$('.s-help-feedback-wrap input').data('finish-prompt', finishPromptText);
				$('.s-help-prompt').text(promptText);
			} else {
				$('.s-help-prompt').html(finishPromptText);
			}

		});
	});

	$('.aritem').on('click', function() {
		var type = 'desktop';
		if (WH.isMobileDomain) {
			type = 'mobile';
		}

		var rating = 0;
		if ($(this).attr('id') == 'gatAccuracyYes') {
			rating = 1;
		}

		var pageId = $(this).attr('pageid');
		WH.ratings.rateItem(rating, pageId, 'article_mh_style', type);
	});

	$('#article_rating').on('change', '.ar_public', function() {
		if ($(this).val() == 'yes') {
			$('.ar_public_info').show();
		} else {
			$('.ar_public_info').hide();
		}
	});

//	if (!mw.user.isAnon()) {
//		if ($('#servedtime').length) {
//			var time = parseInt($('#servedtime').html());
//			WH.ga.sendEvent('servedtime', 'appserver', 'milliseconds', time, 1);
//			ga('send', 'timing', 'appserver', 'servedtime', time);
//		}
//	}

	WH.checkMethods();

	if (WH.methodThumbsCTAActive === undefined) {
		// Set to false to disable Method Helpfulness per-method CTA
		// Alternatively, this variable can be set elsewhere with more complex
		// logic if necessary.
		WH.methodThumbsCTAActive = true;
	}

	if (WH.displayMethodHelpfulness) {
		WH.injectMethodThumbs();
	}

	WH.loadPrintModule();

	WH.updateProjectLinks();

	$('#gatFollowNewsletter').click(function() {
		WH.maEvent('newsletter_signup_rightrail', { category: 'newsletter_signup' }, false);
	});
});

// strips step text of extra the text from script tags
function stripScripts(s) {
	var div = $('<div>');
	div.innerHTML = s;
	var scripts = div.find('script');
	var i = scripts.length;

	while (i-- > 0) {
	  scripts[i].parentNode.removeChild(scripts[i]);
	}

	var noscripts = div.find('noscript');
	i = noscripts.length;

	while (i-- > 0) {
	  noscripts[i].parentNode.removeChild(noscripts[i]);
	}

	var rptimg = div.find('rpt_img');
	i = rptimg.length;
	while (i-- > 0) {
		rptimg[i].parentNode.removeChild(rptimg[i]);
	}

	return div.innerHTML;
}

// switch class for given element
function switchClass(elem, currentClass, replacementClass) {
	$(elem).removeClass(currentClass).addClass(replacementClass);
}

/**
 * Display a notice if we detect an ad blocker. Adapted from:
 * https://www.christianheilmann.com/2015/12/25/detecting-adblock-without-an-extra-http-overhead/
 * https://marthijnhoiting.com/detect-if-someone-is-blocking-google-analytics-or-google-tag-manager/
 * http://www.detectadblock.com/
 */
WH.loadAdblockNotice = function() {
	// Check to see if adblock notice is loaded in html.
	// If it isn't, it's not a target page
	if (!$('#ab_notice').length) return;

	// Only for anons
	if (mw.user.getId() === 0) {
		var test = document.createElement('div');
		test.innerHTML = '&nbsp;';
		test.className = 'adsbox';
		document.body.appendChild(test);
		window.setTimeout(function() {
			// testing offsetHeight as a proxy for adBlock
			// testing of existing of element id for safari content blocking (and others)
			// testing existence of ga as a proxy for detecting ghostery and ublock
			if (test.offsetHeight === 0 || !document.getElementById('vDxPZmfJyISu') || !(window.ga && ga.create)) {
				$('#ab_notice').show();
				$('.ad_label_method').hide();
			}
			test.remove();
		}, 100);
	}
};

WH.sendToOpti = function(type, param, paramValue) {
	window['optimizely'] = window['optimizely'] || [];
	var info = {};
	info['type'] = type;
	info[param] = paramValue;
	window['optimizely'].push(info);
};

function onLoadWikihowCommonBottom() {
	WH.loadAdblockNotice();
}

}(mediaWiki, jQuery));
