(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.KbQue = {

		localStorageKey: 'kbc-filter-ids',
		windowName: 'editorwindow',
		windowPane: null,
		storage: {},
		clipper: {},

		initialize: function ($container) {
			this.$container = $container;

			ZeroClipboard.config({
				swfPath: "/extensions/wikihow/KnowledgeBoxFilter/assets/compiled/ZeroClipboard.swf"
			});

			this.storage = new WH.DataStore(
				this.localStorageKey,
				WH.KbApp.article.id
			);

			if (this.storage.getItems().length > 0) {
				this.fetch();
			} else {
				this.render({});
			}

			this.$container.on('click', '.remove', $.proxy(this, 'remove'));
		},

		fetch: function () {
			$.ajax({
				dataType: 'json',
				data: {
					kbc_ids: this.storage.getItems()
				},
				url: '/Special:KnowledgeBoxFilter/kb/getQue'
			}).done($.proxy(this, 'render'));
		},

		renderErrors: function (data) {
			this.$container.html(WH.KbApp.errorTemplate(data));
		},

		render: function (data) {
			if (!_.isEmpty(data.errors)) {
				this.renderErrors(data);
				return;
			}

			this.data = data;
			data.title = "Saved Submissions";
			data.article = WH.KbApp.article;
			this.$container.html(WH.KbApp.template(data)).animate({scrollTop: 0}, 300);
			this.listen();
		},

		listen: function () {
			this.$container.on('click', '.remove', $.proxy(this, 'remove'));
			this.clipper = new ZeroClipboard(this.$container.find('.edit'));
			this.$container.on('mouseup', '.sub', $.proxy(this, 'findSelection'));
			this.$container.on('mouseup', '.edit', $.proxy(this, 'launchEditor'));
		},

		findSelection: function (event) {
			var $sub = $(event.currentTarget),
				$btn = $sub.find('.edit');

			if (window.getSelection && window.getSelection().toString() !== '') {
				$btn.attr('data-clipboard-text', window.getSelection().toString());
			} else {
				$btn.attr('data-clipboard-text', $sub.find('.sub-content.full').text());
			}
		},

		launchEditor: function (event) {
			event.stopPropagation();
			event.preventDefault();

			if (_.isNull(this.windowPane) || this.windowPane.closed) {
				this.windowPane = window.open(
					WH.KbApp.article.url,
					this.windowName
				);
				this.windowPane.focus();

			} else {
				this.windowPane.focus();
			}
		},

		remove: function (event) {
			event.stopPropagation();
			event.preventDefault();
			var $sub = $(event.currentTarget).closest('.sub');

			this.storage.removeItem($sub.data('kbc-id'));
			WH.KbApp.transOut($sub);
		},

		add: function ($item) {
			this.$container.find('.alert').remove();
			this.$container.append($item.clone().addClass('fadeInLeftBig').slideDown(20));
			this.$container.animate({scrollTop: this.$container[0].scrollHeight}, 300);
			this.storage.addItem($item.data('kbc-id'));
			this.listen();
		}
	};
}());
