(function () {
	'use strict';
	window.WH = WH || {};
	window.WH.MethodHelpfulness.BottomForm.Mobile = function () {};
	window.WH.MethodHelpfulness.BottomForm.Mobile.prototype = new window.WH.MethodHelpfulness.BottomForm();

	window.WH.MethodHelpfulness.BottomForm.Mobile.prototype.platform = 'mobile';

	window.WH.MethodHelpfulness.BottomForm.Mobile.prototype.initialize = function (elem) {
		WH.MethodHelpfulness.BottomForm.prototype.initialize.call(this, elem);

		elem.siblings('h2').remove();
		$('#article_rating').css('max-width', 'none');
	};

	$(document).ready(function () {
		var mhbfm = new WH.MethodHelpfulness.BottomForm.Mobile();
		mhbfm.initialize(mhbfm.parentElement);
	});
}());

