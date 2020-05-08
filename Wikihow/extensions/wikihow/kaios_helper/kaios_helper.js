(function (mw, $) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.KaiosHelper = {
		init: function() {
			$("<input />").attr("type", "hidden")
				.attr("name", "kaios")
				.attr("value", "1")
				.appendTo('form');
		}
	};

	WH.KaiosHelper.init();
}(mw, jQuery));



