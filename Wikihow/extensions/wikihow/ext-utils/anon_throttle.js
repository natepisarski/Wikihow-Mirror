window.WH = window.WH || {};
window.WH.AnonThrottle = (function () {
	"use strict";

	function AnonThrottle(options) {
		this.numEditsAllowed = options.maxEdits || 5;
		this.expires = options.cookieExp || 1;
		this.cookieName = options.toolName;

		if (this.cookieName === undefined) {
			console.error("you must pass a tool name to AnonThrottle class");
		}

		if (!this.getCookie()) {
			// only initialize it if there is not already one there...
			this.setCookie(0);
		}
	}

	AnonThrottle.prototype = {

		isAnon: function () {
			return mw.user.isAnon() && $.cookie(this.cookieName);
		},

		recordEdit: function (numEdits) {
			numEdits = numEdits || 1;
			if (this.isAnon()) {
				this.setCookie(this.getCookie() + numEdits);
			}
		},

			limitReached: function () {
			return this.isAnon() && !WH.isAndroidAppRequest && this.getCookie() >= this.numEditsAllowed;
		},

		getCookie: function () {
			var val = $.cookie(this.cookieName);
			return val ? parseInt($.cookie(this.cookieName), null) : undefined;
		},

		setCookie: function (val) {
			if (!mw.user.isAnon()) {
				return;
			}

			$.cookie(this.cookieName, val, {
				expires: this.expires,
				domain: '.' + mw.config.get('wgCookieDomain')
			});
		}
	};

	return AnonThrottle;
}());