(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.BotBlockIPWhitelist = {
		getNow : Date.now || function() { return new Date().getTime(); },
		startTime : null,
		articleVisible: false,
		tool: '/Special:BotBlockIPWhitelist',

		initEventHandlers: function( obj ) {

			$( document ).on( 'click', '#new_ipwl_btn', function( event ) {
				var payload = {};

				payload = {
					action: 'submit',
					ipwl_addr: document.getElementById( "input_ip_wl" ).value
				};
				WH.BotBlockIPWhitelist.save( payload, function( response ) {
					console.log( 'result from server', response );
				} );

				return false;
			});

			$( document ).on( 'click', '#viewall_ipwl_btn', function( event ) {
				var x = document.getElementById( "ipwl_list" );
				x.style.visibility = "visible";
			});

		},

		init: function() {
			WH.xss.addToken();

			this.startTime = this.getNow();
			this.initEventHandlers( this );
		},

		save: function ( payload, callback ) {
			$.post( this.tool, payload, null, 'json' ).done( function( text ) {
				if( text.response == 1 ) {
					window.location.reload();
					alert( 'Inserted IP into the Whitelist DB.' );
				} else {
					window.location.reload();
					alert( 'Error. Failed to insert into the DB. Please verify that the IP address is correct.' );
				}
			});

		},
	};

	$( document ).ready( function() {
		WH.BotBlockIPWhitelist.init();
	});

})();
