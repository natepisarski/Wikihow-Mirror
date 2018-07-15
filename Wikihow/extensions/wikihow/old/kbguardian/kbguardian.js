(function($, mw) {
	// Default values for object that holds all the pertinent article data
	var aData = {
		kbc_id: 0,
		title: ""
	};

	var preloadedData = null;
	var preloaded = false;
	// Add a question mark to work around chrome mobile browser bug
	var toolURL = '/Special:KBGuardian?';

	var numChecked = 0;
	var signupinlink = '/Special:UserLogin?returnto=Special:KBGuardian';

	var KBG_STANDINGS_TABLE_REFRESH = 600;

	$(document).ready(function() {
		if (isOldBrowser()) {
			showErrorMessage(mw.message('kbg-error-old-browser').text(), "");
			return;
		}

		if (WH.isMobileDomain) {
			WH.ArticleDisplayWidget.init();
		}
		initEventListeners();
		initToolTitle();
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

	function initToolTitle() {
		$(".firstHeading").before("<h5>" + $(".firstHeading").html() + "</h5>").html('');
	}

	function initEventListeners() {
		$(document).on('click', '#kbg-yes', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('clickfail')) {
				updateStats();
				doAction('vote_up');
			}

		});

		$(document).on('click', '#kbg-unsure', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('clickfail')) {
				doAction('not_sure');
			}
		});

		$(document).on('click', '#kbg-maybe', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('clickfail')) {
				doAction('maybe');
			}
		});

		$(document).on('click', '#kbg-no', function(e) {
			e.preventDefault();
			if (!$(this).hasClass('clickfail')) {
				updateStats();
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
						preloadedData = {'error': mw.message('kbg-error-unknown').text()};
						preloaded = true;
					})
			},
			'json'
		)
			.fail(function() {
				preloadedData = {'error': mw.message('kbg-error-unknown').text()};
				preloaded = true;
			});
	}

	/*
	* Empty out a few fields so we don't have to transmit as much data back to the server
	 */
	function cleanDataForPost(data) {
		data['kbc_content'] = '';
		return data;

	}
	function doAction(action) {
		disableButtons();
		if (WH.isMobileDomain) {
			WH.ArticleDisplayWidget.onBeginArticleChange();
		}

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
			loadResult(aData);

			data = cleanDataForPost(data);

			$.post(toolURL,
				{ data: data, a: action},
				function(result) {
					preloadedData = result;
					preloaded = true;
				},
				'json'
			)
				.fail(function() {
					preloadedData = {'error': mw.message('kbg-error-unknown').text()};
					preloaded = true;
				});
		};

		$('#kbg-preview').hide();
		if (WH.isMobileDomain) {
			$('#kbg-knowledge').hide();
		} else {
			$('#kbg-knowledge').html(mw.message('kbg-knowledge-loading').text());
		}

		$('.kbg-waiting').fadeIn(function() {
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
					heading = mw.message('kbg-waiting-yes-heading').text();
					sub = mw.message('kbg-waiting-yes-sub').text();
					break;
				case 'vote_down':
					heading = mw.message('kbg-waiting-no-heading').text();
					sub = mw.message('kbg-waiting-no-sub').text();
					break;
				case 'not_sure':
				case 'maybe':
					heading = mw.message('kbg-waiting-maybe-heading').text();
					sub = mw.message('kbg-waiting-maybe-sub').text();
					break;
				case 'getNext':
					heading = mw.message('kbg-waiting-initial-heading').text();
					sub = mw.message('kbg-waiting-initial-sub').text();
					break;
			}
		}

		$('#kbg-waiting-heading').html(heading);
		$('#kbg-waiting-subheading').html(sub);
	}

	function updateTitle() {
		var title = mw.message('howto', aData['title']).text().replace(/-/g, ' ');
		var html = mw.html.element(
			'a',
			{
				href: "/" + mw.util.rawurlencode(aData['title']),
				target: "_blank"
			},
			title
		);

		if (WH.isMobileDomain) {
			$("#kbg-article-title").html(title);
		} else {
			$("h1.firstHeading").html(html);
		}

	}

	function updateContent() {
		aData['kbc_content'] = aData['kbc_content'].replace(/\n/g, '<br>');
		$('#kbg-knowledge').html(aData['kbc_content']);
	}

	function showErrorMessage(error, pageTitle) {
		$("#kbg-article-title, h1.firstHeading, #kbg-toolbar").html("");
		$("#kbg-knowledge").hide();


		if (!WH.isMobileDomain) {
			$('#kbg-prompt, #kbg-sub-prompt').hide();
			$('#kbg-toolbar').hide();
		}
		$('#kbg-preview').addClass('section_text').addClass('kbg_error').html(error);
		$('.kbg-waiting').fadeOut();
		$("#kbg-preview").show();
	}

	function updateButtonText() {
		var yesButtonMsg = 'kbg-yes';
		var noButtonMsg = 'kbg-no';
		if ($('.rcl_new_change').length > 1) {
			yesButtonMsg += '-plural';
			noButtonMsg += '-plural';
		}
		$("#kbg-yes").html(mw.message(yesButtonMsg).text());
		$("#kbg-no").html(mw.message(noButtonMsg).text());
	}

	function updateArticle() {
		updateTitle();


		if (WH.isMobileDomain) {
			WH.ArticleDisplayWidget.updateArticleId(aData['kbc_aid']);
		} else {
			$("#kbg-preview").html(aData['html']);
		}

		updateContent();
		updateButtonText();
	}
	function loadResult(result) {
		aData = result;

		$('body').data({
			assoc_id: (result.kbc_id == -1) ? result.pqk_id : result.kbc_id,
			event_type: 'kb_guardian',
			article_id: result.kbc_aid,
			label: (result.kbc_id==-1) ? 'plant' : ''
		});

		if (result['error'] != undefined) {
			showErrorMessage(result['error'], "");
			disableButtons();
		}
		else {
			updateArticle();

			// Html no longer longer needed.
			// Remove so it doesn't get passed back to server
			// in the subsequent request
			aData['html'] = "";

			$('.kbg-waiting').fadeOut();
			$('#kbg-knowledge').show();
			$("#kbg-preview").show();


			$(document).trigger('rcdataloaded');
			if (WH.isMobileDomain) {
				mw.mobileFrontend.emit('page-loaded');
			}

			enableButtons();
		}
		numChecked++;
	}

	function disableButtons() {
		$('a[id^=kbg-]').addClass('clickfail');
	}

	function enableButtons() {
		$('a[id^=kbg-]').removeClass('clickfail');
	}

	function updateStats(){
		var statboxes = '#iia_stats_today_kbguardian_indiv_stats,#iia_stats_week_kbguardian_indiv_stats,#iia_stats_all_kbguardian_indiv_stats,#iia_stats_group';
		$(statboxes).each(function(index, elem) {
				$(this).fadeOut(function () {
					var cur = parseInt($(this).html());
					$(this).html(cur + 1);
					$(this).fadeIn();
				});
			}
		);
	}

	updateStandingsTable = function() {
		var url = '/Special:Standings/KBGuardianStandingsGroup';
		jQuery.get(url, function (data) {
				jQuery('#iia_standings_table').html(data['html']);
			},
			'json'
		);
		$("#stup").html(KBG_STANDINGS_TABLE_REFRESH / 60);
		//reset timer
		window.setTimeout(updateStandingsTable, 1000 * KBG_STANDINGS_TABLE_REFRESH);
	}

	updateWidgetTimer = function () {
		WH.updateTimer('stup');
		window.setTimeout(updateWidgetTimer, 60 * 1000);
	}

	if ($('.iia_stats').length) {
		window.setTimeout(updateWidgetTimer, 60 * 1000);
	}

	if ($('#iia_standings_table').length) {
		window.setTimeout(updateStandingsTable, 1000 * KBG_STANDINGS_TABLE_REFRESH);
	}
})(jQuery, mw);
