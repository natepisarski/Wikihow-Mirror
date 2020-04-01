(function ($) {
	'use strict';

	window.WH = WH || {};
	window.WH.Ouroboros = function () {};

	window.optimizely = window.optimizely || [];

	window.WH.Ouroboros.prototype = {
		/**
		 * The number of articles we've served up so far.
		 */
		articleCount: 0,

		articleRequestAlways: function (result) {
			this.hideSpinner();
		},

		articleRequestDone: function (result) {
			if (result.error) {
				return false;
			}

			this.blacklist.push(result.pageID);
			this.currentPageID = result.pageID;

			var ouroborosHtml = $(unescape(result.html));

			var titleHeader = $('<h1/>', {
				class: 'ouroboros-title',
				text: result.title
			});
			var titleDiv = $('<div/>').append(titleHeader);

			var articleDiv = $('<div/>', {
				class: 'ouroboros',
			});
			articleDiv.append(ouroborosHtml);

			articleDiv.find('#intro')
				.removeAttr('id')
				.removeClass('sticky')
				.addClass('ouroboros-intro')
				.prepend(titleDiv);

			articleDiv.find('.articleinfo').remove();

			articleDiv.find('.mwimg').addClass('noclick');

			$('#content').append(articleDiv);
			
			this.articleCount += 1;
			this.bindScrollHandlers();
		},

		articleRequestFail: function (result) {
			// Just twiddle your thumbs and pretend like nothing happened.
			this.debugPrint('server request failed');
			return false;
		},

		/**
		 * Blacklisted article names. Used to prevent serving up duplicates.
		 */
		blacklist: [wgArticleId],

		/**
		 * Bind an event listener to detect when user has scrolled far enough to
		 * load a new article.
		 */
		bindScrollHandlers: function () {
			if (this.articleCount > 0) {
				// We don't need this for the first article.
				this.debugPrint('binding scroll listener to first step of article #' + this.articleCount);
				$(window).bind('scroll', $.proxy(this.scrollHandlerFirstStep, this));
			}

			this.debugPrint('binding scroll listener to last step of article #' + this.articleCount);
			$(window).bind('scroll', $.proxy(this.scrollHandlerLastStep, this));
		},

		/**
		 * Creates an element to contain a loading spinner to display when
		 * articles are requested.
		 */
		createSpinner: function () {
			var spinner = $('<div/>', {
				class: 'ouroboros-spinner',
				style: 'display: none;'
			});

			var spinnerImg = $('<img/>', {
				src: '/extensions/wikihow/rotate.gif',
				alt: 'Fetching another article...',
				width: '64px',
				height: '64px'
			});

			spinner.append(spinnerImg);

			$('#footer').before(spinner);
		},

		currentPageID: wgArticleId,

		/**
		 * Print event info to console?
		 */
		debug: false,

		debugPrint: function (msg) {
			if (this.debug) {
				console.log('Ouroboros: ' + msg);
			}
		},

		/**
		 * Send a request to /Special:Ouroboros to fetch a new article.
		 */
		getNewArticle: function () {
			var action = 'gimme';

			var data = {
				action: action,
				blacklist: JSON.stringify(this.blacklist),
				currentPage: this.currentPageID
			};

			this.showSpinner();

			this.debugPrint('requesting new article');

			$.post(this.toolURL, data, function () { return; }, 'json')
				.done($.proxy(this.articleRequestDone, this))
				.fail($.proxy(this.articleRequestFail, this))
				.always($.proxy(this.articleRequestAlways, this));
		},

		hideSpinner: function () {
			$('.ouroboros-spinner').hide();
		},

		/**
		 * Set up the initial scroll trigger.
		 */
		initialize: function () {
			this.createSpinner();

			this.bindScrollHandlers();
		},

		/**
		 * Max number of articles to serve before stopping.
		 */
		maxArticles: 2,

		scrollHandlerFirstStep: function () {
			var stepElem = $('.ouroboros:last').find('.steps_list_2:first>li:first');

			if (stepElem.length == 0) {
				return false;
			}

			if ($(window).scrollTop() + $(window).height() > stepElem.offset().top) {
				var eventName = 'ouroborosFirstStep' + this.articleCount;
				this.debugPrint('pushing optimizely event ' + eventName);
				window.optimizely.push(['trackEvent', eventName]);

				this.unbindScrollHandlerFirstStep();
			}
		},

		/**
		 * Listen to scroll events until user passes the last step of the last
		 * article. When this happens, request a new article and destroy the scroll handler.
		 */
		scrollHandlerLastStep: function () {
			if ($(window).scrollTop() + $(window).height() > $('.steps_list_2:last>li:last').offset().top) {
				var eventName = 'ouroborosLastStep' + this.articleCount;
				this.debugPrint('pushing optimizely event ' + eventName);
				window.optimizely.push(['trackEvent', 'ouroborosLastStep' + this.articleCount]);
				
				if (this.articleCount < this.maxArticles) {
					this.getNewArticle();
				}

				this.unbindScrollHandlerLastStep();
			}
		},

		showSpinner: function () {
			$('.ouroboros-spinner').show();
		},

		toolAction: 'gimme',

		/**
		 * The Special Page through which AJAX calls are routed
		 */
		toolURL: '/Special:Ouroboros',

		/**
		 * Destroy the scroll handler
		 */
		unbindScrollHandlerFirstStep: function () {
			this.debugPrint('unbinding scroll listener from first step of article #' + this.articleCount);
			$(window).unbind('scroll', this.scrollHandlerFirstStep);
		},

		/**
		 * Destroy the scroll handler
		 */
		unbindScrollHandlerLastStep: function () {
			this.debugPrint('unbinding scroll listener from last step of article #' + this.articleCount);
			$(window).unbind('scroll', this.scrollHandlerLastStep);
		}
	};

	$(document).ready(function () {
		var ouroboros = new WH.Ouroboros();
		WH.ouroborosSingleton = new WH.Ouroboros();

		// To initialize, run WH.ouroborosSingleton.initialize();
	});
})(jQuery);

