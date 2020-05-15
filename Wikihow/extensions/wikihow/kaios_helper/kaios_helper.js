(function (mw, $) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.KaiosHelper = {
		init: function() {
			$("<input />").attr("type", "hidden")
				.attr("name", "kaios")
				.attr("value", "1")
				.appendTo('form');

			WH.KaiosHelper.initListeners();
		},

		initListeners: function() {
			$(document).on('keydown', function(e) {
				switch (e.key) {
					case 'Backspace':
						// Don't close the app
						if (WH.KaiosHelper.isInputFocused()) {
							if (!$(document.activeElement).val()) {
								document.activeElement.blur();
							}
						} else {
							e.preventDefault();
						}

						break;
				}
			});

			$(document).on('keyup', function(e) {
				switch (e.key) {
					case 'Backspace':
						if (!WH.KaiosHelper.isInputFocused()) {
							history.back();
						}
						break;
				}
			});
		},

		isInputFocused: function () {
			var activeTag = document.activeElement.tagName.toLowerCase();
			var isInput = false;
			// the focus switches to the 'body' element for system ui overlays
			if (activeTag == 'input' || activeTag == 'select' || activeTag == 'text' || activeTag == 'textarea' || activeTag == 'body' || activeTag == 'html') {
				isInput = true;
			}

			return isInput;
		}
	};

	WH.KaiosHelper.init();
}(mw, jQuery));



