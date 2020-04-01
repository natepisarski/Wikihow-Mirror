(function () {
	"use strict";
	window.WH = window.WH || {};
	WH.reservations = {

		init: function () {
			$('#tag-selector').change($.proxy(this, 'loadArticles'));
			$('.btn.reserve').click($.proxy(this, 'saveArticles'));
			this.errorTemplate = Handlebars.compile($('#error-template').html());
		},

		saveArticles: function (event) {
			var $btn = $(event.currentTarget);

			WH.cfApp.showLoading();

			$.post(
				WH.Routes.reservations_create,
				$btn.data()
			).always(function (response) {
				this.handleResponse(response, $btn);
			}.bind(this));

		},

		handleResponse: function(response, $btn) {
			WH.cfApp.hideLoading();

			if (response.success) {
				var $tr = $btn.closest('tr');
				$tr.html($('#confirm-template').html());
				// $tr.anCss('fadeOut').anDone(function () {
				// 	this.remove();
				// });

			} else {
				$('#errors-modal')
					.modal('show')
					.html(this.errorTemplate(response));
			}
		},

		loadArticles: function () {
			$('#tag-selector-form').submit();
		}
	};

}());