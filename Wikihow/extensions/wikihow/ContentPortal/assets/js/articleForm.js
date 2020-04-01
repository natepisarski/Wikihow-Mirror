(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.articleForm = {

		initialize: function (article) {
			this.template = Handlebars.compile($('#dropdown-tpl').html());
			this.url = WH.Routes.users_filter;
			this.article = article;
			this.listen();
			this.catChanged();
		},

		listen: function () {
			$('#state').change($.proxy(this, 'catChanged'));
			$('#note-toggle').click($.proxy(this, 'toggleNote'));
			$('#lock-toggle').click($.proxy(this, 'toggleLock')).popover({
				placement: 'down',
				trigger: 'hover',
				container: 'body'
			});
			this.toggleNote();
		},

		toggleLock: function (event) {
			var $lock = $(event.currentTarget),
				$input = $('input[name="article[title]"]');

			$lock.popover('hide');

			$lock.toggleClass('locked');
			if ($lock.hasClass('locked')) {
				$input.attr('disabled', true);
			} else {
				$input.attr('disabled', false);
			}
		},

		toggleNote: function () {
			var $checkbox = $("#note-toggle"),
				$textarea = $('#note-input textarea');
			$textarea = $checkbox.is(':checked') ? $textarea.removeClass('hidden').hide().slideDown(200) : $textarea.slideUp(200);
		},

		catChanged: function () {

			if ($('#state').find('option:selected').is(':disabled')) {
				$('#assign-form').hide();
				return;
			}

			$('#assign-form').show();
			$('#writers').addClass('fadeOut');
			$.ajax({
				url: this.url,
				data: {role_id: $('#state').val()},
				method: "get"
			}).done($.proxy(this, 'renderDropdown'));
		},

		renderDropdown: function (response) {
			var data = response;
			data.article = this.article;

			$('#users').removeClass('fadeOut').addClass('fadeIn').html(this.template(data));
		}
	};
}());
