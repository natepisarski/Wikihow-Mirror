(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.AdminCategoryDescriptions = {
		init: function() {
			$('#acd_download').click(function () {
				var url = '/Special:AdminCategoryDescriptions';
				var form = 'action=retrieve-list';
				$.download(url, form);
				return false;
			});

			$('#acd_file').change(function () {
				var filename = $('#acd_file').val();
				if (!filename) {
					alert('No file selected!');
				} else {
					$('#admin-result').html('sending list ...');
					$('#acd-upload-form').submit();
				}
				return false;
			});
			
			$('#acd-upload-form').on("submit", function(){
				return AIM.submit(
					this, 
					{ 
						onStart: function () {  }, 
						onComplete: function (data) { 
							console.log('d',data); 
							data = JSON.parse(data);
							$("#acd_insert span").html(data.results.stats.update);
							$("#acd_delete span").html(data.results.stats.delete);
							$("#acd_bad span").html(data.results.stats.badcats.join(", "));
							$("#acd_good").show();
						} 
					}
				);
			});
		},
	};
	$(document).ready(function() {
		WH.AdminCategoryDescriptions.init();
	});
})();
