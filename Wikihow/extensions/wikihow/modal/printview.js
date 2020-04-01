(function ($) {
	'use strict';

	window.WH = WH || {};

	window.WH.PrintView = {
		popModal: function () {
			WH.shared.loadAllImages();
			if (WH.video) {
				WH.video.loadAllVideos();
			}
			$.get('/Special:BuildWikihowModal?modal=printview', function(data) {
				$.modal(data, {
					zIndex: 100000007,
					maxWidth: 360,
					minWidth: 360,
					overlayCss: { "background-color": "#000" }
				});

				WH.PrintView.prep();
			});
		},

		prep: function () {
			$('#wh_modal_close').click(function () {
				$.modal.close()
				$('.mwimg').removeClass('mwimg-show');
				return false;
			});

			$('#wh_modal_btn_text_only').click(function () {
				window.print();
				$.modal.close()
				return false;
			});


			$('#wh_modal_btn_incl_imgs').click(function () {
				$('.mwimg').addClass('mwimg-show');
				window.print();
				$.modal.close()
				$('.mwimg').removeClass('mwimg-show');
				return false;
			});
		}
	};
}(jQuery));

