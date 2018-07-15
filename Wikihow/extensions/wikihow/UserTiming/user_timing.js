/**
 * @file Provides wrappers for methods defined in W3C's User Timing spec
 *       (https://www.w3.org/TR/2013/REC-user-timing-20131212).
 */

(function() {
	'use strict';

	window.WH = window.WH || {};
	window.WH.performance = {};

	/**
	 * performance.mark() wrapper
	 */
	window.WH.performance.mark = function(name) {
		if (typeof performance !== 'undefined' && typeof performance.mark === 'function') {
			return performance.mark(name);
		}
	};

	/**
	 * performance.clearMarks() wrapper
	 */
	window.WH.performance.clearMarks = function(name) {
		if (typeof performance !== 'undefined' && typeof performance.clearMarks === 'function') {
			return performance.clearMarks(name);
		}
	};

}());
