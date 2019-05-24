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
					statValuesString = $stat.text().replace(/,/g,""), // Getting all stat values as String and removing all commas (since parseInt doesn't handle commas)
					newVal = parseInt(statValuesString, 10) + numEdits; // Parsing stat values to int and adding number of new votes
				newVal = newVal.toLocaleString(); // toLocaleString adds thousand-separator commas to improve readability

				$stat.fadeOut(duration, function () {
					$stat.html(newVal).fadeIn();
				});
			});
		}
	};
	
}());
