(function($, mw)
{
	'use strict';
	window.WH = window.WH || {};
	window.WH.SensitiveArticleAdmin = {

		tool_url: '/Special:SensitiveArticleAdmin',

		init: function() {
			this.addHandlers();
		},

		addHandlers: function() {
			$('#sensitive_article_admin').on('click', 'button.save_btn', function() {
				WH.SensitiveArticleAdmin.saveReason(this);
			});

			$('#sensitive_article_admin').on('click', 'a.delete', function(e) {
				e.preventDefault();
				WH.SensitiveArticleAdmin.deleteVerify(this);
			});

			// A checkbox or input field changed
			$('#sensitive_article_admin').on('change keyup', 'td > input', function() {
				var $tr = $(this).closest('tr');
				var inputIsEmpty = $tr.find(':text').val().trim() === '';
				$tr.find('button').prop('disabled', inputIsEmpty);
			});
		},

		saveReason: function(obj) {
			var $tr = $(obj).closest('tr');
			var is_new = $tr.data('id') == 0;

			$.post(this.tool_url, {
				action: 'upsert',
				id: $tr.data('id'),
				name: $tr.find(':text').val(),
				enabled: $tr.find(':checkbox').prop('checked')
			},'json')
			.done($.proxy(function(data) {
				if (data.error) {
					$('#sensitive_article_admin').prepend(data.error);
				}
				else {
					if (is_new)
						window.location.reload();
					else
						$tr.find('button').prop('disabled', true);
				}
			},this));
		},

		deleteVerify: function(obj) {
			var reason_id = $(obj).closest('tr').data('id');
			if (!reason_id) return;

			$.post(this.tool_url, {
				action: 'delete_verify',
				id: reason_id
			},'json')
			.done($.proxy(function(data) {
				if (!data.confirm_message) return;

				if (confirm(data.confirm_message)) {
					if (confirm(mw.message('saa_delete_confirm_2').text())) {
						this.deleteReason(reason_id);
					}
				}
			},this));
		},

		deleteReason: function(reason_id) {
			$.post(this.tool_url, {
				action: 'delete',
				id: reason_id
			},'json')
			.done(function(data) {
				if (data.error)
					$('#sensitive_article_admin').prepend(data.error);
				else
					window.location.reload();
			});
		}
	}

	$(document).ready(function() {
		WH.SensitiveArticleAdmin.init();
	});

}(jQuery, mediaWiki));
