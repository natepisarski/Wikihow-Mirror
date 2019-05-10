(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.CreateEmptyIntlArticle = {
		init: function() {
			this.clickHandlers();
		},

		clickHandlers: function() {
			/*$(document).on("submit", "#admin-upload-form", function(e){
				e.preventDefault();
				var filename = $('#csvFile').val();
				if (!filename) {
					alert('No file selected!');
					return;
				}

				var formData = new FormData(this);
				$.post({
					url: '/Special:CreateEmptyIntlArticle?upload=1',
					data: formData,
					processData: false,
					contentType: false
				});
			});*/
		}

	};
	$(document).ready(function() {
		WH.CreateEmptyIntlArticle.init();
	});
})();
