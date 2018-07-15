(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.cfEditor = {

		init: function () {
			// WH.cfMessages.init();
			
			$('a.left-tab').click($.proxy(this, 'showTab'));
			$('#google-editor').load($.proxy(this, 'iframeLoaded'));
			// $('#loading').show();

			if (!_.isEmpty(window.location.hash)) {
				$(".left-tab").each(function () {
					if ($(this).attr('href') == window.location.hash) {
						$(this).click();
					}
				});
			}

			$('[data-toggle="popover"]').popover({
				placement: 'top',
				trigger: 'hover'
			});

			$('.left-tab').tooltip();
			$('a.doc').click($.proxy(this, 'docClicked'));
			$('.doc-tr').last().addClass('warning');
		},

		docClicked: function (event) {
			$('.doc-tr').removeClass('warning');
			$(event.currentTarget).parent().parent().addClass('warning');
			// $('#loading').show();
		},

		iframeLoaded: function () {
			// $('#loading').fadeOut(200);
		},

		showTab: function (event) {
			var $link = $(event.currentTarget),
				$pane = $link.parent().toggleClass('open').css('z-index', 0);

			$('.overlay-pane').not($pane[0]).removeClass('open').css('z-index', 1);
		}
	};

}());