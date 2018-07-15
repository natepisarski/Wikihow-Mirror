(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.cfApp = {

		initialize: function (errors) {
			this.setupActiveLinks();
			this.formSubmit();

			$('.kudo .close').click(function (event) {
				var $btn = $(event.currentTarget);
				event.preventDefault();
				$.get($btn.attr('href'));
				$btn.parent().hide();
			});

			$('#impersonate-bar .cancel').click(function () {
				$('#impersonate-bar').fadeOut(200);
			});

			if (errors) this.highlightErrors(errors);

			$('body').on('click', 'a.blocking', $.proxy(this, 'showLoading'));
		},

		showLoading: function () {
			$('#loading').show();
		},

		hideLoading: function () {
			$('#loading').hide();
		},

		formSubmit: function () {
			$('form.prevent-double').submit(function () {
				$(this).find('*[type="submit"]').attr('disabled', 'true').val('Sending...').text('Sending...');
				return true;
			});
		},

		highlightErrors: function (errors) {
			_.each(_.keys(errors), function (key) {
				$('.form-group.' + key).addClass('has-error');
			}, this);
		},

		setupActiveLinks: function () {
			var url = _.last(window.location.href.split("Special:")),
				arr = url.split('/'),
				className,
				$link;

			arr.splice(0, 1, 'nav');
			className = arr.join('-').split('?')[0];

			$('.navbar a').each(function () {
				$link = $(this);
				if ($link.hasClass(className)) {
					$link.parent().addClass('active');
				}
			});
		}
	};
}());