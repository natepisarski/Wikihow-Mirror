(function () {
	'use strict';
	window.WH = WH || {};
	window.WH.MethodHelpfulnessWidget = function () {};

	window.WH.MethodHelpfulnessWidget.prototype = {
		/**
		 * Sorts the methods in the widget to match those in the article.
		 *
		 * The backend is unaware of which methods actually exist in an article,
		 * and may send the stored methods in a jumbled order, and even methods
		 * that no longer exist.
		 * 
		 * This function matches the methods sent by the backend with what's
		 * actually in the article DOM, and reorganizes the methods displayed
		 * in the widget so they appear in the same order as the article.
		 *
		 * Methods sent from the backend that also appear in the article are
		 * sorted and placed at the top. Received methods that do not appear
		 * in the article are placed at the bottom, and a blank separator
		 * row is inserted in-between.
		 *
		 * TODO: Move this logic to backend when possible.
		 */
		sortMethods: function () {
			var mh = this;
			$.each($('.mhw-table'), function (_, table) {
				var orderedPriorityRows = [];
				table = $(table);
				$.each(table.find('.mhw-tr-data'), function (j, row) {
					row = $(row);
					var col = row.find('.mhw-k-0');
					var colText = $.trim(col.text());
					var rowMethodFound = false;
					$.each(WH.methods, function (i, method) {
						if (colText === method) {
							orderedPriorityRows[i] = row;
							rowMethodFound = true;
							return;
						}
					});
				});
				// Normalize array keys
				var normalizedPriorityRows =
					orderedPriorityRows.filter(function () { return true; });

				$.each(normalizedPriorityRows, function(i, row) {
					mh.moveRow(row, i);
				});

				var priorityCount = normalizedPriorityRows.length;

				if (priorityCount && table.find('.mhw-tr-data').length > priorityCount) {
					var tr = $('<tr/>', {
						'class': 'mhw-tr mhw-tr-separator'
					});

					var td = $('<td/>', {
						'class': 'mhw-td',
					});

					td.append($('<hr/>'));

					for (var i = 0; i < table.find('.mhw-tr-header').children().length; i++) {
						td.clone().appendTo(tr);
					}

					tr.insertBefore(table.first().find('.mhw-tr-data:eq(' + priorityCount + ')'));
				}
			});

		},

		moveRow: function (a, i) {
			var sibling = a.parent().find('.mhw-tr-data:eq(' + i + ')');
			if (!sibling.length) {
				sibling = a.parent().find('.mhw-tr-data:last');
			}
			if (!sibling.is(a)) {
				a.insertAfter(sibling);
			}
		}
	};
}());

