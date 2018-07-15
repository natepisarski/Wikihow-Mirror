(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.noteModals = {

		init: function () {
			$('body').on('click', '.modal-trigger,#message-submit', function(event) {
				var $target = $(event.currentTarget);
				if ($target.hasClass('modal-trigger')) WH.noteModals.loadForm(event);
				if ($target.attr('id') === 'message-submit') WH.noteModals.validateMessageForm(event);
			});
		},

		loadForm: function(event) {
			event.preventDefault();
			event.stopPropagation();
			var $link = $(event.currentTarget),
				url = $link.attr('href'),
				$modal = $('#note-modal');

			WH.cfApp.showLoading();
			$.get(url, function (response) {
				$modal.modal('show').find('.modal-content').html(response);
				WH.cfApp.hideLoading();
			});
		},

		validateMessageForm: function (event) {
			var $btn = $(event.currentTarget),
				$form = $btn.parent(),
				$textarea = $form.find('textarea'),
				ajax = $form.find("input[name=ajax]").val();

			if ($textarea.hasClass('required') && $textarea.val() === '') {
				event.preventDefault();
				event.stopPropagation();
				$form.find('.form-group').addClass('has-error');
				return;
			}

			if (ajax) {
				event.preventDefault();
				event.stopPropagation();

				$btn.attr('disabled', true).val('Sending...');

				$.ajax({
					url: $form.attr('action'),
					type: 'post',
					data: $form.serialize()
				}).always(function (response) {
					WH.articleTable.updateTr(response);
					$('#note-modal').modal('hide');
				});
			}
		}
	};

}());
