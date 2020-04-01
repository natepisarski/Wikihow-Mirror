(function(window, document, $) {
	'use strict';

	function initImageForm() {
		if ($('#articleName').val() == "") {
			alert("The article URL is empty");
			return false;
		}
		if (!$('#ImageUploadFile').val()) {
			alert("You must upload a file");
			return false;
		}

		var wpDestFileVal = $('#wpDestFile').val();
		if (!wpDestFileVal || wpDestFileVal == '.jpg') {
			alert("The image name cannot be empty");
			return false;
		}
		if (wpDestFileVal.substr(-4) != ".jpg") {
			alert("You must name your image with a .jpg at the end.");
			return false;
		}
	}

	function initAdminPanel() {
		$('.hp_admin_box').sortable();

		$('.hp_delete').click(function() {
			var url = '/Special:WikihowHomepageAdmin?delete=' + this.id;
			$.get(url, function(data) {
				if (data == 1) {
					//success!
					location.reload();
				} else {
					alert('Your request to delete this specific item was denied for reasons unknown. I blame unicorns.');
				}
			});
		});
	}

	$(document).ready(function() {

		$('#hp_form').submit(initImageForm);

		if (!window.WH.HPAdminReload) {
			initAdminPanel();
		} else {
			$.ajax({
				type: 'POST',
				data: { 'reload': true }
			})
			.done(function(data, textStatus, jqXHR) {
				$('.hp_admin_panel').replaceWith(data);
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				var data = JSON.parse(jqXHR.responseText);
				$('.hp_admin_panel').replaceWith(data);
			})
			.always(function() {
				initAdminPanel();
			});
		}

	});

}(window, document, jQuery));
