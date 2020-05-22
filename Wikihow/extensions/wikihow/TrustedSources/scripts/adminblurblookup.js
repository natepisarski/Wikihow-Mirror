(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.AdminBlurbLookup = {
		URL: '/Special:AdminTrustedSources',

		init: function() {
			$("#abl_name").on("change keyup", function(e){
				var search = $(this).val();
				var searchLen = search.length;
				var regex = new RegExp(
					search.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'i');
				$('.abl_name').each(function () {
					var val = $(this).text();

					if(val.search(regex) != -1) {
						$(this).parents("tr").removeClass("hidden");
					} else {
						$(this).parents("tr").addClass("hidden");
					}

				});

			});
			$(".abl_copy a").on("click", function(e) {
				e.preventDefault();
				$(this).parents("tr").find(".abl_blurbid input").select();
				document.execCommand("copy");
			});
		},
	};
	$(document).ready(function() {
		WH.AdminBlurbLookup.init();
	});
})();
