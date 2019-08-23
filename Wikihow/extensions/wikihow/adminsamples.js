(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.AdminSamples = {

		init: function() {
			$('#pages-go')
				.removeAttr('disabled')
				.click(function () {
					var form = $('#404_samples').serializeArray();
					$('#pages-result').html('loading...');
					$.post('/Special:AdminSamples',
						form,
						function(data) {
							$('#pages-result').html(data['result']);
							$('#pages-list').focus();
						},
						'json');
					return false;
				});

			$('#import_samples').click(function() {
				var form = $('#import_the_samples').serializeArray();
				$('#import_result').html('importing...');
				$.post('/Special:AdminSamples?action=import',
					form,
					function(data) {
						$('#import_result').html(data['result']);
						$('#import_samples').removeAttr('disabled');
					},
					'json');
				return false;
			});

			$('.display_name_table .edit').click(function() {
				//reset any open inputs
				$('.display_name_table').find('.dname_edit').each(function() {
					$(this).parent().html($(this).attr('value'));
				});
				var samp = $(this).prev().prev().html();
				var dname = $(this).prev();
				$(dname).html('<input type=\"text\" class=\"dname_edit\" name=\"dname_edit\" value=\"'+$(dname).html()+'\" /> <input type=\"button\" id=\"dname_change\" value=\"Update\" />');

				$('#dname_change').click(function() {
					var new_dname = $(this).prev().attr('value');
					$.post('/Special:AdminSamples?action=edit', { 'sample': samp, 'dname': new_dname, 'pages-list': 'nothing' }, function(data) {
						if (data.length) {
							$(dname).html(new_dname);
						}
						else {
							alert('Error: Display name not changed.');
						}
					});
				});
			});

			$('.display_name_table .delete').click(function() {
				var samp = $(this).prev().prev().prev().html();
				var row = $(this).parent();
				if (confirm('Are you sure you want to remove the display name from '+samp+'?')) {
					$.post('/Special:AdminSamples?action=delete', { 'sample': samp, 'pages-list': 'nothing' }, function(data) {
						if (data.length) {
							alert(data);
							$(row).hide();
						}
					});
				}
			});

			$('#add_new_dname').click(function() {
				var samp = $('#new_sample').attr('value');
				var dname = $('#new_dname').attr('value');

				$.post('/Special:AdminSamples?action=addnew', { 'sample': samp, 'dname': dname, 'pages-list': 'nothing' }, function(data) {
					if (data.length) {
						$('#new_sample').attr('value','');
						$('#new_dname').attr('value','');
						alert(data);
						location.reload();
					}
					else {
						alert('Error: Display name not added.');
					}
				});
			});
		}
	};

	WH.AdminSamples.init();
}($, mw));
