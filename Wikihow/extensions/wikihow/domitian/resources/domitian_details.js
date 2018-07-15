(function () {
	"use strict";
	window.WH = WH || {};

	window.WH.domitian.details = function () {};
	window.WH.domitian.details.prototype = new window.WH.domitian;

	window.WH.domitian.details.prototype.dataKeys =
		['tools', 'dates', 'aggregate_by', 'platforms', 'usertypes'];
	window.WH.domitian.details.prototype.toolURL = '/Special:DomitianDetails';

	WH.domitian.details.prototype.initialize();
}());
