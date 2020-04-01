/* Title: jQuery Tiny Pub/Sub - v0.7 - 10/27/2011
 * Description: A really tiny pub/sub implementation for jQuery 1.7 using the two new methods(since jQuery 1.7): .on() and .off().
 * http://benalman.com/
 * Copyright (c) 2011 "Cowboy" Ben Alman; Licensed MIT, GPL
 */
(function($) {
	var o = $({});
	$.subscribe = function() {
		o.on.apply(o, arguments);
	};
	$.unsubscribe = function() {
		o.off.apply(o, arguments);
	};
	$.publish = function() {
		o.trigger.apply(o, arguments);
	};
}(jQuery));
