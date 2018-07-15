(function () {
	"use strict";
	window.WH = WH || {};

	window.WH.domitian.segments = function () {};
	window.WH.domitian.segments.prototype = new window.WH.domitian;

	window.WH.domitian.segments.prototype.dataKeys =
		['tools', 'dates'];
	window.WH.domitian.segments.prototype.toolURL = '/Special:DomitianSegments';

	WH.domitian.segments.prototype.initialize();
}());
