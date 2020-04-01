(function () {
	"use strict";
	window.WH = WH || {};
	window.WH.domitian = function () {};

	/**
	 * Subclasses should define:
	 *     toolURL: string (e.g. "/Special:DomitianSummary")
	 *     dataKeys: array (e.g. ["tools", "dates", "platforms", "usertypes"]
	 */
	window.WH.domitian.prototype = {
		dataKeys: undefined,

		generate: function () {
			var data = this.prepareDataForPost('generate');

			$.download(this.toolURL, data);
		},

		getData: function () {
			var keys = this.dataKeys;
			var data = {};

			if ($.inArray('tools', keys) !== -1) {
				data['tools'] = $('#domitian_tools_form input:checkbox:checked')
					.map(function () {
						return $(this).val();
					}).get();
			}

			if ($.inArray('dates', keys) !== -1
					|| $.inArray('date_from', keys) !== -1) {
				data['date_from'] = $('#domitian_date_from').val();
			}

			if ($.inArray('dates', keys) !== -1
					|| $.inArray('date_to', keys) !== -1) {
				data['date_to'] = $('#domitian_date_to').val();
			}

			if ($.inArray('stat_type', keys) !== -1) {
				data['stat_type'] =
					$('#domitian_stat_type_form input:checked').val();
			}

			if ($.inArray('aggregate_by', keys) !== -1) {
				data['aggregate_by'] =
					$('#domitian_aggregate_by_form input:checked').val();
			}

			if ($.inArray('platforms', keys) !== -1) {
				data['platforms'] =
					$('#domitian_platforms_form input:checkbox:checked')
						.map(function () {
							return $(this).val();
						}).get();
			}

			if ($.inArray('usertypes', keys) !== -1) {
				data['usertypes'] =
					$('#domitian_usertypes_form input:checkbox:checked')
						.map(function () {
							return $(this).val();
						}).get();
			}

			return data;
		},

		getQueries: function () {
			var data = this.prepareDataForPost('show_queries');

			$.post(this.toolURL, data, function (result) {
				$('#domitian_queries').removeClass('domitian_hidden');
				$('.domitian_query').remove();
				$.each(result, function (tool, queries) {
					var element = $('<div/>', {
						class: 'domitian_query'
					});

					var toolElement = $('<b/>', {
						text: tool
					});

					var queryText;

					if ($.isArray(queries)) {
						queryText = queries.join("\n\n");
					} else {
						queryText = queries;
					}

					var queryElement = $('<pre/>', {
						class: 'domitian_query_pre',
						text: queryText
					});

					element.append(toolElement);
					element.append(queryElement);

					$('#domitian_queries').append(element);
				});
			}, 'json');
		},

		initialize: function () {
			$('body').on(
				'click',
				'#domitian_tools_select_all',
				function (e) {
					$('#domitian_tools_form input:checkbox')
						.prop('checked', true);
					return false;
				}
			);

			$('body').on(
				'click',
				'#domitian_tools_select_none',
				function (e) {
					$('#domitian_tools_form input:checkbox')
						.prop('checked', false);
					return false;
				}
			);

			$('body').on(
				'click',
				'#domitian_generate',
				$.proxy(function (e) {
					this.generate();
					return false;
				}, this)
			);

			$('body').on(
				'click',
				'#domitian_show_queries',
				$.proxy(function (e) {
					this.getQueries();
					return false;
				}, this)
			);

			// From http://stackoverflow.com/questions/6982692/html5-input-type-date-default-value-to-today
			Date.prototype.toDateInputValue = (function() {
				var local = new Date(this);
				local.setMinutes(this.getMinutes() - this.getTimezoneOffset());
				return local.toJSON().slice(0,10);
			});

			$(document).ready(function() {
				// Set date range to one week before today by default
				var date_from = new Date();
				var date_to = new Date();
				date_from.setDate(date_from.getDate() - 6);

				$('#domitian_date_from').val(date_from.toDateInputValue());
				$('#domitian_date_to').val(date_to.toDateInputValue());
			});

			window.onbeforeunload = window.onpagehide = window.onunload = function () {
				return;
			};
		},

		prepareDataForPost: function (action) {
			var data = this.getData();
			
			var verifyResult = this.verify(data);

			if (!verifyResult['success']) {
				alert(verifyResult['error']);
				return;
			}

			data['action'] = action;

			// Might not be the best solution...
			Object.keys(data).map(function (key, _) {
				if (Object.prototype.toString.call(data[key]) === '[object Array]') {
					data[key] = data[key].join(',');
				}
			});

			return data;
		},

		toolURL: undefined,

		verify: function (data) {
			var keys = this.dataKeys;
			var result = {}
			result['success'] = false;

			if (!data) {
				result['error'] = 'No data received';
				return result;
			}

			if ($.inArray('tools', keys) !== -1
					&& (!data['tools'] || data['tools'].length == 0)) {
				result['error'] = 'No tools selected';
				return result;
			}

			var date_re = /\d{4}-\d{2}-\d{2}/;

			if (($.inArray('dates', keys) !== -1
						|| $.inArray('date_from', keys) !== -1)
					&& !date_re.test(data['date_from'])) {
				result['error'] = 'Invalid date given';
				return result;
			}

			if (($.inArray('dates', keys) !== -1
						|| $.inArray('date_to', keys) !== -1)
					&& !date_re.test(data['date_to'])) {
				result['error'] = 'Invalid date given';
				return result;
			}

			if (($.inArray('dates', keys) !== -1
						|| ($.inArray('date_from', keys) !== -1
							&& $.inArray('date_to', keys) !== -1))
					&& data['date_from'] > data['date_to']) {
				result['error'] = 'Start date cannot be greater than end date';
				return result;
			}

			if ($.inArray('stat_type', keys) !== -1
					&& (!data['stat_type']
						|| $.inArray(
							data['stat_type'],
							['total_events', 'unique_users']) === -1)) {
				result['error'] =
					'Stat type must be "total_events" or "unique_users"';
				return result;
			}

			if ($.inArray('aggregate_by', keys) !== -1
					&& (!data['aggregate_by']
						|| $.inArray(
							data['aggregate_by'],
							['day', 'week', 'month']) === -1)) {
				result['error'] =
					'Aggregation method must be "day", "week" or "month"';
				return result;
			}

			if ($.inArray('platforms', keys) !== -1
					&& (!data['platforms'] || data['platforms'].length == 0)) {
				result['error'] = 'No platforms selected';
				return result;
			}

			if ($.inArray('usertypes', keys) !== -1
					&& (!data['usertypes'] || data['usertypes'].length == 0)) {
				result['error'] = 'No user types selected';
				return result;
			}

			result['success'] = true;
			return result;
		},
	};

}());

