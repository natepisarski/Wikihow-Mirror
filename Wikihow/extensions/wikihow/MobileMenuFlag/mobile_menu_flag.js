(function(M,$) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.MobileMenuFlag = {
		COOKIE_NAME: 'mmf',
		COOKIE_SHOW_ALL: 0,	// <-|
		COOKIE_SHOW_MENU: 1,	//   |--cookie values
		COOKIE_SHOW_NONE: 2,	// <-|
		MSG_PCT: 'mobile_menu_percent',
		MSG_FLAG_LINE: 'mobile_menu_flag',
		$hamburger: $('#mw-mf-main-menu-button'),
		ID_HAMBURGER: 'mmf_ham',
		ID_MENU: 'mmf_menu',

		init: function() {
			if (this.flagging()) {
				this.addFlags();
			}
		},

		/**
		 * check if we're throwing flags
		 * - returns t/f
		 */
		flagging: function() {
			//anon only
			if (wgUserName !== null) return false;

			//not flagging if we've already set the cookie
			var mmf = $.cookie(this.COOKIE_NAME);
			if (typeof mmf != 'undefined' && parseInt(mmf) == this.COOKIE_SHOW_NONE) return false;

			//get the threshold percentage that's set by a mw msg
			var tp = parseInt(mw.msg(this.MSG_PCT));

			//is it a good %? if not, NO FLAG FOR YOU!
			if (tp == 'NaN' || tp < 0 || tp > 100) return false;

			return this.getVisitorPct() <= tp;
		},

		/**
		 * getVisitorPct()
		 * - returns a % based on the first character of the visitor id
		 */
		getVisitorPct: function() {
			var visitor_pct = 110; //impossible %
			var visitor_id = $.cookie('whv');

			if (typeof visitor_id != 'undefined') {

				//hash it up
				var id_hash = this.hashCode(visitor_id);
				if (id_hash != 0) {

					//make this a positive experience
					id_hash = id_hash < 0 ? id_hash * -1 : id_hash;

					//mod it for %
					visitor_pct = id_hash % 100;
				}
			}

			return visitor_pct;
		},

		hashCode: function(visitor_id) {
			var hash = 0, i, chr, len;
			if (visitor_id.length === 0) return hash;
			for (i = 0, len = visitor_id.length; i < len; i++) {
				chr = visitor_id.charCodeAt(i);
				hash = ((hash << 5) - hash) + chr;
				hash |= 0; // Convert to 32bit integer
			}
			return hash;
		},

		addFlags: function() {
			//where are we putting stuff? grab the mw msg
			var line_id = mw.msg(this.MSG_FLAG_LINE);
			if (line_id == '') return;

			//set the obj
			var $line = $('#'+line_id);
			if (!$line.length) return;

			//add those flags
			var mmf = typeof $.cookie(this.COOKIE_NAME) == 'undefined' ? this.COOKIE_SHOW_ALL : parseInt($.cookie(this.COOKIE_NAME));
			if (mmf < this.COOKIE_SHOW_NONE) {
				//menu flag
				$line.prepend('<div id="'+this.ID_MENU+'">NEW</div>');
				//hamburger flag
				if (mmf == this.COOKIE_SHOW_ALL) this.$hamburger.before('<div id="'+this.ID_HAMBURGER+'">1</div>');
			}

			this.addHandlers($line);
		},

		addHandlers: function($line) {
			// FIXME change when micro.tap.js in stable
			if ( M.isBetaGroupMember() ) {
				$( '#mw-mf-main-menu-button' ).on( 'tap', $.proxy(function() {
					this.hamburgerClick();
				},this));
			}
			else {
				$( '#mw-mf-main-menu-button' ).click( $.proxy(function() {
					this.hamburgerClick();
				},this));
			}

			$line.on('click', $.proxy(function() {
				this.menuClick();
			},this));
		},

		hamburgerClick: function() {
			$('#'+this.ID_HAMBURGER).fadeOut($.proxy(function() {
				$.cookie(this.COOKIE_NAME,this.COOKIE_SHOW_MENU);
			},this));
		},

		menuClick: function() {
			$.cookie(this.COOKIE_NAME,this.COOKIE_SHOW_NONE);
		}

	}

	$(document).ready(function() {
		window.WH.MobileMenuFlag.init();
	});

}( mw.mobileFrontend, jQuery ));
