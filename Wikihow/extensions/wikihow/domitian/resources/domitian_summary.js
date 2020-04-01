(function () {
	"use strict";
	window.WH = WH || {};

	window.WH.domitian.summary = function () {};
	window.WH.domitian.summary.prototype = new window.WH.domitian;

	window.WH.domitian.summary.prototype.dataKeys =
		['tools', 'dates', 'platforms', 'usertypes'];
	window.WH.domitian.summary.prototype.toolURL = "/Special:DomitianSummary";

	WH.domitian.summary.prototype.initialize();
}());

