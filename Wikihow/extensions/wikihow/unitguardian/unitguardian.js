(function($, mw) {
	// Default values for object that holds all the pertinent article data
	var aData = {
		ugc_id: 0,
		title: ""
	};

	var preloadedData = null;
	var preloaded = false;
	// Add a question mark to work around chrome mobile browser bug
	var toolURL = '/Special:UnitGuardian?';

	var numChecked = 0;

	$(document).ready(function() {
		if (isOldBrowser()) {
			showErrorMessage(mw.message('ug-error-old-browser').text(), "");
			return;
		}

		initEventListeners();
		initToolTitle();
		initData();
		WH.ArticleDisplayWidget.init();
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

	function initToolTitle() {
		$(".firstHeading").before("<h5>" + $(".firstHeading").html() + "</h5>").html('');
	}

	function initEventListeners() {
		$(document).on('click', '#ug-yes', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('clickfail')) {
				doAction('vote_up');
			}

		});

		$(document).on('click', '#ug-maybe', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('clickfail')) {
				doAction('maybe');
			}
		});

		$(document).on('click', '#ug-no', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('clickfail')) {
				doAction('vote_down');
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
				action: 'getNext',
				data: aData
			},
			function (result) {
				loadResult(result);
				$.post(toolURL,
					{
						action: 'getNext',
						data: aData
					}, function(result) {
						preloadedData = result;
						preloaded = true;
					},
					'json'
				)
					.fail(function() {
						preloadedData = {'error': mw.message('ug-error-unknown').text()};
						preloaded = true;
					})
			},
			'json'
		)
		.fail(function() {
			preloadedData = {'error': mw.message('ug-error-unknown').text()};
			preloaded = true;
		});
	}

	/*
	 * Empty out a few fields so we don't have to transmit as much data back to the server
	 */
	function cleanDataForPost(data) {
		data['ugc_html'] = '';
		return data;

	}

	function doAction(action) {
		disableButtons();
		setLoadingMessage(action);
		var doActionInner = function(action) {

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
			WH.ArticleDisplayWidget.onBeginArticleChange();
			loadResult(aData);

			data = cleanDataForPost(data);

			$.post(toolURL,
				{ data: data, action: action},
				function(result) {
					preloadedData = result;
					preloaded = true;
				},
				'json'
			)
				.fail(function() {
					preloadedData = {'error': mw.message('ug-error-unknown').text()};
					preloaded = true;
				});
		};

		$('#ug-preview').hide();

		$('.ug-waiting').fadeIn(function() {
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
				case 'vote_up':
					heading = mw.message('ug-waiting-yes-heading').text();
					sub = mw.message('ug-waiting-yes-sub').text();
					break;
				case 'vote_down':
					heading = mw.message('ug-waiting-no-heading').text();
					sub = mw.message('ug-waiting-no-sub').text();
					break;
				case 'not_sure':
				case 'maybe':
					heading = mw.message('ug-waiting-maybe-heading').text();
					sub = mw.message('ug-waiting-maybe-sub').text();
					break;
				case 'getNext':
					heading = mw.message('ug-waiting-initial-heading').text();
					sub = mw.message('ug-waiting-initial-sub').text();
					break;
			}
		}

		$('#ug-waiting-heading').html(heading);
		$('#ug-waiting-subheading').html(sub);
	}

	function updateTitle() {
		$("#ug-article-title").html(mw.message('howto', aData['title']).text());
	}

	function updateContent() {
		converted = aData['ugc_converted'].replace("(", "<span class='highlight'>(");
		converted = converted.replace(")", ")</span>");
		html = aData['content'].replace(aData['ugc_original'], converted);
		$('#ug-convert').html(html);
	}

	function showErrorMessage(error, pageTitle) {
		$('.mt_prompt_box').hide();
		$('#ug-toolbar').hide();
		$('#adw_toolbar').hide();
		$('#ug-convert').html(error);
		$('.ug-waiting').fadeOut();
	}

	function updateButtonText() {
		var yesButtonMsg = 'ug-yes';
		var noButtonMsg = 'ug-no';
		if ($('.rcl_new_change').length > 1) {
			yesButtonMsg += '-plural';
			noButtonMsg += '-plural';
		}
		$("#ug-yes").html(mw.message(yesButtonMsg).text());
		$("#ug-no").html(mw.message(noButtonMsg).text());
	}

	function updateArticle() {
		updateTitle();

		WH.ArticleDisplayWidget.updateArticleId(aData['aid'], aData['html']);
		updateContent();
		updateButtonText();
	}
	function loadResult(result) {
		aData = result;

		$('body').data({
			assoc_id: (result.ugc_id == -1) ? result.pqu_id : result.ugc_id,
			event_type: 'unit_guardian',
			article_id: result.ugc_page,
			label: (result.ugc_id==-1) ? 'plant' : ''
		});

		if (result['error'] != undefined) {
			showErrorMessage(result['error'], "");
			disableButtons();
		}
		else {
			updateArticle();

			$('.ug-waiting').fadeOut();
			$("#ug-preview").show();

			enableButtons();
		}
		numChecked++;
	}

	function disableButtons() {
		$('a[id^=ug-]').addClass('clickfail');
	}

	function enableButtons() {
		$('a[id^=ug-]').removeClass('clickfail');
	}

})(jQuery, mw);
