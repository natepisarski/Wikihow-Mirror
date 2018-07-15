(function () {
	"use strict";
	window.WH = WH || {};
	window.WH.usageLogs = {

		que: [],
		selector: '*[data-event_action]',
		showConsole: false,

		defaults: {
			event_type: null,
			event_action: null,
			label: null,
			article_id: null,
			assoc_id: null,
			browser_version: bowser.version,
			browser: bowser.name,
			serialized_data: null
		},

		initialize: function () {
			WH.xss.addToken();
			$('body').on('mousedown', this.selector, _.throttle($.proxy(this, 'trackUsage'), 200));
			var handler = _.once($.proxy(this, 'postDataIfNeeded'));
			window.onbeforeunload = window.onpagehide = window.onunload = function () {
				handler(false);
				return;
			};

			setInterval($.proxy(this, 'postDataIfNeeded'), 2000);
		},

		postDataIfNeeded: function (async) {
			if (_.isEmpty(this.que)) {
				return;
			}

			async = _.isUndefined(async) ? true : false;

			$.ajax({
				type: 'POST',
				url: '/Special:UsageLogs',
				data: {
					events: this.que
				},
				async: async,
				dataType: 'json'
			});

			this.clearData();
		},

		clearData: function () {
			this.que = [];
		},

		setDefaults: function () {
			var domData = this.filterKeys($('body').data());
			_.extend(this.defaults, domData);
		},

		// make sure we are only posting permitted keys (not anything on data dom)
		filterKeys: function (obj) {
			return _.pick(obj, function (value, key) {
			   return _.has(WH.usageLogs.defaults, key);
			});
		},

		log: function (action) {
			this.setDefaults();
			var payload = _.extend(_.clone(this.defaults), this.filterKeys(action));
			// if data attr not in our defaults list, it gets put in serialized_data as JSON string
			_.each(_.keys(action), function (key) {
				if (!_.has(this.defaults, key)) {
					payload.serialized_data = payload.serialized_data || {};
					payload.serialized_data[key] = action[key];
				}
			}, this);
			
			// failsafe to prevent eager clicking before DOM and JS are initialized
			if (_.isNull(payload.event_type) || _.isNull(payload.event_action)) {
				return;
			};

			payload.serialized_data = payload.serialized_data ? JSON.stringify(payload.serialized_data) : null;
			this.que.push(payload);
			this.trace(this.que, 'table');
		},

		highlightDom: function () {
			if (WH.opWHTracker) {
				$(this.selector).each(function (index, item) {
					WH.opWHTracker.highlight(index, item, 'green');
				});
			} else {
				console.warn("opWHTracker.js is not loaded on this page, therefore I cannot highlight the dom as they share the highlighting functionality.");
			}
		},

		trackUsage: function (event) {
			var $target = $(event.currentTarget);
			if ($target.hasClass('clickfail')) {
				return;
			}
			this.log($target.data());
		},

		trace: function (data, action) {
			if (!this.showConsole) {
				return;
			}
			action = action || 'log';
			console[action](data);
		}
	};

	WH.usageLogs.initialize();
}());
