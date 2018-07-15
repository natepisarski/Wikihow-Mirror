(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.KbApp = {

		template: Handlebars.compile($('#kbc-index').html()),
		errorTemplate: Handlebars.compile($('#kbc-errors').html()),
		article: {},
		articleUrl: '',

		initialize: function (articleUrl) {
			this.articleUrl = articleUrl;
			this.$subContainer = $('#submission-container');
			this.$queContainer = $('#que-container');

			if (!_.isEmpty(articleUrl)) {
				this.findArticle();
			}

			$('body').on('click', '.pane-toggle', $.proxy(this, 'toggleSub'));
			$('body').on('click', '.sub-content a.hint', $.proxy(this, 'toggle'));
		},

		toggleSub: function (event) {
			event.stopPropagation();
			event.preventDefault();
			var numMin = 0,
				$btn = $(event.currentTarget),
				$target;

			$btn.toggleClass('active');
			$('.app-container').removeClass('fullScreen');

			$.each($('.pane-toggle'), function () {
				$target = $("#" + $(this).data('target'));
				$target = ($(this).hasClass('active')) ? $target.removeClass('minimized') : $target.addClass('minimized');
			});

			numMin = $('.app-container.minimized').length;
			// cant have them both gone...
			if (numMin === 2) {
				$btn.click();
			} else if (numMin === 1) {
				$('.app-container').addClass('fullScreen');
			}
		},

		toggle: function (event) {
			event.stopPropagation();
			event.preventDefault();
			$(event.currentTarget).parent().parent().find('.sub-content ').toggle();
		},

		findArticle: function () {
			$.ajax({
				dataType: 'json',
				data: {
					article: this.articleUrl
				},
				url: '/Special:KnowledgeBoxFilter/kb/findArticle'
			}).done($.proxy(this, 'startup'));
		},

		startup: function (data) {
			if (!_.isEmpty(data.errors)) {
				this.showErrors(data);
				return;
			}

			this.article = data.article;
			WH.KbSubmissions.initialize(this.$subContainer);
			WH.KbQue.initialize(this.$queContainer);
		},

		showErrors: function (data) {
			this.$subContainer.html(this.errorTemplate(data));
		},

		transOut: function ($sub, remove) {
			remove = (remove === undefined) ? false : remove;
			var className = remove ? 'fadeOutRightBig' : 'fadeOutLeftBig';
			$sub.removeClass('fadeInLeftBig fadeInRightBig').addClass(className)
				.delay(200)
				.slideUp(200, function () {
					if (remove) {
						$sub.remove();
					}
				});
		}
	};

	Handlebars.registerHelper('truncate', function (str, len) {
		if (str.length > len && str.length > 0) {
			var new_str = str + " ";
			new_str = str.substr(0, len);
			new_str = str.substr(0, new_str.lastIndexOf(" "));
			new_str = (new_str.length > 0) ? new_str : str.substr(0, len);

			return new Handlebars.SafeString(new_str + '...');
		}
		return str;
	});

	Handlebars.registerHelper('round', function (num) {
		return parseInt(num, null);
	});

	Handlebars.registerHelper('isZero', function (str) {
		return Handlebars.helpers.round(str) === 0;
	});

	Handlebars.registerHelper('isEmpty', function (str) {
		return str.replace(" ") === "";
	});

	Handlebars.registerHelper('blankForZero', function (str) {
		var val = Handlebars.helpers.round(str);
		return val === 0 ? '' : val;
	});

	Handlebars.registerHelper('toggleClass', function (str, len) {
		return (str.length > len) ? 'toggle' : '';
	});
}());

