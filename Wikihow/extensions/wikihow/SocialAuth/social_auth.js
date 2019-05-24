/*global mw, jQuery, WH, FB, gapi, civic*/

/**
 * @file Facebook, Google and Civic social authentication
 *
 * Provides a common interface for authentication with external providers and login to wikiHow.
 * Authentication providers include Facebook (WH.social.fb), Google+ (WH.social.gplus) and Civic
 * (WH.social.civic). Each of these endpoints is a function that automatically loads scripts and
 * initializes the provider's API as needed and then returns a promise when the provider is ready to
 * be used.
 *
 * Because some providers use pop-up windows, click handlers must be attached within the resolve
 * handler so the popup is opened in the same call-stack as the direct user-action. Otherwise,
 * authentication pop-up windows will be blocked by most browsers. In most cases, it's best to use
 * the `setupLoginButtons` and `setupAutoLoginButtons` conveince methods, which will add a loading
 * class to the given buttons, initialize the requested providers and setup click handlers for each
 * button to log users in and return them to the given URL.
 *
 * @example
 * WH.social.setupLoginButtons(
 *     {
 *         fb: '#facebookButton', // Accepts CSS selectors
 *         gplus: $container.find( '.google_button' ) // or jQuery selections
 *     },
 *     mw.config.get( 'wgPageName' )
 * );
 *
 * @example
 * WH.social.setupAutoLoginButtons(
 *     {
 *         fb: '#facebookButton', // Accepts CSS selectors
 *         gplus: $container.find( '.google_button' ) // or jQuery selections
 *     },
 *     function () {
 *         // Authentication and login complete
 *     },
 *     function ( error ) {
 *         // Error authenticating or logging into a wikiHow account
 *     }
 * );
 *
 * After initializing a provider, the promise is resolved with an object that has three methods:
 * authenticate, login and autoLogin.
 *
 * The login method calls authenticate (see below) and then logs the user into to a new or existing
 * wikiHow account. It returns a promise that is rejected if authentication fails and otherwise
 * redirects the user to log them into wikiHow which then redirects them to specified title.
 *
 * @example
 * WH.social.fb().then(
 *     function ( fbLogin ) {
 *         $( #login-button ).click( function () {
 *             // Authenticate with Facebook, login to wikiHow and redirect to Main_Page
 *             fbLogin.login( 'Main_Page' ).fail( function ( error ) {
 *                 // Error authenticating
 *             } );
 *         } );
 *     },
 *     function ( error ) {
 *         // Error initializing
 *     }
 * );
 *
 * The autoLogin method also calls authenticate (see below) and then logs the user into a new or
 * existing wikiHow account. However it does so asynchronously and returns a promise that is
 * resolved upon completion.
 *
 * @example
 * WH.social.fb().then(
 *     function ( fbLogin ) {
 *         $( #login-button ).click( function () {
 *             // Authenticate with Facebook and login to wikiHow
 *             fbLogin.autoLogin().then(
 *                 function () {
 *                     // Authentication and login complete
 *                 },
 *                 function ( error ) {
 *                     // Error authenticating or logging into a wikiHow account
 *                 }
 *             } );
 *         );
 *     },
 *     function ( error ) {
 *         // Error initializing
 *     }
 * );
 *
 * The authenticate method presents the user with a provider-supplied authentication dialog and
 * returns a promise that's resolved when the user completes that process. The promise resolves with
 * the provider-specific response, which will differ from provider to provider.
 *
 * @example
 * WH.social.fb().then(
 *     function ( fbLogin ) {
 *         $( #login-button ).click( function () {
 *             fbLogin.authenticate().then(
 *                 function ( response ) {
 *                     // Authentication complete
 *                 },
 *                 function ( error ) {
 *                     // Error authenticating
 *                 },
 *             );
 *         } );
 *     },
 *     function ( error ) {
 *         // Error initializing
 *     }
 * );
 */
( function ( window, document, $ ) {
	'use strict';

	var urls = {
		facebook: 'https://' + window.location.hostname + '/Special:FBLogin',
		google: 'https://' + window.location.hostname + '/Special:GPlusLogin',
		civic: 'https://' + window.location.hostname + '/Special:CivicLogin'
	};

	/**
	 * Initiate the signup/login process by submitting a form to a special page
	 *
	 * @param {string} type Login type (facebook, google, civic, etc.)
	 * @param {string} authToken Authentication token
	 * @param {string} returnTo Page to return to after login
	 */
	function submitLoginForm( type, authToken, returnTo ) {
		if ( returnTo === undefined ) {
			returnTo = '';
		}

		var isGDPR = false;
		if ( WH.gdpr != undefined ) {
			if ( WH.gdpr.isEULocation() ) {
				isGDPR = true;
			}
		}
		$( '<form>' )
			.attr( 'method', 'post' )
			.attr( 'action', urls[type] )
			.attr( 'enctype', 'multipart/form-data' )
			.append( $( '<input name="token" value="' + authToken + '"/>' ) )
			.append( $( '<input name="action" value="login" />' ) )
			.append( $( '<input name="returnTo" value="' + returnTo + '" />' ) )
			.append( $( '<input name="gdpr" value="' + isGDPR + '" />' ) )
			.appendTo( 'body' )
			.submit();
	}

	/**
	 * Perform social signup/login in the background
	 *
	 * @param {string} type Login type (facebook, google, civic, etc.)
	 * @param {string} token Authentication token
	 * @return {jQuery.Promise} Promise resolved when login is complete
	 */
	function queryLoginApi( type, authToken ) {
		var deferred = $.Deferred();
		var isGDPR = false;
		if ( WH.gdpr != undefined ) {
			if ( WH.gdpr.isEULocation() ) {
				isGDPR = true;
			}
		}
		$.ajax( {
			type: 'GET',
			dataType: 'jsonp',
			jsonpCallback: 'wh_jsonp_social',
			url: 'https://' + window.location.hostname + '/Special:SocialLogin?action=login',
			data: { type: type, authToken: authToken, gdpr: isGDPR }
		} ).then(
			function ( response ) {
				deferred.resolve( response );
			},
			function () {
				deferred.reject( 'Login failed' );
			}
		);
		return deferred.promise();
	}

	/**
	 * Report error to console if possible.
	 *
	 * @param {Mixed} Error
	 */
	function reportError( error ) {
		var c = console;
		if ( c.error || c.log ) {
			( c.error || c.log )( 'SocialAuth Error:', error );
		}
	}

	/* Facebook */

	function FBLogin() {
		//
	}

	/**
	 * Initialize Facebook authentication.
	 *
	 * @example
	 * FBLogin.init().then( function ( fbLogin ) { fbLogin.autoLogin(); }  );
	 *
	 * @static
	 * @return {jQuery.Promise<FBLogin>} Promise resolved with login object when initialized
	 */
	FBLogin.init = function () {
		var initializing = FBLogin.initializing;
		if ( !initializing ) {
			FBLogin.initializing = initializing = $.Deferred();
			window.fbAsyncInit = function () {
				FB.init( {
					appId: mw.config.get( 'wgFBAppId' ),
					xfbml: true,
					status: true,
					version: 'v3.3'
				} );
				initializing.resolve( new FBLogin() );
			};
			( function ( d, s, id ) {
				var locale,
					lang = mw.config.get( 'wgUserLanguage' );
				if ( lang == 'en' ) {
					locale = 'en_US';
				} else if ( lang == 'pt' ) {
					locale = 'pt_BR';
				} else {
					locale = lang + '_' + lang.toUpperCase();
				}
				var js, fjs = d.getElementsByTagName( s )[0];
				if ( d.getElementById( id ) ) {
					return;
				}
				js = d.createElement( s );
				js.addEventListener( 'error', function () {
					initializing.reject( 'Failed to load Facebook SDK' );
				} );
				js.id = id;
				js.src = '//connect.facebook.net/' + locale + '/sdk.js';
				fjs.parentNode.insertBefore( js, fjs );
			} ( document, 'script', 'facebook-jssdk' ) );
		}
		return initializing.promise();
	};

	/**
	 * Authenticate with Facebook.
	 *
	 * @return {jQuery.Promise} Promise resolved when login is complete
	 */
	FBLogin.prototype.authenticate = function () {
		var deferred = $.Deferred();
		FB.getLoginStatus( function ( response ) {
			if ( response.status === 'connected' ) {
				deferred.resolve( response );
			} else {
				FB.login(
					function ( response ) {
						if ( response.status === 'connected' ) {
							deferred.resolve( response );
						} else {
							deferred.reject( response );
						}
					},
					{ scope: 'public_profile,email' }
				);
			}
		} );
		return deferred.promise();
	};

	/**
	 * Initiate the Special:FBLogin signup/login process.
	 *
	 * @param {string} returnTo Page to return to after login
	 * @return {jQuery.Promise} Promise rejected if authentication fails
	 */
	FBLogin.prototype.login = function ( returnTo ) {
		return this.authenticate().then(
			function ( response ) {
				submitLoginForm( 'facebook', response.authResponse.accessToken, returnTo );
			},
			function () {
				alert( mw.msg( 'socialauth-fblogin-login-failed' ) );
			}
		);
	};

	/**
	 * Send a post request to our back-end, which will create a new user account if
	 * necessary, and then log the user in.
	 *
	 * @return {jQuery.Promise} Promise resolved when login is complete
	 */
	FBLogin.prototype.autoLogin = function () {
		return this.authenticate().then( function ( response ) {
			return queryLoginApi( 'facebook', response.authResponse.accessToken );
		} );
	};

	/* Google */

	function GPlusLogin( authInstance ) {
		this.authInstance = authInstance;
	}

	/**
	 * Initialize Google+ authentication.
	 *
	 * @example
	 * GPlusLogin.init().then( function ( gPlusLogin ) { gPlusLogin.autoLogin(); }  );
	 *
	 * @static
	 * @return {jQuery.Promise<GPlusLogin>} Promise resolved with login object when initialized
	 */
	GPlusLogin.init = function () {
		var initializing = GPlusLogin.initializing;
		if ( !initializing ) {
			GPlusLogin.initializing = initializing = $.Deferred();
			$.ajax( {
				url: 'https://apis.google.com/js/api:client.js',
				dataType: 'script',
				cache: true
			} ).then(
				function () {
					gapi.load( 'auth2', {
						callback: function () {
							gapi.auth2.init( {
								client_id: mw.config.get( 'wgGoogleAppId' ),
								cookiepolicy: 'http://' + mw.config.get( 'wgCookieDomain' )
							} ).then(
								function() {
									if ( mw.config.get( 'wgUserLanguage' ) == 'ar' ) {
										// Prevent horizontal bar on RTL languages
										var $iframe = $( '#ssIFrame_google' );
										var value = $iframe.css( 'left' );
										$iframe.css( 'left', '' );
										$iframe.css( 'right', value );
									}
									var authInstance = gapi.auth2.getAuthInstance();
									initializing.resolve( new GPlusLogin( authInstance ) );
								},
								function () {
									initializing.reject( 'Failed to initialize Google auth2 API' );
								}
							);
						},
						onerror: function () {
							initializing.reject( 'Failed to load Google auth2 API' );
						},
						timeout: 10000, // 10 seconds.
						ontimeout: function () {
							initializing.reject( 'Loading Google auth2 API timed out' );
						}
					} );
				},
				function () {
					initializing.reject( 'Failed to load Google client API' );
				}
			);
		}
		return initializing.promise();
	};

	/**
	 * Authenticate with Google+.
	 *
	 * @return {jQuery.Promise} Promise resolved when login is complete
	 */
	GPlusLogin.prototype.authenticate = function () {
		return this.authInstance.signIn();
	};

	/**
	 * Initiate the Special:GPlusLogin signup/login process.
	 *
	 * @param {string} returnTo Page to return to after login
	 * @return {jQuery.Promise} Promise rejected if authentication fails
	 */
	GPlusLogin.prototype.login = function ( returnTo ) {
		return this.authenticate().then(
			function ( response ) {
				submitLoginForm( 'google', response.getAuthResponse().id_token, returnTo );
			},
			function () {
				alert( mw.msg( 'socialauth-gpluslogin-login-failed' ) );
			}
		);
	};

	/**
	 * Send a post request to our back-end, which will create a new user account if
	 * necessary, and then log the user in.
	 *
	 * @return {jQuery.Promise} Promise resolved when login is complete
	 */
	GPlusLogin.prototype.autoLogin = function () {
		return this.authenticate().then( function ( response ) {
			return queryLoginApi( 'google', response.getAuthResponse().id_token );
		} );
	};

	/* Civic */

	function CivicLogin( sip ) {
		this.sip = sip;
	}

	/**
	 * Initialize Civic authentication.
	 *
	 * @example
	 * CivicLogin.init().then( function ( civicLogin ) { civicLogin.autoLogin(); }  );
	 *
	 * @static
	 * @return {jQuery.Promise<CivicLogin>} Promise resolved with login object when initialized
	 */
	CivicLogin.init = function () {
		var initializing = CivicLogin.initializing;
		if ( !initializing ) {
			CivicLogin.initializing = initializing = $.Deferred();
			$.ajax( {
				url: 'https://hosted-sip.civic.com/sip/js/civic.sip.min.js',
				dataType: 'script',
				cache: true
			} ).then(
				function () {
					$( 'head' ).append( '<link rel="stylesheet" href="https://hosted-sip.civic.com/sip/css/civic-modal.min.css">' );
					var sip = new civic.sip( { appId: mw.config.get( 'wgCivicAppId' ) } );
					initializing.resolve( new CivicLogin( sip ) );
				},
				function () {
					initializing.reject( 'Failed to load Civic API' );
				}
			);
		}
		return initializing.promise();
	};

	/**
	 * Authenticate with Civic
	 *
	 * @return {jQuery.Promise} Promise resolved when login is complete
	 */
	CivicLogin.prototype.authenticate = function () {
		var deferred = $.Deferred();
		this.sip.on( 'auth-code-received', function ( response ) {
			deferred.resolve( response );
		} );
		this.sip.on( 'user-cancelled', function ( response ) {
			deferred.reject( response );
		} );
		this.sip.signup( { style: 'popup', scopeRequest: this.sip.ScopeRequests.BASIC_SIGNUP });
		return deferred.promise();
	};

	/**
	 * Initiate the Special:CivicLogin signup/login process.
	 *
	 * @param {string} returnTo Page to return to after login
	 * @return {jQuery.Promise} Promise rejected if authentication fails
	 */
	CivicLogin.prototype.login = function ( returnTo ) {
		return this.authenticate().then(
			function ( response ) {
				submitLoginForm( 'civic', response.response, returnTo );
			},
			function () {
				alert( mw.msg( 'socialauth-civiclogin-login-failed' ) );
			}
		);
	};

	/**
	 * Send a post request to our back-end, which will create a new user account if
	 * necessary, and then log the user in.
	 *
	 * @return {jQuery.Promise} Promise resolved when login is complete
	 */
	CivicLogin.prototype.autoLogin = function () {
		return this.authenticate().then( function ( response ) {
			return queryLoginApi( 'civic', response.response );
		} );
	};

	/**
	 * Attach click handlers to a set of login buttons.
	 *
	 * @param {Object.<string,string|jQuery>} buttons Map of a provider/selector or selection pairs
	 * @param {Object.<string,Function>} initHooks Callbacks, keyed by provider name, which accept a
	 *   provider object, to execute when matching provider is initialized
	 * @param {Function} onClick Callback, which accepts a provider object, to execute when provider
	 *   is initialized and button is clicked
	 * @return {jQuery.Promise} Promise resolved when setup is complete
	 */
	function setupButtons( buttons, initHooks, onClick ) {
		var deferred = $.Deferred(),
			steps = 0;
		$.each( buttons, function( key, selector ) {
			steps++;
			if ( key in WH.social ) {
				$( selector ).addClass( 'loading' );
				WH.social[key]().then( function ( provider ) {
					if ( typeof initHooks[key] === 'function' ) {
						initHooks[key]( provider );
					}
					$( selector )
						.removeClass( 'loading' )
						.click( function ( e ) {
							e.preventDefault();
							onClick( provider );
						} );
					steps--;
					if ( !steps ) {
						deferred.resolve();
					}
				}, reportError );
			}
		} );
		return deferred.promise();
	}

	/**
	 * Attach click handlers to a set of login buttons.
	 *
	 * @param {Object.<string,string|jQuery>} buttons Map of a provider/selector or selection pairs
	 * @param {string} returnTo Title to return user to after login
	 * @return {jQuery.Promise} Promise resolved when provider is initialized and setup is complete
	 */
	function setupLoginButtons( buttons, returnTo ) {
		return setupButtons(
			buttons,
			{
				civic: function ( provider ) {
					// Trigger Civic login when an auth-code is received, regardless of whether the
					// button was clicked, because when the mobile Civic app returns to the browser,
					// it will open the same URL that it launched from in a new window, but this
					// time with a uuid param. The hosted civic script will interpret this param and
					// trigger a signup. So to complete the signup, we have to intercept the
					// auth-code-received and redirect when it comes in. Since a redirect is
					// blocking, this will superceed the one triggered by the end of the login
					// process started by clicking the button.
					provider.sip.on( 'auth-code-received', function ( response ) {
						submitLoginForm( 'civic', response.response, returnTo );
					} );
				}
			},
			function ( provider ) {
				provider.login( returnTo );
			}
		);
	}

	/**
	 * Attach click handlers to a set of auto login buttons.
	 *
	 * @param {Object.<string,string|jQuery>} buttons Map of a provider/selector or selection pairs
	 * @param {Function} [done] Callback to execute when login is done
	 * @param {Function} [fail] Callback to execute when login fails
	 * @return {jQuery.Promise} Promise resolved when provider is initialized and setup is complete
	 */
	function setupAutoLoginButtons( buttons, done, fail ) {
		return setupButtons( buttons, {}, function ( provider ) {
			provider.autoLogin().then( done, fail );
		} );
	}

	/* Exports */

	window.WH = window.WH || {};
	WH.social = WH.social || {};
	WH.social.setupLoginButtons = setupLoginButtons;
	WH.social.setupAutoLoginButtons = setupAutoLoginButtons;
	WH.social.fb = FBLogin.init;
	WH.social.gplus = GPlusLogin.init;
	WH.social.civic = CivicLogin.init;

} ( window, document, jQuery ) );
