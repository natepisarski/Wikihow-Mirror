(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.TrustedSources = {
		init: function() {
			$(document).on('mouseenter', 'sup.reference', function(e){
				WH.TrustedSources.showPopup(e, this);
			});
			$(document).on('mouseleave', 'sup.reference', function(e){
				e.preventDefault();
				e.stopPropagation();
				var $popup = $(".ts_popup", $(this).parent());
				$popup.hide();
			});
			$(document).on('click', 'sup.reference', function(e){
				WH.TrustedSources.showPopup(e, this);
			});
			$(document).on('click', '.ts_close', function(e){
				e.preventDefault();
				e.stopPropagation();
				var $popup = $(this).closest(".ts_popup");
				$popup.hide();
			});

		},
		showPopup(e, link) {
			//check to make sure it's not a link in the popup
			if($(e.target).closest(".ts_popup").length > 0) return;

			e.preventDefault();
			e.stopPropagation();

			var $popup = $(".ts_popup", $(link));
			if ($popup.is(':visible')) return;

			var url = $($("a", link).attr("href") + " span a").attr("href");
			$(".ts_source", $popup).attr("href", url);
			if( !$popup.hasClass("trustedsource") ) {
				$(".ts_source", $popup).text(url);
			}
			var $expertImage = $(".ts_expert_image", $popup);
			if($expertImage.length > 0 && $expertImage.attr("src") != "") {
				$expertImage.attr("src", $expertImage.data("src"));
			}
			$(".ts_popup").hide();
			$popup.show();

			var isRTL = $('body').hasClass('rtl');

			var startPosition = isRTL ? $(e.target).offset().left : $(e.target).offset().left + $(e.target).outerWidth();
			var boxWidth = $popup.outerWidth();

			var edgePos = isRTL ? startPosition - boxWidth : startPosition + boxWidth;
			var screenEdge = isRTL ? 0 : $(window).width();
			var adjustIt = isRTL ? edgePos < screenEdge : edgePos > screenEdge;

			if (adjustIt) {
				var offset = isRTL ? screenEdge + edgePos : screenEdge - edgePos;
				var direction = isRTL ? 'right' : 'left';
				$popup.css(direction, offset+'px');
			}
		}
	};
	$(document).ready(function() {
		WH.TrustedSources.init();
	});
})();
