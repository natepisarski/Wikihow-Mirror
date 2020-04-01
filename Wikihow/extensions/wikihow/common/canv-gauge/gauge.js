/*
 * HTML5 Canvas Gauge implementation
 * 
 * This code is subject to MIT license.
 *
 * Copyright (c) 2012 Mykhailo Stadnyk <mikhus@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
 * Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 * @authors: Mykhailo Stadnyk <mikhus@gmail.com>
 *           Chris Poile <poile@edwards.usask.ca>
 */
var Gauge = function( config) {

	/**
	 *  Default gauge configuration
	 */
	this.config = {
		renderTo    : null,
		width       : 200,
		height      : 200,
		title       : false,
		maxValue    : 100,
		minValue    : 0,
		majorTicks  : ['0', '20', '40', '60', '80', '100'],
		minorTicks  : 10,
		strokeTicks : true,
		units       : false,
		valueFormat : { int : 3, dec : 2 },
		glow        : true,
		animation   : {
			delay    : 10,
			duration : 250,
			fn       : 'cycle'
		},
		colors : {
			plate      : '#fff',
			majorTicks : '#444',
			minorTicks : '#666',
			title      : '#888',
			units      : '#888',
			numbers    : '#444',
			needle     : { start : 'rgba(240, 128, 128, 1)', end : 'rgba(255, 160, 122, .9)' }
		},
		highlights  : [{
			from  : 20,
			to    : 60,
			color : '#eee'
		}, {
			from  : 60,
			to    : 80,
			color : '#ccc'
		}, {
			from  : 80,
			to    : 100,
			color : '#999'
		}]
	};

	var
		value     = 0,
		self      = this,
		fromValue = 0,
		toValue   = 0,
		imready   = false
	;

	/**
	 * Sets a new value to gauge and updates the gauge view
	 * 
	 * @param {Number} val  - the new value to set to the gauge
	 * @return {Gauge} this - returns self
	 */
	this.setValue = function( val) {

		fromValue = config.animation ? value : val;

		var dv = (config.maxValue - config.minValue) / 100;

		toValue = val > config.maxValue ?
			toValue = config.maxValue + dv :
				val < config.minValue ?
					config.minValue - dv : 
						val
		;

		value = val;

		config.animation ? animate() : this.draw();

		return this;
	};

	/**
	 * Clears the value of the gauge
	 * @return {Gauge}
	 */
	this.clear = function() {
		value = fromValue = toValue = this.config.minValue;
		this.draw();
		return this;
	};

	/**
	 * Returns the current value been set to the gauge
	 * 
	 * @return {Number} value - current gauge's value
	 */
	this.getValue = function() {
		return value;
	};

	/**
	 * Ready event for the gauge. Use it whenever you
	 * initialize the gauge to be assured it was fully drawn
	 * before you start the update on it
	 * 
	 * @event {Function} onready
	 */
	this.onready = function() {};

	function applyRecursive( dst, src) {
		for (var i in src) {
			// modification by Chris Poile, Oct 08, 2012. More correct check of an Array instance
			if (typeof src[i] == "object" && !(Object.prototype.toString.call( src[i]) === '[object Array]')) {
				if (typeof dst[i] != "object") {
					dst[i] = {};
				}

				applyRecursive( dst[i], src[i]);
			} else {
				dst[i] = src[i];
			}
		}
	};

	applyRecursive( this.config, config);
	config = this.config;
	fromValue = value = config.minValue;

	if (!config.renderTo) {
		throw Error( "Canvas element was not specified when creating the Gauge object!");
	}

	var
		canvas = config.renderTo.tagName ? config.renderTo : document.getElementById( config.renderTo),
		ctx = canvas.getContext( '2d'),
		cache, CW, CH, CX, CY, max
	;

	function baseInit() {
		canvas.width  = config.width;
		canvas.height = config.height;

		cache = canvas.cloneNode( true);
		cctx = cache.getContext( '2d');
		CW  = canvas.width;
		CH  = canvas.height;
		CX  = CW / 2;
		CY  = CH / 2;
		max = CX < CY ? CX : CY;
		
		cache.i8d = false;

		// translate cache to have 0, 0 in center
		cctx.translate( CX, CY);
		cctx.save();

		// translate canvas to have 0,0 in center
		ctx.translate( CX, CY);
		ctx.save();
	};

	// do basic initialization
	baseInit();

	/**
	 * Updates the gauge config
	 *
	 * @param  {Object} config
	 * @return {Gauge}
	 */
	this.updateConfig = function( config) {
        applyRecursive( this.config, config);
        baseInit();
        this.draw();
        return this;
    };

	var animateFx = {
		linear : function( p) { return p; },
		quad   : function( p) { return Math.pow( p, 2); },
		quint  : function( p) { return Math.pow( p, 5); },
		cycle  : function( p) { return 1 - Math.sin( Math.acos( p)); },
		bounce : function( p) {
			return 1 - (function( p) {
				for(var a = 0, b = 1; 1; a += b, b /= 2) {
					if (p >= (7 - 4 * a) / 11) {
						return -Math.pow((11 - 6 * a - 11 * p) / 4, 2) + Math.pow(b, 2);
					}
				}
			})( 1 - p);
		},
		elastic : function( p) {
			return 1 - (function( p) {
				var x = 1.5;
				return Math.pow( 2, 10 * (p - 1)) * Math.cos( 20 * Math.PI * x / 3 * p);
			})( 1 - p);
		}
	};

	var animateInterval = null;

	function _animate( opts) {
		var start = new Date; 

		animateInterval = setInterval( function() {
			var
				timePassed = new Date - start,
				progress = timePassed / opts.duration
			;

			if (progress > 1) {
				progress = 1;
			}

			var animateFn = typeof opts.delta == "function" ?
				opts.delta :
				animateFx[opts.delta]
			;

			var delta = animateFn( progress);
			opts.step( delta);

			if (progress == 1) {
				clearInterval( animateInterval);
			}
		}, opts.delay || 10);
	};

	function animate() {
		animateInterval && clearInterval( animateInterval); // stop previous animation
		var
			path = (toValue - fromValue),
			from = fromValue,
			cfg  = config.animation
		;

		_animate({
			delay    : cfg.delay,
			duration : cfg.duration,
			delta    : cfg.fn,
			step     : function( delta) { fromValue = from + path * delta; self.draw(); }
		});
	};

	// defaults
	ctx.lineCap = "round";

	/**
	 * Drows the gauge. Normally this function should be used to
	 * initally draw the gauge
	 * 
	 * @return {Gauge} this - returns the self Gauge object
	 */
	this.draw = function() {
		if (!cache.i8d) {
			// clear the cache
			cctx.clearRect( -CX, -CY, CW, CH);
			cctx.save();

			var tmp = ctx;
			ctx = cctx;

			cache.i8d = true;
			ctx = tmp;
			delete tmp;
		}

		// clear the canvas
		ctx.clearRect( -CX, -CY, CW, CH);
		ctx.save();

		ctx.drawImage( cache, -CX, -CY, CW, CH);

		if (!Gauge.initialized) {
			var iv = setInterval(function() {
				if (!Gauge.initialized) {
					return;
				}

				clearInterval( iv);

				drawNeedle();

				if (!imready) {
					self.onready && self.onready();
					imready = true;
				}
			}, 10);
		} else {
			drawNeedle();

			if (!imready) {
				self.onready && self.onready();
				imready = true;
			}
		}

		return this;
	};

	/**
	 * Transforms degrees to radians
	 */
	function radians( degrees) {
		return degrees * Math.PI / 180;
	};

	/**
	 * Linear gradient
	 */
	function lgrad( clrFrom, clrTo, len) {
		var grad = ctx.createLinearGradient( 0, 0, 0, len);  
		grad.addColorStop( 0, clrFrom);  
		grad.addColorStop( 1, clrTo);

		return grad;
	};

	function padValue( val) {
		var
			cdec = config.valueFormat.dec,
			cint = config.valueFormat.int
		;
		val = parseFloat( val);

		var n = (val < 0);

		val = Math.abs( val);

		if (cdec > 0) {
			val = val.toFixed( cdec).toString().split( '.');
	
			for (var i = 0, s = cint - val[0].length; i < s; ++i) {
				val[0] = '0' + val[0];
			}

			val = (n ? '-' : '') + val[0] + '.' + val[1];
		} else {
			val = Math.round( val).toString();

			for (var i = 0, s = cint - val.length; i < s; ++i) {
				val = '0' + val;
			}

			val = (n ? '-' : '') + val
		}

		return val;
	};

	function rpoint( r, a) {
		var 
			x = 0, y = r,

			sin = Math.sin( a),
			cos = Math.cos( a),

			X = x * cos - y * sin,
			Y = x * sin + y * cos
		;

		return { x : X, y : Y };
	};

	// drows the gauge needle
	function drawNeedle() {
		var
			r1 = max / 100 * 12,
			r2 = max / 100 * 8,

			rIn  = max / 100 * 87,
			rOut = max / 100 * 5,
			pad1 = max / 100 * 4,
			pad2 = max / 100 * 2,

			shad = function() {
				ctx.shadowOffsetX = 2;
				ctx.shadowOffsetY = 2;
				ctx.shadowBlur    = 10;
				ctx.shadowColor   = 'rgba(188, 143, 143, 0.45)';
			}
		;

		ctx.save();

		shad();
		
		if (fromValue < 0) {
			fromValue = Math.abs(config.minValue - fromValue);
		} else if (config.minValue > 0) {
			fromValue -= config.minValue
		} else {
			fromValue = Math.abs(config.minValue) + fromValue;
		}

		ctx.rotate( radians( 90 + fromValue / ((config.maxValue - config.minValue) / 180)));

		ctx.beginPath();
		ctx.moveTo( -pad2, -rOut);
		ctx.lineTo( -pad1, 0);
		ctx.lineTo( -1, rIn);
		ctx.lineTo( 1, rIn);
		ctx.lineTo( pad1, 0);
		ctx.lineTo( pad2, -rOut);
		ctx.closePath();

		ctx.fillStyle = lgrad(
			config.colors.needle.start,
			config.colors.needle.end,
			rIn - rOut
		);
		ctx.fill();

		ctx.beginPath();
		ctx.lineTo( -0.5, rIn);
		ctx.lineTo( -1, rIn);
		ctx.lineTo( -pad1, 0);
		ctx.lineTo( -pad2, -rOut);
		ctx.lineTo( pad2 / 2 - 2, -rOut);
		ctx.closePath();
		ctx.fillStyle = 'rgba(255, 255, 255, 0.2)';
		ctx.fill();

		ctx.restore();

		shad();

		/*ctx.beginPath();
		ctx.arc( 0, 0, r1, 0, Math.PI * 2, true);
		ctx.fillStyle = lgrad( '#f0f0f0', '#ccc', r1);
		ctx.fill();

		ctx.restore();

		ctx.beginPath();
		ctx.arc( 0, 0, r2, 0, Math.PI * 2, true);
		ctx.fillStyle = lgrad( "#e8e8e8", "#f5f5f5", r2);
		ctx.fill();*/
	};

};

// initialize
Gauge.initialized = false;
(function(){
	var
		d = document,
		r = d.createElement( 'style')
	;

	r.type = 'text/css';

	var iv = setInterval(function() {
		if (!d.body) {
			return;
		}

		clearInterval( iv);

		var dd = d.createElement( 'div');

		dd.style.fontFamily = 'Led';
		dd.style.position   = 'absolute';
		dd.style.height     = dd.style.width = 0;
		dd.style.overflow   = 'hidden';

		dd.innerHTML = '.';

		d.body.appendChild( dd);

		setTimeout(function() { // no other way to handle font is rendered by a browser
			                    // just give the browser around 250ms to do that :(
			Gauge.initialized = true;
			dd.parentNode.removeChild( dd);
		}, 250);
	}, 1);
})();
