/*global WH, IncrementalDOM, jsonml2idom*/

/* Exports */

/**
 * Component-based rendering system that incrementally updates the DOM.
 *
 * @author Trevor Parscal <trevorparscal@gmail.com>
 * @license MIT
 *
 * @param {Render.Component} root Root component
 * @param {Element} target DOM element to render into.
 */
WH.Render = function ( root, target ) {
	new Renderer( root, target );
};

/**
 * Create a component.
 *
 * @param {Object} prototype Prototype methods and properties to add
 * @return {Function} Component constructor
 */
WH.Render.createComponent = function ( prototype ) {
	function CreatedComponent( props ) {
		Component.call( this, props );
		typeof this.create === 'function' && this.create();
	}
	return extend( CreatedComponent, Component, prototype );
};

/**
 * Create a context.
 *
 * @param {Object} prototype Prototype methods and properties to add
 * @return {Function} Context constructor
 */
WH.Render.createContext = function ( prototype ) {
	function CreatedContext() {
		Context.call( this );
		typeof this.create === 'function' && this.create();
	}
	return extend( CreatedContext, Context, prototype );
};

/**
 * Parse DOM Element to JSONML.
 *
 * @param {Element} element DOM Element to parse
 * @return {array} List of JSONML elements
 */
WH.Render.parseElement = function ( element ) {
	var i, len;

	if ( element.nodeType === 3 ) {
		return element.data;
	} else if ( element.nodeType === 1 ) {
		// Name
		var name = 'div';
		if ( element.nodeName ) {
			name = element.nodeName.toLowerCase();
		}

		// Attributes
		var attributes = {};
		if ( element.attributes ) {
			for ( i = 0, len = element.attributes.length; i < len; i++ ) {
				var attribute = element.attributes[i];
				attributes[attribute.name] = attribute.value;
			}
		}

		// Children
		var children = [];
		if ( element.childNodes ) {
			for ( i = 0, len = element.childNodes.length; i < len; i++) {
				var node = element.childNodes[i];
				children.push( WH.Render.parseElement( node ) );
			}
		}
		return [ name, attributes ].concat( children );
	}
};

/**
 * Parse HTML string to JSONML.
 *
 * @param {string} html HTML string to parse
 * @return {array} List of JSONML elements
 */
WH.Render.parseHTML = function ( html ) {
	var wrapper = document.createElement( 'span' );
	wrapper.innerHTML = html;
	return WH.Render.parseElement( wrapper ).slice( 2 );
};

/* Functions */

/**
 * Extend constructor.
 *
 * @param {Function} child Child constructor
 * @param {Function} parent Parent constructor
 * @param {Object} prototype Prototype methods and properties to add to child constructor
 * @return {Function} Child constructor
 */
function extend( child, parent, prototype ) {
	// Safety for simply loading files in really old browsers
	if ( Object.create ) {
		child.prototype = Object.create( parent.prototype );
	}
	child.prototype.constructor = child;
	if ( prototype ) {
		for ( var key in prototype ) {
			if ( prototype.hasOwnProperty( key ) ) {
				child.prototype[key] = prototype[key];
			}
		}
	}
	return child;
}

/* Classes */

/**
 * Sync Changeable object.
 *
 * @class {Changeable}
 *
 * @constructor
 * @param {Object} props Initialization properties
 */
function Changeable() {
	this.state = {};
	this.touch = null;
	this.id = Changeable.count++;
}

/**
 * Counter for generating unique ID numbers.
 *
 * @static
 * @type {number}
 */
Changeable.count = 0;

/**
 * Change state.
 *
 * Stores changes to state and executes #touch.
 *
 * @param {Object} changes Changes to shallow merge onto #state
 * @chainable
 */
Changeable.prototype.change = function ( changes ) {
	for ( var key in changes ) {
		this.state[key] = changes[key];
	}
	if ( this.touch ) {
		this.touch( this );
	}
	return this;
};

/**
 * Change properties.
 *
 * Stores changes to props and executes #touch.
 *
 * @param {Object} changes Changes to shallow merge onto #props
 * @chainable
 */
Changeable.prototype.using = function ( changes ) {
	for ( var key in changes ) {
		this.props[key] = changes[key];
	}
	if ( this.touch ) {
		// TODO: should this be used?
		//this.touch( this );
	}
	return this;
};

/**
 * Sync Component.
 *
 * Components are rendered and their state changes bubble-up.
 *
 * @class {Component}
 * @extends {Changable}
 *
 * @constructor
 * @param {Object} props Initialization properties
 */
function Component( props ) {
	Changeable.call( this );
	this.props = props || {};
}

extend( Component, Changeable );

/**
 * Render the component.
 *
 * @param {Object} [context] Rendering context
 * @return {Array} JSONML rendering
 */
Component.prototype.render = function ( context ) {
	return [ 'div' ];
};

/**
 * Handle component having been attached to rendering tree.
 */
Component.prototype.onAttach = function () {};

/**
 * Handle component about to be detached from rendering tree.
 */
Component.prototype.onDetach = function () {};

/**
 * Sync Context.
 *
 * Contexts are not rendered and their state changes trickle-down.
 *
 * @class {Context}
 * @extends {Changable}
 */
function Context() {
	Changeable.call( this );
}

extend( Context, Changeable );

/**
 * Sync Rendererable.
 *
 * There are two types of invalidation that can occur, trickle-down and bubble-up.
 * Trickle-up happens when the touch changer is an instance of {Context} or the renderable has been
 * moved to a new parent, allowing context changes to affect the entire tree downstream of a context
 * object. Bubble-up happens in all other cases, allowing maximum re-use of cached renderings.
 *
 * Renders a component.
 */
function Renderable( component ) {
	this.component = component;
	this.renderer = null;
	this.parent = null;
	this.bindings = { methods: [], callbacks: {} };
	this.cache = null;
	this.bubbleUp = false;
	this.trickleDown = false;
	this.touch = this.touch.bind( this );
}

/**
 * Attach to rendering tree.
 *
 * @param {Renderer} renderer Renderer to attach to
 * @param {Renderable} parent Renderable parent
 */
Renderable.prototype.attach = function ( renderer, parent ) {
	if ( renderer !== this.renderer || parent !== this.parent ) {
		// console.log( 'attach', this.component.constructor.name );
		this.renderer = renderer;
		this.parent = parent;
		this.purge();
		this.component.onAttach();
	}
};

/**
 * Detach from rendering tree.
 */
Renderable.prototype.detach = function () {
	// console.log( 'detach', this.component.constructor.name );
	this.component.onDetach();
	this.renderer = null;
	this.parent = null;
	this.purge();
};

/**
 * Purge rendering cache.
 */
Renderable.prototype.purge = function () {
	// console.log( 'purge', this.component.constructor.name );
	this.cache = null;
	this.bubbleUp = true;
	this.trickleDown = true;
};

/**
 * Request a component to be re-rendered.
 *
 * @param {Changable} changer Object that initiated the change
 */
Renderable.prototype.touch = function ( changer ) {
	// console.log( 'touch', this.component.constructor.name );
	if ( changer instanceof Context ) {
		this.trickleDown = true;
	}
	var renderable = this;
	do {
		renderable.bubbleUp = true;
	} while ( ( renderable = renderable.parent ) );
	if ( this.renderer ) {
		this.renderer.touch();
	}
};

/**
 * Render a component.
 *
 * Caches and re-uses rendering unless cache has been purged or state changes have bubbled-up or
 * trickled-down since last rendering.
 *
 * @param {Object} [context={}] Rendering context
 * @return {Array} JASONML rendering
 */
Renderable.prototype.render = function ( context ) {
	if ( this.trickleDown || this.bubbleUp || !this.cache ) {
		// console.log(
		// 	'render',
		// 	{ td: this.trickleDown, bu: this.bubbleUp, ch: !!this.cache },
		// 	this.component.constructor.name
		// );
		this.cache = this.component.render( context || {} );
		this.bubbleUp = false;
		// this.trickleDown is cleared by renderer after children have been added and rendered
		if ( this.renderer ) {
			this.renderer.stats.misses++;
		}
	} else {
		if ( this.renderer ) {
			this.renderer.stats.hits++;
		}
	}
	return this.cache;
};

/**
 * Bind a method.
 *
 * When called repeatedly with the same method, either by name or reference, the same binding is
 * returned.
 *
 * @param {string|Function} method Method name or functionn
 * @return {Function} Bound function, always identical for the same logical input
 */
Renderable.prototype.bind = function ( method ) {
	if ( typeof method === 'string' ) {
		if ( typeof this.component[method] !== 'function' ) {
			throw new Error( 'Unknown method: ' + method );
		}
		method = this.component[method];
	}
	var index = this.bindings.methods.indexOf( method );
	if ( index === -1 ) {
		index = this.bindings.methods.push( method ) - 1;
		this.bindings.callbacks[index] = method.bind( this.component );
	}
	return this.bindings.callbacks[index];
};

/**
 * Sync Renderer.
 *
 * Renders a tree of renderables.
 *
 * @class
 *
 * @constructor
 * @param {Component} Root component to render
 * @param {Element} target DOM element to render into
 */
function Renderer( root, target ) {

	/* Properties */

	/**
	 * @property {Component} root Root component to render
	 */
	this.root = root;
	/**
	 * @property {Element} target DOM element to render into
	 */
	this.target = target;
	/**
	 * @property {boolean} queued Rendering has been queued
	 */
	this.queued = false;
	/**
	 * @property {Object} renderables List of renderables in either #prev or #next lists
	 */
	this.renderables = {};
	/**
	 * @property {Array} prev List of changeable objects that were used in the last rendering
	 */
	this.prev = [];
	/**
	 * @property {Array} next List of changeable objects that will be used in the next rendering
	 */
	this.next = [];
	/**
	 * @property {Object} Rendering statistics
	 */
	this.stats = {};

	/* Initialization */

	// Bind render method so it can be passed as a callback
	this.render = this.render.bind( this );
	// Initial render
	this.render();
}

/**
 * Request a re-render.
 *
 * Requests are batched and performed asynchronously using requestAnimationFrame.
 */
Renderer.prototype.touch = function () {
	if ( !this.queued ) {
		this.queued = true;
		requestAnimationFrame( this.render, 0 );
	}
};

/**
 * Add a changeable object to the next rendering.
 *
 * If the object is a component, a renderable for it will be automatically updated or generated
 * for it and added to #renderables keyed by the component object and also returned for
 * convenience. If the component was added last rendering and hasn't been moved, adding it again
 * has no side-effects.
 *
 * @param {Changeable} changeable Changeable to add
 * @param {Changeable} [parentChangeable=null] Parent changeable, use null when adding root
 * @return {Renderable|null} Renderable object if changeable is a component
 */
Renderer.prototype.add = function ( changeable, parentChangeable ) {
	if ( changeable instanceof Changeable ) {
		// Move changeable from prev to next
		var index = this.prev.indexOf( changeable );
		if ( index !== -1 ) {
			this.prev.splice( index, 1 );
		}
		this.next.push( changeable );
		// Setup event propagation
		var parentRenderable = parentChangeable && this.renderables[parentChangeable.id];
		if ( changeable instanceof Context ) {
			// Direct touch events at parent renderable
			changeable.touch = parentRenderable ? parentRenderable.touch : null;
		} else if ( changeable instanceof Component ) {
			// Update parent or auto-create renderable
			var renderable = this.renderables[changeable.id];
			if ( !renderable ) {
				renderable = new Renderable( changeable );
				this.renderables[changeable.id] = renderable;
				this.stats.added++;
			}
			// Direct touch events at renderable
			changeable.touch = renderable.touch;
			// Assert heirarchy
			renderable.attach( this, parentRenderable );
			return renderable;
		}
	}

	return null;
};

/**
 * Render component tree.
 *
 * Traverses and renders component tree, updating objects along the way to reflect additions and
 * removals. Updates the DOM in-place by diffing with the previous rendering and
 * patching as needed.
 */
Renderer.prototype.render = function () {
	this.stats.hits = 0;
	this.stats.misses = 0;
	this.stats.added = 0;
	this.stats.deleted = 0;

	this.queued = false;

	// Root
	var renderable = this.add( this.root );
	var context = {};
	var list = renderable.render( context );

	// Traverse and render - breath first
	var stack = [ { renderable: renderable, context: context, list: list } ];
	var current, i, len, changeable, item, child, key, method,
		index = 0;
	while ( ( current = stack[index++] ) ) {
		renderable = current.renderable;
		context = current.context;
		list = current.list;
		for ( i = 0, len = list.length; i < len; i++ ) {
			item = list[i];
			if ( i > 0 && item instanceof Changeable ) {
				// Special objects - handle, then use rendering or discard
				if ( item instanceof Component ) {
					// Add child to this rendering cycle
					child = this.add( item, renderable.component );
					// Trickle-down invalidation
					if ( renderable && renderable.trickleDown ) {
						child.trickleDown = true;
					}
					// Replace child with its rendering
					list[i] = child.render( context );
					// Descend into item in future iteration
					stack.push( { renderable: child, context: context, list: list[i] } );
				} else {
					// Discard non-renderable item from rendering
					delete list[i];
					if ( item instanceof Context ) {
						// Add child to this rendering cycle
						this.add( item, renderable.component );
						// Ammend context prototype chain
						context = Object.create( context );
						for ( key in item.state ) {
							context[key] = item.state[key];
						}
					}
				}
			} else if ( i === 1 && typeof item === 'object' && !Array.isArray( item ) ) {
				// Attributes - Look for events to bind
				var component = renderable.component;
				for ( key in item ) {
					method = item[key];
					if ( key.indexOf( 'on' ) === 0 && typeof component[method] === 'function' ) {
						// Bind "on*" event to matching method
						item[key] = renderable.bind( method );
					}
				}
			} else if ( Array.isArray( item ) ) {
				// Element - Descend into child
				stack.push( { renderable: renderable, context: context, list: item } );
			}
		}
		// Now that all children have been added and rendered, reset trickle-down flag
		renderable.trickleDown = false;
	}

	// Patch DOM
	IncrementalDOM.patch( this.target, jsonml2idom, stack[0].list );

	// Cleanup detached changables (whatever is left in the prev list)
	for ( i = 0, len = this.prev.length; i < len; i++ ) {
		changeable = this.prev[i];
		// Disconnect touch triggering
		changeable.touch = null;
		// Cleanup renderables list
		if ( changeable instanceof Component ) {
			renderable = this.renderables[changeable.id];
			renderable.detach();
			delete this.renderables[changeable.id];
		}
		this.stats.deleted++;
	}

	// Swap lists and prepare for next iteration
	this.prev = this.next;
	this.next = [];

	// console.log( this.stats );
};
