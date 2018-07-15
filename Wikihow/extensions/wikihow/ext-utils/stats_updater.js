(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.statsUpdater = {

		update: function (numEdits) {
			numEdits = numEdits || 1;
			var $counters = $("td[id^='iia_stats_']").not("td[id^='iia_stats_standing']");

			$counters.each(function (index, elem) {
				var duration = index * 400,
					$stat = $(elem),
					newVal = parseInt($stat.text(), null) + numEdits;

				$stat.fadeOut(duration, function () {
					$stat.html(newVal).fadeIn();
				});
			});
		}
	};
	
}());