
$(document).ready(function(){
	supplementAnimations();
				
	$(document).on('click', '#touchdown', function() {
		var bottomtarget = $('#at_the_bottom');
	
		//get the speed
		var pixelDiff = bottomtarget.offset().top - $(this).offset().top;
		var scrollTime = interpPixelsScrollTime(pixelDiff);

		$('html, body').animate({
			scrollTop: bottomtarget.offset().top
		},scrollTime,'easeInOutExpo');
	});
});

// input is pixels to scroll, output is length of time in milliseconds
function interpPixelsScrollTime(pixels) {
	// linearly interpolate
	// from a range [xa, xb], xa < xb
	// to a range [ya, yb], ya < yb
	// variable: xa <= x <= xb.
	// outputs y: ya <= y <= yb
	var linear = function(xa, xb, ya, yb, x) {
		var diffx = xb - xa;
		var diffy = yb - ya;
		y = ya + diffy * ((x - xa) / diffx);
		return y;
	};
	var x = pixels;
	if (x <= 1000) {         // [-inf,1000] -> 1500
		y = 1500;
	} else if (x <= 1500) {  // [1000,1500] -> [1500,2000]
		y = linear(1000,  1500, 1500, 2000, x);
	} else if (x <= 2500) {  // [1500,2500] -> [2000,2500]
		y = linear(1500,  2500, 2000, 2500, x);
	} else if (x <= 4500) {  // [2500,4500] -> [2500,3000]
		y = linear(2500,  4500, 2500, 3000, x);
	} else if (x <= 8500) {  // [4500,8500] -> [3000,3500]
		y = linear(4500,  8500, 3000, 3500, x);
	} else if (x <= 15000) { // [8500,15000] -> [3500,4000]
		y = linear(8500, 15000, 3500, 4000, x);
	} else {                 // [15000,inf] -> 4000
		y = 4000;
	}
	// ensure formulas and error in floating points
	y = Math.max(1500, y);
	y = Math.min(4000, y);
	return Math.round(y);
}

function supplementAnimations() {
	//add our slick easing
	$.extend($.easing,
	{
		easeInOutQuad: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return c/2*t*t + b;
			return -c/2 * ((--t)*(t-2) - 1) + b;
		},
		easeInOutQuint: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return c/2*t*t*t*t*t + b;
			return c/2*((t-=2)*t*t*t*t + 2) + b;
		},
		easeInOutQuart: function (x, t, b, c, d) {
			if ((t/=d/2) < 1) return c/2*t*t*t*t + b;
			return -c/2 * ((t-=2)*t*t*t - 2) + b;
		},
		easeInOutExpo: function (x, t, b, c, d) {
			if (t==0) return b;
			if (t==d) return b+c;
			if ((t/=d/2) < 1) return c/2 * Math.pow(2, 10 * (t - 1)) + b;
			return c/2 * (-Math.pow(2, -10 * --t) + 2) + b;
		},
	});
}
