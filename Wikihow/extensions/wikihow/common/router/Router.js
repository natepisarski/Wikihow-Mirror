( function () {
	/**
	 * @callback RouteCallback
	 * @param {Object} [params] Parameters to pass to route
	 */

	/**
	 * Route.
	 *
	 * @license MIT
	 * @author Trevor Parscal
	 *
	 * @class
	 *
	 * @constructor
	 * @param {string} route Route pattern, e.g. /foo, /foo/:bar, /home/(:bar/(:id/*-*))
	 * @param {Object} constraints Declare optionnal regex constraints, e.g. { id: /\d+/ }
	 * @param {RouteCallback} callback Function to call when route is entered
	 */
	function Route( pattern, constraints, callback ) {
		/**
		 * Execute route.
		 *
		 * @method
		 */
		this.execute = callback;
		/**
		 * Match against a pathname.
		 *
		 * @return {Object|null} Map of parameters extracted from pathname or null if pathname did not
		 *   match the pattern and constraints
		 */
		this.match = parse( pattern, constraints );
	}

	/**
	 * Router.
	 *
	 * @license MIT
	 * @author Trevor Parscal
	 *
	 * @class
	 *
	 * @constructor
	 */
	function Router( root ) {
		/**
		 * @property {string} Common root path to handle routes from
		 */
		this.root = root || '';
		/**
		 * @property {Object.<string,Function>} Map of route callbacks keyed by path
		 */
		this.routes = [];
		/**
		 * @property {Object.<string,Function>} Map of error callbacks keyed by symbolic name
		 */
		this.errors = {
			'route-not-found': function ( params ) {
				throw params.error;
			},
			'route-error': function ( params ) {
				throw params.error;
			}
		};

		// Bind event handlers
		this.onClick = onClick.bind( this );
		this.onPopState = onPopState.bind( this );
	}

	/**
	 * Generate a link to a subpath within the router.
	 *
	 * @param {string} subpath Subpath to generate link for.
	 * @return {string} Full pathname to route
	 */
	Router.prototype.link = function ( subpath ) {
		// Assert leading forward-slash
		if ( subpath[0] !== '/' ) {
			subpath = '/' + subpath;
		}
		return this.root + subpath;
	};

	/**
	 * Start router.
	 *
	 * @chainable
	 */
	Router.prototype.start = function () {
		document.body.addEventListener( 'click', this.onClick );
		window.addEventListener( 'popstate', this.onPopState );
		this.go( document.location.pathname, true );
		return this;
	};

	/**
	 * Stop router.
	 *
	 * @chainable
	 */
	Router.prototype.stop = function () {
		document.body.removeEventListener( 'click', this.onClick );
		window.removeEventListener( 'popstate', this.onPopState );
		return this;
	};

	/**
	 * Mount a route callback.
	 *
	 * @param {string} pattern Pattern to match subpath against, e.g. /foo, /foo/:bar,
	 *   /home/(:bar/(:id/*-*))
	 * @param {Object} [constraints] Declare optionnal regex constraints, e.g. { id: /\d+/ }
	 * @param {RouteCallback} callback Function to call when route is entered
	 * @throws {Error} If pattern is not a string
	 * @throws {Error} If callback is not a function
	 * @chainable
	 */
	Router.prototype.mount = function ( pattern, constraints, callback ) {
		if ( typeof pattern !== 'string' ) {
			throw new Error( 'Route pattern must be a string.' );
		}
		if ( typeof constraints === 'function' ) {
			callback = constraints;
			constraints = {};
		}
		if ( typeof callback !== 'function' ) {
			throw new Error( 'Route callback must be a function.' );
		}
		this.routes.push( new Route( pattern, constraints, callback ) );
		return this;
	};

	/**
	 * @callback ErrorCallback
	 * @param {Object} [params] Error to be handled
	 */

	/**
	 * Mount an error callback.
	 *
	 * @param {string} error Error name, e.g. "route-error" or "route-not-found"
	 * @param {ErrorCallback} callback Function to call to handle error
	 * @throws {Error} If error is not recognized
	 * @throws {Error} If callback is not a function
	 * @chainable
	 */
	Router.prototype.handle = function ( error, callback ) {
		if ( !( error in this.errors ) ) {
			throw new Error( 'Error name is not recognized.' );
		}
		if ( typeof callback !== 'function' ) {
			throw new Error( 'Route callback must be a function.' );
		}
		this.errors[error] = callback;
		return this;
	};

	/**
	 * Go to a route.
	 *
	 * @param {string} pathname Pathname of route to go to
	 */
	Router.prototype.go = function ( pathname, replace ) {
		var i, len, subpath, target, origin, route, params;
		if ( pathname.indexOf( this.root ) === 0 ) {
			origin = normalize( document.location.pathname );
			target = normalize( pathname );
			subpath = target.substr( this.root.length );
			for ( i = 0, len = this.routes.length; i < len; i++ ) {
				route = this.routes[i];
				params = route.match( subpath || '/' );
				if ( params ) {
					route.execute( params );
					if ( !replace && origin !== target ) {
						history.replaceState( capture(), '', origin );
						history.pushState( null, '', target );
						requestAnimationFrame( restore );
					} else {
						history.replaceState( history.state, '', target );
					}
					return;
				}
			}
		}

		throw 'route-not-found';
	};

	/* Helper Functions */

	/**
	 * Handle document body clicks.
	 *
	 * @param {Event} event Document mouse-click event
	 */
	function onClick( event ) {
		var tag = event.target;
		// Search up the DOM for an A tag that may have triggered the click
		while ( tag.tagName !== 'A' && tag.parentElement ) {
			tag = tag.parentElement;
		}
		if ( tag.tagName == 'A' && tag.href && event.button == 0 ) {
			var pathname = tag.pathname;
			if ( tag.origin == document.location.origin && pathname.indexOf( this.root ) == 0 ) {
				try {
					this.go( pathname );
				} catch ( error ) {
					var key = error === 'route-not-found' ? error : 'route-error';
					this.errors[key]( error );
				}
				event.preventDefault();
				return false;
			}
		}
	}

	/**
	 * Handle window pop-state events.
	 *
	 * @param {Event} event Window pop-state event
	 */
	function onPopState( event ) {
		this.go( document.location.pathname, true );
		requestAnimationFrame( restore );
		event.preventDefault();
	}

	/**
	 * Normalize pathname.
	 *
	 * Strips trailing slash from the end.
	 *
	 * @param {string} path Pathname to normalize
	 * @return {string} Normalized pathname
	 */
	function normalize( pathname ) {
		return pathname !== '/' &&  pathname.substr( -1 ) === '/' ?
			pathname.substr( 0, pathname.length - 1 ) : pathname;
	}

	/**
	 * Check if a value is a regular expression.
	 *
	 * @param {Mixed} value Value to check
	 * @return {boolean} Value is a regular express
	 */
	function isRegExp( value ) {
		return ( Object.prototype.toString.call( value ).indexOf( 'RegExp' ) > 0 );
	}

	/**
	 * Parse pattern and constraints into a matching function.
	 *
	 * @license MIT
	 * @author Emmanuel Vaïsse
	 * @see https://github.com/evaisse/meteor-route-parser
	 *
	 * @param {string} route Route pattern, e.g. /foo, /foo/:bar, /home/(:bar/(:id/*-*))
	 * @param {Object} constraints Declare optionnal regex constraints, e.g. { id: /\d+/ }
	 * @return {Function} Pattern matching function
	 */
	function parse( pattern, constraints ) {
		constraints = constraints || {};
		var regex = pattern,
			params = [];
		// If pattern isn't a regular expression yet, we generate one from the pattern string
		if ( !isRegExp( regex ) ) {
			params = regex.match( /(\(|:\w+|\*)/g );
			params = params ? params : [];
			// Create sequence of patterns matchs list, to later assign named groups, positionnal
			// param & discard optionnal virtual groups
			var i = 0;
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
			// Detect named parameters & assign pattern constraints
			regex = regex.replace( /(:(\w+))/g, function ( a ) {
				var constraint = constraints[ a.substr( 1 ) ];
				// We check if there is given constraint for named parameter
				if ( constraint ) {
					if ( isRegExp( constraint ) ) {
						// Insert the regexp constraint into the
						constraint = constraint + '';
						return '(' + constraint.replace( /^\//, '' )
							.replace( /(\/\w*)$/, '' ) + ')';
					} else {
						// insert raw constraint
						return '(' + constraint + ')';
					}
				} else {
					// default named parameter constraint
					return '([^/]+)';
				}
			});
			// Detect "splat" params
			regex = regex.replace( /\*/g, '(.+)' );
			// Build final regex
			regex = new RegExp( '^' + regex + '$' );
		} else {
			params = null;
		}
		return function ( url ) {
			// Ignore query string - TODO: maybe parse query string and pass as params?
			url = url.replace( /\?+.*$/, '' );
			var p = regex.exec( url ),
				res = {};
			if ( p ) {
				p.slice( 1 ).forEach( function ( v, i ) {
					if ( !params ) {
						res[i] = v === undefined ? null : v;
					} else if ( params[i] !== null ) {
						res[params[i]] = v === undefined ? null : v;
					}
				} );
				return res;
			}
			return null;
		};
	}

	/**
	 * Capture page state.
	 *
	 * @return {Object} Page state
	 */
	function capture() {
		return { pageYOffset: pageYOffset };
	}

	/**
	 * Restore page state.
	 *
	 * @param {Object} state Page state
	 */
	function restore() {
		var val = history.state && 'pageYOffset' in history.state ? history.state.pageYOffset : 0;
		window.scrollTo( 0, val );
	}

	/* Exports */

	window.WH.Router = Router;

} ) ();
