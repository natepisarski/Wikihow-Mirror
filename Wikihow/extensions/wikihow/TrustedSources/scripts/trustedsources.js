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
			var url = $($("a", link).attr("href") + " span a").attr("href");
			$(".ts_source", $popup).attr("href", url);
			if( !$popup.hasClass("trustedsource") ) {
				$(".ts_source", $popup).text(url);
			}
			$(".ts_popup").hide();
			$popup.show();
			var rightPos = $popup.offset().left + $popup.outerWidth();
			var screenWidth = $(window).width();
			if(  rightPos > screenWidth ) {
				$popup.css("left", (screenWidth - rightPos)+"px");
			}
		}
	};
	$(document).ready(function() {
		WH.TrustedSources.init();
	});
})();
