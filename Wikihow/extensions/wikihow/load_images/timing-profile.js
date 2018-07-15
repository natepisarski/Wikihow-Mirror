( function () {
	'use strict';
	window.WH = window.WH || {};
	window.WH.timingProfile = {
		records: [],
		deferEnabled: false,

		init: function () {
			this.start = new Date().getTime();
			this.deferEnabled = window.defer !== undefined;

			if (this.deferEnabled) {
				defer.addCallback(function () {
					defer.callback = null;
					WH.timingProfile.recordTime('firstImageLoaded');
				});
			}

			this.addEvent(window, 'load', function () {
				WH.timingProfile.recordTime('windowLoaded');
				WH.timingProfile.saveRecords();
			});
		},

		recordTime: function (label) {
			var now = new Date().getTime(),
				dur = now - this.start;

			this.records.push({
				label: label,
				duration: dur
			});
		},

		saveRecords: function () {
			//for (var i = this.records.length - 1; i >= 0; i--) {
				//var record = this.records[i];
				//ga('send', {
				//	'hitType': 'timing',
				//	'timingCategory': 'images',
				//	'timingVar': this.deferEnabled ? 'deferLoadTime' : 'loadTime',
				//	'timingValue': record.duration,
				//	'timingLabel': record.label,
				//	'page': window.location.pathname
				//});
			//}

			this.records = [];
		},

		addEvent: function (el, name, callback) {
			if (el.addEventListener) { // Modern
				el.addEventListener(name, callback, false);
			} else if (el.attachEvent) { // Internet Explorer
				el.attachEvent('on' + name, callback);
			}
		}
	};
	WH.timingProfile.init();
}() );
