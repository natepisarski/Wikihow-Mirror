(function () {
	'use strict';
	window.WH = window.WH || {};
	WH.importer = {

		init: function () {
			this.template = Handlebars.compile($('#success-msg').html());
			$('.btn.import').click($.proxy(this, 'save'));
		},

		save: function (event) {
			event.preventDefault();

			var $target = $('#' + $(event.target).data('target')),
				$checked = $target.find('.article-select:checked'),
				payload = _.map($checked, function (article) {
					return JSON.parse($(article).closest('.article').find('.data').val());
				});

			WH.cfApp.showLoading();
			$.ajax({
				type: 'post',
				url: WH.Routes.import_create,
				data: {articles: payload}
			})
			.always(_.bind(function (response) {
				$checked.closest('.article').remove();
				this.showResult(response);
			}, this));
		},

		showResult: function (response) {
			WH.cfApp.hideLoading();

			_.each($('.panel.group'), function (group) {
				if ($(group).find('.article').length === 0) {
					$(group).remove();
				}
			});

			utils.checkAll.update();
			$('body').animate({scrollTop: 0}, 300);
			$('#message').html(this.template(response));
		}

	};
}());