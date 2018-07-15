(function () {
	"use strict";

	window.utils = window.utils || {};
	window.utils.preventDouble = {
		setup: function () {
			$('form.prevent-double').submit(function () {
				$(this).find('*[type="submit"]').attr('disabled', 'true').val('Sending...').text('Sending...');
				return true;
			});
		}
	};

	window.utils.preventDouble.setup();
}());