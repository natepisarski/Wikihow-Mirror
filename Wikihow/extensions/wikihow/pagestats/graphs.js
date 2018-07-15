$(document).ready(function() {
	function makeModal(div) {
		var info = document.getElementById(div).layout.info;
		var data = document.getElementById(div).data;
		$('#' + div).removeClass('graphs-small').addClass('graphs-large').modal({
			overlayCss: {
				backgroundColor: '#000',
				opacity: 10,
			},
			onClose: function() {
				$.modal.close();
				$('#' + div).empty().removeClass('graphs-large').addClass('graphs-small');
				createChart(div, info, { labels: data[0].x }, data); // this cheats a bit, since we're re-drawing
			},
			overlayClose: true,
			position: ['20%', '30%']
		});
		$('#' + div).empty();
		createChart(div, info, { labels: data[0].x }, data, true); // this cheats a bit, since we're re-drawing
	}

	function getTraceData(info, data) {
		var traces = [];
		var traceDefault = {
			x: $.map(data.labels, function(x) { return moment(x).utc().toDate(); }),
			marker: {
				color: '#93b874'
			}
		};
		if (data.data.hasOwnProperty('length')) {
			data.data = { 'total': data.data }; // cheat
		}

		if (info.subcmd === 'get_helpfulness_data') {
			var trace = $.extend({}, traceDefault, {
				y: $.map(data.data.percent, function(x) { return Number(x); }),
				name: info.names.percent,
				type: 'scatter',
				text: $.map(data.data.total, function(x) { return 'Votes: ' + x; })
			});
			traces.push(trace);
		} else {
			// Technically this just creates multiple series on one x-axis. See comments
			// below if you need to create a second y-axis.
			$.each(Object.keys(data.data), function(idx, key) {
				var trace = $.extend({}, traceDefault, {
					y: $.map(data.data[key], function(x) { return Number(x); }),
					name: info.names[key],
					type: 'scatter'
				});
				traces.push(trace);
			});
		}

		/**
		 // How to do a second y-axis, should it become necessary again
			traces.push($.extend({}, traceDefault, {
				y: $.map(val, function(x) { return Number(x); }),
				name: info.names[key],
				yaxis: 'y2',
				type: 'scatter',
				marker: {
					color: 'rgb(68,79,69)'
				},
				line: {
					dash: 'dashdot',
				}
			}));
	 **/

		return traces;
	}

	function createChart(chartId, info, data, traces, modal) {
		var layout = {
			title: info.title,
			paper_bgcolor: 'rgba(0,0,0,0)',
			plot_bgcolor: 'rgba(0,0,0,0)',
			font: {
				size: 9
			},
			margin: { l: 30, r: 20, t: 40, b: 30 },
			showlegend: false,
			xaxis: {
				type: 'date'
			},
			yaxis: {
				rangemode: 'normal'
			},
			info: info
		};
		var options = {
			displaylogo: false,
			displayModeBar: false,
			modeBarButtonsToRemove: ['sendDataToCloud']
		};

		if (modal) {
			layout.paper_bgcolor = '#f6f5f4';
			layout.plot_bgcolor = '#f6f5f4';
			layout.margin.t = 80;
			options.displayModeBar = 'hover';
			layout.title += '&nbsp;<a href="#">&#10006;</a>';
		} else {
			layout.title += '&nbsp;<a href="#">&#10530;</a>';
		}

		if (info.xnote) {
			layout.xaxis.title = info.xnote;
			layout.xaxis.titlefont = {
				size: 7
			};
		}

		if (info.yrangemode) {
			layout.yaxis.rangemode = info.yrangemode;
		}

		if (data.labels.length === 1) {
			layout.xaxis.type = 'category';
		}

		$.each(traces, function(idx, val) {
			if (val.yaxis) {
				layout.yaxis2 = {
					overlaying: 'y',
					side: 'right'
				};
			}

			if (layout.xaxis.type === 'category' && val.x.length === 1) {
				val.x = [moment(val.x[0]).format('YYYY-MM')];
			}
		});

		Plotly.newPlot(chartId, traces, layout, options);
		$('#' + chartId + ' text').find('a').on('click', function(e) {
			e.preventDefault();
			if (!modal) {
				makeModal($(e.target).parents('div.graphs').attr('id'));
			} else {
				$.modal.close();
			}
		});
	}

	function makeChartId(subcmd) {
		return 'staff_chart_' + subcmd;;
	}

	var groupSelect = null;
	function addChart(idx, info) {
		var chartId = makeChartId(info.subcmd);

		var chartsContainer = $('#staff_charts_box');
		if (idx == 0) { // First chart in a group
			groupSelect = $('<select class="staff_charts_select">');
			$('<div style="text-align: center;"></div>')
				.append(groupSelect) // Wrap the select with a div in order to center it
				.appendTo(chartsContainer); // Insert the div inside the chart container
		}
		if (idx >= 0) { // Any chart in a group
			groupSelect.append($('<option>').attr('value', info.subcmd).text(info.title));
		}
		var chartDiv = $('<div/>', {
			id: chartId,
			class: 'graphs graphs-small',
			style: 'height: 225px',
		}).appendTo(chartsContainer);

		var params = {
			subcmd: info.subcmd,
			action: 'graphs',
			format: 'json',
			page_id: wgArticleId,
			language_code: wgContentLanguage
		};

		$.get('/api.php', params).done(function(data) {
			if (!data.data) {
				$(chartId).remove();
				return false;
			}
			var traces = getTraceData(info, data);
			createChart(chartId, info, data, traces);
			if (idx > 0) {
				$(chartDiv).hide();
			}
		});
	}

	if ($('#staff_charts_box').length) {
		var helpfulTitle = 'Page helpfulness %';
		if ($.inArray('Computers and Electronics', wgCategories) != -1) {
			helpfulTitle = 'Tech Page up-to-date %';
		}
		var charts = [
			[
				{ title: '30 day views m/m', names: { total: 'Views' }, subcmd: 'get_30day_views' },
				{ title: '30 day unique views m/m', names: { total: 'Unique Views' }, subcmd: 'get_30day_views_unique' },
				{ title: '30 day unique views mobile m/m', names: { total: 'Unique Views Mobile' }, subcmd: 'get_30day_views_unique_mobile' }
			],
			{ title: helpfulTitle, names: { total: 'Votes', percent: 'Helpful %' }, subcmd: 'get_helpfulness_data', xnote: 'Points reflect helpfulness resets', yrangemode: 'tozero' }
		];

		$.each(charts, function(idx, config) {
			if (config instanceof Array) { // A group of related charts
				$.each(config, function(idx, info) { addChart(idx, info); });
			} else { // Individual chart
				addChart(-1, config);
			}
		});
	}

	$('select.staff_charts_select').change(function() {
		$(this).find('option').each(function() {
			var chartId = makeChartId(this.value);
			$('#' + chartId).hide();
		});
		var selectedChartId = makeChartId(this.value);
		$('#' + selectedChartId).show();
	});

});
