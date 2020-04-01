(function () {
	'use strict';
	window.WH.MethodHelpfulness.BottomForm.Desktop = function () {};
	window.WH.MethodHelpfulness.BottomForm.Desktop.prototype = new window.WH.MethodHelpfulness.BottomForm();

	window.WH.MethodHelpfulness.BottomForm.Desktop.prototype.platform = 'desktop';

	$(document).ready(function () {
		var mhbfd = new WH.MethodHelpfulness.BottomForm.Desktop();
		mhbfd.initialize(mhbfd.parentElement);
	});
}());

