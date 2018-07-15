(function($, mw) {
	// Default values for object that holds all the pertinent article data
	var aData = {
		rc_id: 0,
		rc_cur_id: 0,
		title: "",
		rc_namespace: 0
	};

	var preloadedData = null;
	var preloaded = false;
	//var toolURL = mw.util.getUrl();
	var toolURL = '/Special:RCLite';

	var throttler = null;
	var MAX_NUM_ANON_CHECKED = 10;
	var signupinlink = '/Special:UserLogin?returnto=Special:RCLite';

	$(document).ready(function() {
		if (isOldBrowser()) {
			showErrorMessage(mw.message('rcl-error-old-browser').text(), "");
			return;
		}
		
		$('body').data('event_type', 'rc_patrol')
		throttler = new WH.AnonThrottle({
			toolName: 'rclite',
			maxEdits: MAX_NUM_ANON_CHECKED
		});

		initEventListeners();
		initData();
	});

	function isOldBrowser() {
		var isSlowDevice = false;

		var version = navigator.userAgent.match(/Android\s+([\d\.]+)/);
		if(version != null && parseFloat(version[1]) < 4.0) {
			isSlowDevice = true;
		}

		// First gen kindle
		if (navigator.userAgent.match(/AppleWebKit\/533.1/) != null) {
			isSlowDevice = true;
		}

		return isSlowDevice;
	}

	function initEventListeners() {
		$(document).on('click', '#rcl-yes', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('clickfail')) {
				doAction('patrol');
			}

		});

		$(document).on('click', '#rcl-unsure', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('clickfail')) {
				doAction('skip');
			}
		});

		$(document).on('click', '#rcl-no', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('clickfail')) {
				doAction('rollback');
			}
		});
	}


	/*
	 * Initial load of article data, including pre-fetching of a second
	 * article to give the illusion of faster transitioning between
	 * patrols
	 */
	function initData() {
		$.post(
			toolURL,
			{
				a: 'getNext',
				data: aData
			},
			function (result) {
				loadResult(result);
				$.post(toolURL,
					{
						a: 'getNext',
						data: aData
					}, function(result) {
						preloadedData = result;
						preloaded = true;
					},
					'json'
				)
					.fail(function() {
						result = {'error': mw.message('rcl-error-unknown').text()};
						loadResult(result);
					})
			},
			'json'
		)
			.fail(function() {
				result = {'error': mw.message('rcl-error-unknown').text()};
				loadResult(result);
			});
	}

	function doAction(action) {
		disableButtons();
		setLoadingMessage(action);
		var doActionInner = function(action) {
			$('#rcl-preview').hide();
			if (!preloaded) {
				// Wait half a second and then check if data
				// has been loaded
				setTimeout(function(){doAction(action)}, 500);
				return;
			}

			var data = aData;
			preloaded = false;
			aData = preloadedData;
			preloadedData = null;
			loadResult(aData);

			$.post(toolURL,
				{ data: data, a: action},
				function(result) {
					preloadedData = result;
					preloaded = true;
				},
				'json'
			)
				.fail(function() {
					preloadedData = {'error': mw.message('rcl-error-unknown').text()};
					preloaded = true;
				});
		};

		$('.rcl-waiting').fadeIn(function() {
			if (mw.user.isAnon()) {
				// Set a delay for anon users so they can
				// see the interstitial messages between actions
				setTimeout(function() {doActionInner(action)}, 500);
			} else {
				doActionInner(action);
			}
		});
	}

	function setLoadingMessage(action) {
		var heading = "";
		var sub = "";

		// Only anons see the cutesie messages
		if (mw.user.isAnon()) {
			switch (action) {
				case 'patrol':
					heading = mw.message('rcl-waiting-yes-heading').text();
					sub = mw.message('rcl-waiting-yes-sub').text();
					break;
				case 'rollback':
					heading = mw.message('rcl-waiting-no-heading').text();
					sub = mw.message('rcl-waiting-no-sub').text();
					break;
				case 'skip':
					heading = mw.message('rcl-waiting-maybe-heading').text();
					sub = mw.message('rcl-waiting-maybe-sub').text();
					break;
				case 'getNext':
					heading = mw.message('rcl-waiting-initial-heading').text();
					sub = mw.message('rcl-waiting-initial-sub').text();
					break;
			}
		}

		$('#rcl-waiting-heading').html(heading);
		$('#rcl-waiting-subheading').html(sub);
	}

	function getHtmlTitle() {

		var title = "";
		// Main namespace
		if (aData['rc_namespace'] == 0) {
			title = mw.message('howto', aData['title']).text().replace(/-/g, ' ');
		} else {
			title = mw.message('rcl-type-talk-title', aData['title']).text();
		}
		var html = mw.html.element(
			'span',
			{'class' : 'mt_prompt_article_title'},
			title
		);
		return html;
	}

	function updatePrompt() {
		var msg = 'rcl-type-' + aData['type'];
		if ($('.rcl_new_change').length > 1) {
			msg += '-plural';
		}
		$("#rcl-edit-type").html(mw.message(msg, getHtmlTitle()).text());
	}

	function showErrorMessage(error, pageTitle) {
		$("#rcl-toolbar").hide();
		if (typeof(WH) !== 'undefined' && typeof(WH.AndroidHelper) === 'undefined') {
			$("#rcl-content").css('margin-top', '40px');
		}
		$('#rcl-preview').addClass('section_text').html(error);
		$('.rcl-waiting').fadeOut();
		$("#rcl-preview").show();
		$('#rcl-header').hide();

	}

	function updateButtonText() {
		var yesButtonMsg = 'rcl-yes';
		var noButtonMsg = 'rcl-no';
		if ($('.rcl_new_change').length > 1) {
			yesButtonMsg += '-plural';
			noButtonMsg += '-plural';
		}
		$("#rcl-yes").html(mw.message(yesButtonMsg).text());
		$("#rcl-no").html(mw.message(noButtonMsg).text());
	}

	function updateArticle() {
		// strip the image src so that the browser doesn't try to load the images
		// when we manipulate via jquery. Don't do it for talk messages
		if (aData['type'] != 'talk') {
			aData['html'] = aData['html'].replace(/<img\b[^>]*>/ig, '');
		}
		$("#rcl-preview").html(aData['html']);
		updatePrompt();
		updateButtonText();
		swapImagesWithPlaceholder();
	}
	function loadResult(result) {
		aData = result;
		$('body').data('assoc_id', result.rc_id);
		$('body').data('article_id', result.rc_cur_id);
		
		if (throttler.limitReached()) {
			showAnonLimitReachedMsg();
			return;
		}
		else if (result['error'] != undefined) {
			showErrorMessage(result['error'], "");
			disableButtons();
		}
		else {
			updateArticle();
			adjustContentPadding();

			// Html no longer longer needed.
			// Remove so it doesn't get passed back to server
			// in the subsequent request
			aData['html'] = "";

			$('.rcl-waiting').fadeOut();
			$("#rcl-preview").show();

			// Don't trigger whvid init since we aren't displaying
			// images in rc lite
			//$(document).trigger('rcdataloaded');
			mw.mobileFrontend.emit('page-loaded');

			jumpToFirstEdit();
			enableButtons();
		}
		throttler.recordEdit();
	}

	function swapImagesWithPlaceholder() {
		// Remove any captions on images
		$('span.caption').remove();

		// Add the placeholder in places where mw images would be
		var placeholder = mw.html.element(
			'div',
			{class: 'rcl_image_placeholder'},
			mw.msg('rcl-image-placeholder-txt')
		);

		// Only put placeholder on floatcenter images. Don't do anything for
		// images that may be floating right as it messes up formatting
		$("#rcl-preview div.mwimg.floatcenter > a").replaceWith(placeholder);
	}

	function adjustContentPadding() {
		var offset = 0;
		if (typeof(WH) !== 'undefined' && typeof(WH.AndroidHelper) !== 'undefined') {
			offset = $('#rcl-header').height();
		} else {
			offset = $('.header').height() + $('#rcl-header').height();
		}


		$('#rcl-content').css('margin-top', offset + 'px');
	}

	function jumpToFirstEdit() {
		// User_talk namespace edits should always be at top
		// of display
		if (aData['rc_namespace'] == 0) {
			var firstChange = function(){
				var selector = '.rcl_new_change';
				var offset = $('.header').height() + $('#rcl-header').height() + 20;
				if ($('.rcl_old_change').length) {
					selector = '.rcl_old_change';
				}
				selector += ":first";
				$(document).scrollTop( $(selector).offset().top - offset);
			};

			// If article has images we have to delay the jump until
			// the last image is loaded
			if ($("div.mwimg:last img:first").length) {
				$("div.mwimg:last img:first").load(firstChange);
			} else {
				firstChange();
			}
		} else {
			// Scroll to top of html if there isn't a change marker
			// (ie a user_talk message).
			$(document).scrollTop(0);
		}
	}

	function disableButtons() {
		$('a[id^=rcl-]').addClass('clickfail');
	}

	function enableButtons() {
		$('a[id^=rcl-]').removeClass('clickfail');
	}

	// asks the backend for a new article
	//to edit and loads it in the page
	function getAnonLimitReachedMsg() {
		var href = signupinlink + '&type=signup';
		if (WH.isMobileDomain) {
			href = href + "&useformat=mobile&returntoquery=useformat%3Dmobile";
		}

		var signupLink = mw.html.element(
			'a',
			{
				href: href,
				title: mw.msg('rcl-signup'),
				class: "button primary"
			},
			mw.msg('rcl-signup')
		);
		return mw.msg('rcl-msg-anon-limit', signupLink);
	}

	function showAnonLimitReachedMsg() {
		disableButtons();
		$('#rcl-article-title').html('');
		$('#rcl-toolbar').hide();
		adjustContentPadding();
		$('.rcl-waiting').fadeOut();
		$("#rcl-preview").show();
		$('#rcl-preview').addClass('section_text').html(getAnonLimitReachedMsg());
		$('#rcl-header').hide();
	}
})(jQuery, mw);
