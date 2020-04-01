(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.TrustedSourcesAdmin = {
		URL: '/Special:AdminTrustedSources',

		init: function() {
			$('#ats_download').click(function () {
				var form = 'action=ats_download';
				$.download(WH.TrustedSourcesAdmin.URL, form);
				return false;
			});

			$('#ats_file').change(function () {
				var filename = $('#ats_file').val();
				if (!filename) {
					alert('No file selected!');
				} else {
					$('#admin-result').html('sending list ...');
					$('#ats-upload-form').submit();
				}
				return false;
			});

			$('#ats-upload-form').on("submit", function(){
				return AIM.submit(
					this,
					{
						onStart: function () {  },
						onComplete: function (data) {
							location.reload();
						}
					}
				);
			});

			$("#ats-delete-form").on("submit", function(e) {
				e.preventDefault();
				var ids = [];
				$(".sourceids:checked").each(function(){
					ids.push($(this).val());
				});
				$.post(
					WH.TrustedSourcesAdmin.URL,
					{
						action: "ats_delete",
						ids: JSON.stringify(ids)
					},
					function(result) {
						ids = result.ids;
						for(i = 0; i < ids.length; i++) {
							$('#source' + ids[i]).parent().parent().remove();
						}
					},
					'json'
				);
			});
		},
	};
	$(document).ready(function() {
		WH.TrustedSourcesAdmin.init();
	});
})();
