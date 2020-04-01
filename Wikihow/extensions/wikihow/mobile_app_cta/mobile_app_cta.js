(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.MobileAppCTA = {
		$root: $('#mcta_root'),
		$link: $('#mcta_link'),
		$subprompt: $('#mcta_subprompt'),
		COOKIE_NAME: 'mcta',
		init: function() {
			if (this.isTarget() && !this.isDismissed()) {
				if (this.isAndroid()) {
					this.initDialog('android');
				}
				//else if (this.isIOS()) {
				//	 Holding off on iOS for now
				//	this.initDialog('ios');
				//}
			}
		},
		isTarget: function() {
			// Show on ~50% of pages
			return Math.random() <= .5;
		},
		isDismissed: function() {
			return $.cookie(this.COOKIE_NAME) || false;
		},
		dismissPrompt: function () {
			$.cookie(this.COOKIE_NAME, 1, {expires: 30});
			this.$root.slideUp();
		},
		initDialog: function(os) {
			this.initListeners();
			this.$link.attr('href', mw.msg('mcta_url_' + os)).html(mw.msg('mcta_prompt_' + os));
			this.$subprompt.html(mw.msg('mcta_subprompt_' + os));
			this.$root.addClass(os);

		},
		initListeners: function() {
			$('#mcta_root').on('click', '#mcta_link', $.proxy(this, 'onLinkClick'));
			$('#mcta_root').on('click', '#mcta_close', $.proxy(this, 'dismissPrompt'));
		},
		onLinkClick: function(e) {
			e.preventDefault();
			this.dismissPrompt();
			var win = window.open(this.$link.attr('href'), '_blank');
			win.focus();
		},

		isAndroid: function() {
			return /(android)/i.test(navigator.userAgent);
		},
		isIOS: function() {
			return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
		}
	}

	$(document).ready(function() {
		WH.MobileAppCTA.init();
	});
}($, mw));