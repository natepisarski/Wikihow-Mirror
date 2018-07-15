/*global WH, jQuery, mediaWiki, gatTrack, Mustache*/
( function ( $, mw ) {
	'use strict';
	window.WH = window.WH || {};
	window.WH.ProfileBox = {
		url_ajax: '/Special:ProfileBox?type=ajax&pagename=' + mw.config.get( 'wgPageName' ),
		data: {},

		init: function() {
			WH.ProfileBox.addHandlers();
		},

		addHandlers: function() {
			$( '#remove_user_page' ).click( function () {
				WH.ProfileBox.removeUserPage();
				return false;
			} );

			$( '.view_toggle' ).click( function () {
				WH.ProfileBox.viewToggle( this );
				return false;
			} );

			WH.ProfileBox.addFacebookEnableAccountHandler();
			WH.ProfileBox.addGooglePlusDisconnectAccountHandler();
		},

		removeUserPage: function() {
			var conf = confirm(
				'Are you sure you want to permanently remove your ' +
					mw.message( 'profilebox_name' ).text() + '?'
			);
			if ( conf == true ) {
				var url = '/Special:ProfileBox?type=remove';

				$.get( url, function ( data ) {
					gatTrack( 'Profile','Remove_profile','Remove_profile' );
					$( '#profileBoxID' ).hide();
					$( '#pb_aboutme' ).hide();
				} );
			}
			return false;
		},

		viewToggle: function ( obj ) {
			var section = $( obj ).parent().attr( 'id' );
			var section_class = $( obj ).hasClass( 'more' ) ? 'more' : 'less';
			var section_class_other = section_class == 'more' ? 'less' : 'more';
			var data = WH.ProfileBox.dataForSection( section, section_class );

			if ( !data ) {
				$.getJSON(
					WH.ProfileBox.url_ajax + '&element=' + section + '_' + section_class,
					function ( vars ) {
						WH.ProfileBox.data[section][section_class] = vars;
						WH.ProfileBox.render( section, vars );
					}
				);
			}
			else {
				WH.ProfileBox.render( section, data );
			}

			//switch view more/less link
			$( obj ).fadeOut( function () {
				$( this )
					.html( mw.message( 'pb-view' + section_class_other ).text() )
					.removeClass( section_class )
					.addClass( section_class_other )
					.fadeIn();
			} );
		},

		dataForSection: function ( section, section_class ) {
			var data = WH.ProfileBox.data;
			if ( !data[section] ) {
				data[section] = {};
			}
			if ( !data[section][section_class]) {
				data[section][section_class] = '';
			}
			return data[section][section_class];
		},

		render: function ( section, vars ) {
			var template = '{{#.}}' + $( '#' + section + '_item' ).html() + '{{/.}}';
			var htmlString = Mustache.render( unescape( template ), vars );
			var html = $( '<textarea/>' ).html( htmlString ).text();
			$( '#pb-' + section + ' tbody' ).html( html );
		},


		addFacebookEnableAccountHandler: function () {
			if ( WH.social && WH.social.fb ) {
				var $link = $( '#fl_enable_acct' );
				$link.addClass( 'loading' );
				WH.social.fb().then( function ( fbLogin ) {
					$link
						.removeClass( 'loading' )
						.click( function ( e ) {
							e.preventDefault();
							fbLogin.authenticate().then( function ( response ) {
								$( '#dialog-box' ).html( '<img src="/extensions/wikihow/rotate.gif" alt="" />' );
								$( '#dialog-box' ).dialog( {
									width: 750,
									modal: true,
									closeText: 'Close',
									position: '10px',
									// TODO: i18n?
									title: 'Are you sure you want to Enable Facebook Login?'
								} );
								$( '#dialog-box' ).load(
									'/Special:FBLink',
									{ token: response.authResponse.accessToken, a: 'confirm' }
								);
							} );
						} );
				} );
			}
		},

		addGooglePlusDisconnectAccountHandler: function () {
			if ( WH.social && WH.social.gplus ) {
				var $link = $( '#gplus_disconnect' );
				$link.addClass( 'loading' );
				WH.social.gplus().then( function ( gPlusLogin ) {
					$link
						.removeClass( 'loading' )
						.click( function() {
							var confirm = false;
							$('<div></div>')
								.appendTo( 'body' )
								// TODO: i18n?
								.html( 'Are you sure you want to disconnect your Google account from wikiHow?' )
								.dialog( {
									modal: true,
									title: 'Please confirm',
									zIndex: 10000,
									autoOpen: true,
									width: 400,
									resizable: false,
									closeText: 'x',
									buttons: {
										Disconnect: function() {
											confirm = true;
											$( this ).dialog( 'close' );
										},
										Cancel: function() {
											confirm = false;
											$( this ).dialog( 'close' );
										}
									},
									close: function() {
										$( this ).remove();
										if ( confirm ) {
											gPlusLogin.authInstance.disconnect();
											location.href = '/Special:GPlusLogin?disconnect=user';
										}
									}
								} );
						} );
				} );
			}
		}
	};

	$( function () {
		WH.ProfileBox.init();
	} );

}( jQuery, mediaWiki ) );
