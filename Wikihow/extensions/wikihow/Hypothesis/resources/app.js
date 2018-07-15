/**
 * The MIT License (MIT)
 * 
 * Copyright (c) 2017 Trevor Parscal
 */

/**
 * Simple client-side app framework for wikiHow special pages
 *
 * @license MIT
 * @author Emmanuel Vaïsse
 *
 * @class
 *
 * @constructor
 * @param {sring} CSS selector for app container
 */
function App( sel ) {
	/* Properties */

	/**
	 * @property {jQuery} Selection of the ap container
	 */
	this.$ = $( sel );
	/**
	 * @property {Object.<string,Object>} Map of route configurations keyed by path
	 */
	this.routes = {};
	/**
	 * @property {string} Current path
	 */
	this.path = '';
	/**
	 * @property {Promise|null} Promise resolved when not busy anymore
	 */
	this.busy = null;
	/**
	 * @property {RouteExit} Callback for exiting current route
	 */
	this.exit = null;
	/**
	 * @property {mw.Api} MediaWiki API
	 */
	this.api = new mw.Api();
}

/* Methods */

/**
 * Start app.
 */
App.prototype.start = function() {
	var app = this,
		path = slashify( location.hash.substr( 1 ) );

	$( window ).on( 'hashchange', function () {
		var hash = '#' + app.path;
		// Strict difference
		if ( hashify( hash ) !== hashify( location.hash ) ) {
			// Fuzzy difference
			if ( slashify( hash ) !== slashify( location.hash ) ) {
				// Go to the new location
				app.go( slashify( location.hash.substr( 1 ) ) );
			} else {
				// Adjust the url
				history.replaceState( undefined, undefined, slashify( location.hash ) )
			}
		}
	} );

	return this.go( path );
};

/**
 * Get data from the API.
 *
 * @param {string} action API action
 * @param {Object} params Parameters to pass to API
 */
App.prototype.get = function( action, params ) {
	var key;
	params = params || {};
	params.action = action;
	return this.api.get( params ).then( function ( response ) {
		return response.query[action];
	} );
};

/**
 * Post data to the API.
 *
 * @param {string} action API action
 * @param {Object} params Parameters to pass to API
 */
App.prototype.post = function( action, params ) {
	params = params || {};
	params.action = action;
	return this.api.postWithToken( 'edit', params ).then( function( response ) {
		return response[action];
	} );
};

/**
 * @callback RouteEntry
 * @param {Object} [params] Parameters to pass to route
 * @return {Promise|undefined} Promise resolved upon completion of entry
 */

/**
 * @callback RouteExit
 * @return {Promise|undefined} Promise resolved upon completion of exit
 */

/**
 * Mount a route at a path.
 *
 * @param {string} action API action
 * @param {Object|RouteEntry} config Route configuration or callback for entering route
 * @param {RouteEntry} [config.enter] Callback for entering route
 * @param {RouteExit} [config.exit] Callback for exiting route
 */
App.prototype.mount = function ( path, config ) {
	if ( typeof config === 'function' ) {
		config = { enter: config };
	}
	this.routes[path] = config;

	return this;
};

/**
 * Go to a route.
 *
 * @param {string} path Path to go to
 */
App.prototype.go = function( path ) {
	var key, params, route,
		app = this;

	path = unslashify( path );
	for ( key in this.routes ) {
		params = RouteParser.parse( key ).exec( path );
		if ( params ) {
			route = this.routes[key];
			break;
		}
	}

	return $.when( app.busy ).then( function () {
		return app.busy = $.when( app.exit && app.exit() ).then( function () {
			app.busy = null;
			app.exit = null;
			app.path = path;
			location.hash = '#' + path;
			if ( route ) {
				return $.when( route.enter && route.enter( params || {} ) ).then( function () {
					app.exit = route.exit;
				} );
			} else {
				app.$.append( '<h2>Not found</h2><a href="#/">Return to index</a>' );
			}
		} );
	} );
};

/**
 * Add a trailing slash if not present
 *
 * @param {string} Path to add trailing slash to
 */
function slashify( path ) {
	return path !== '/' && path.substr( -1 ) !== '/' ? path + '/' : path;
}

/**
 * Remove a trailing slash if present
 *
 * @param {string} Path to remove trailing slash from
 */
function unslashify( path ) {
	return path !== '/' &&  path.substr( -1 ) === '/' ? path.substr( 0, path.length - 1 ) : path;
}

/**
 * Add a leading hash if not present
 *
 * @param {string} Path to add trailing slash to
 */
function hashify( path ) {
	return path.substr( 0, 1 ) !== '#' ? '#' + path : path;
}

/**
 * Add a leading hash if not present
 *
 * @param {string} Path to add trailing slash to
 */
function unhashify( path ) {
	return path.substr( 0, 1 ) === '#' ? path.substr( 1 ) : path;
}

/**
 * Small & consise route parser
 *
 * @license MIT
 * @author Emmanuel Vaïsse
 * @see https://github.com/evaisse/meteor-route-parser
 *
 * @namespace
 */
var RouteParser = {
	_namedParam: /(:(\w+))/g,
	_splatParam: /\*/g,
	_groups: /\((.+)\)/g,
	_smash: /(\(|:\w+|\*)/g,
};

/**
 * Parse a route string
 *
 * @param {string} route Route pattern, /foo, /foo/:bar, /home/(:bar/(:id/*-*))
 * @param {Object} constrains Declare optionnal regex constrains, { id: /\d+/ }
 * @return {Object|false} A struct of route object
 */
RouteParser.parse = function ( route, constrains ) {
	var regex, sub,
		params = [],
		i = 0,
		n = 0;

	function isRegExp( regex ) {
		return ( Object.prototype.toString.call( regex ).indexOf( 'RegExp' ) > 0 );
	};

	constrains = constrains || {};
	regex = route;
	// If route is a pattern, we build from the pattern
	if ( !isRegExp( regex ) ) {
		params = regex.match( RouteParser._smash );
		params = params ? params : [];
		// Create sequence of patterns matchs list, to later assign named groups, positionnal param
		// & discard optionnal virtual groups
		params = params.map( function ( v ) {
			if ( v == '(' ) {
				return null;
			} else if ( v == '*' ) {
				return i++; // positionnal arg
			} else {
				return v.substr( 1 );
			}
		} );
		// Make every non-splat, non-named group optionnal
		regex = regex.replace( /\)/g, ')?' );
		// Detect named parameters & assign pattern constrains
		regex = regex.replace( RouteParser._namedParam, function ( a ) {
			var constrain = constrains[ a.substr( 1 ) ];
			// We check if there is given constrain for named parameter
			if ( constrain ) {
				if ( isRegExp( constrain ) ) {
					// Insert the regexp constrain into the
					constrain = constrain + '';
					return '(' + constrain.replace( /^\//, '' ).replace( /(\/\w*)$/, '' ) + ')';
				} else {
					// insert raw constrain
					return '(' + constrain + ')';
				}
			} else {
				// default named parameter constrain
				return '([^\/]+)';
			}
		});
		// Detect "splat" params
		regex = regex.replace( RouteParser._splatParam, '(.+)' );
		// Build final regex
		regex = new RegExp( '^' + regex + '$' );
	} else {
		params = null;
	}

	return {
		route: route,
		regex: regex,
		exec: function ( url ) {
			var p = regex.exec(url),
				res = {};

			if ( !p ) {
				return false;
			}
			p.slice( 1 ).forEach( function ( v, i ) {
				if ( !params ) {
					res[i] = v === undefined ? null : v;
				} else if ( params[i] !== null ) {
					res[params[i]] = v === undefined ? null : v;
				}
			} );

			return res;
		}
	};
};

/* Exports */

WH.App = App;
