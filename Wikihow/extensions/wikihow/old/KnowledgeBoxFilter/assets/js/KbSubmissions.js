(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.KbSubmissions = {
		variable: "",
		$container: {},
		data: {},

		initialize: function ($container) {
			this.$container = $container;
			this.listen();
			this.fetch();
		},

		listen: function () {
			// this.$container.on('click', '#get-more', $.proxy(this, 'fetch'));
			this.$container.on('click', '.remove', $.proxy(this, 'remove'));
			// this.$container.on('click', '.restore', $.proxy(this, 'restore'));
			this.$container.on('click', '.save', $.proxy(this, 'save'));
		},

		remove: function (event) {
			event.stopPropagation();
			event.preventDefault();

			var $sub = $(event.currentTarget).closest('.sub');
			WH.KbApp.transOut($sub);
			this.addToSkipQue($sub);

			_.delay(function () {
				$sub.remove();
				WH.KbSubmissions.fetchIfEmpty();
			}, 1000);
		},

		addToSkipQue: function ($sub) {
			$.ajax({
				url: '/Special:KnowledgeBoxFilter/kb/skip',
				data: $sub.data(),
				method: 'post'
			});
		},

		save: function (event) {
			event.stopPropagation();
			event.preventDefault();
			var $sub = $(event.currentTarget).closest('.sub');
			this.addToSkipQue($sub);

			WH.KbQue.add($sub);
			WH.KbApp.transOut($sub, true);

			_.delay(function () {
				$sub.remove();
				WH.KbSubmissions.fetchIfEmpty();
			}, 1000);

		},

		// restore: function () {
		// 	if (this.$container.find('.restore').is(':disabled')) {
		// 		return;
		// 	}

		// 	this.$container.find('.fadeOutLeftBig').removeClass('fadeOutLeftBig')
		// 		.show().addClass('fadeInLeftBig');
		// },

		fetchIfEmpty: function () {
			// this.$container.find('.restore').removeAttr('disabled');
			if (this.$container.find('.sub').length === 0) {
				this.fetch();
			}
		},

		fetch: function () {
			$.ajax({
				dataType: 'json',
				data: {
					articleId: WH.KbApp.article.id
				},
				url: '/Special:KnowledgeBoxFilter/kb/getSubmissions'
			}).done($.proxy(this, 'render'));
		},

		showErrors: function (response) {
			this.$container.html(WH.KbApp.errorTemplate(response));
		},

		render: function (response) {
			if (!_.isEmpty(response.errors)) {
				this.showErrors(response);
				return;
			}

			this.data = response;
			this.data.title = WH.KbApp.article.title;
			this.data.article = WH.KbApp.article;
			this.$container.html(WH.KbApp.template(this.data)).animate({scrollTop: 0}, 300);
		}
	};
}());
