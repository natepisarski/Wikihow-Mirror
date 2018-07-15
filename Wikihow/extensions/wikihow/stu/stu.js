var STU_BUILD = '6';

// EXIT TIMER MODULE
WH.Stu = (function () {
	'use strict';

// Make it really clear that we can't use jQuery in this context because it may
// not have been loaded yet. We load Stu as early as possible, earlier than
// jQuery so we can collect more and better stats.
//function $() {
//	alert('jQuery not available in stu');
//}

// Only enable Stu pings if running on English, not on mobile,
// with the view action of an article page. Only enable ping timers on
// production because they require a varnish front end to catch and log them.
var countableView = typeof WH.stuCount != 'undefined' ? WH.stuCount : 0,
	pageLang = typeof WH.pageLang != 'undefined' ? WH.pageLang : '',
	isMobile = typeof WH.isMobile != 'undefined' ? WH.isMobile : 0,
	exitTimerEnabled = countableView && pageLang == 'en' && !isMobile,
	dev = !!(location.href.match(/\.wikidogs\.com/)),
	pingTimersEnabled = !!(location.href.match(/\.wikihow\.(com|cz|jp|it|vn)/)) || dev,
	startTime = false,
	restartTime = false,
	activeElapsed = 0,
	DEFAULT_PRIORITY = 0,
	fromGoogle = 0,
	exitSent = false,
	randPageViewSessionID,
	msieVersion = false,
	currentTimerIndex = 0;

var debugCallbackFunc = null,
	debugQueue = [];

// Project LIA -- logging data on periodic visitors to the site so that we
// can eventually offer to edit articles that might be in their interests.
var timers = [{'t':1}, {'t':10}, {'t':30}, {'t':60}, {'t':120}, {'t':180}];

function makeID(len) {
	var text = '';
	var possible = 'abcdefghijklmnopqrstuvwxyz0123456789';

	for (var i = 0; i < len; i++) {
		text += possible.charAt(Math.floor(Math.random() * possible.length));
	}

	return text;
}

// Get current unix time
function getTime() {
	return +(new Date());
}

function sendRestRequest(u, a) {
	var r = new XMLHttpRequest();
	r.open('GET', u, a);
	r.send();

	pingDebug(u);
}

function sendExitPing(priority, domain, message, doAsync) {
	var loggerUrl = (!dev ? '/Special:Stu' : '/x/devstu') + '?v=6';
	if (priority != DEFAULT_PRIORITY) {
		loggerUrl += '&p=' + priority;
	}
	loggerUrl += '&d=' + domain;
	if (typeof WH.pageID != 'undefined' && WH.pageID > 0 && WH.pageNamespace === 0) {
		loggerUrl += '&pg=' + WH.pageID;
	}
	loggerUrl += '&ra=' + randPageViewSessionID;
	loggerUrl += '&m=' + encodeURI(message);
	loggerUrl += '&b=' + STU_BUILD;
	sendRestRequest(loggerUrl, doAsync);
}

function getDomain() {
	if (fromGoogle) {
		// isMobile defines whether we're on a mobile wikihow domain
		// NOTE: we keep this calculation here rather than using the isMobile
		// defined just outside this scope because the definitions are slightly
		// different and we want to keep this one the same for historical Stu.
		var isMobile = !!(location.href.match(/\bm\./i));
		if (isMobile) {
			return 'vm'; // virtual domain mapping to mb and pv domains
		} else {
			return 'vw'; // virtual domain mapping to bt and pv domains
		}
	} else {
		return 'pv';
	}
}

// Return the stored active time (in activeElapsed) summed with
// the current active timer.
function getCurrentActiveTime() {
	var activeTime = activeElapsed;

	// restartTime may not be set if window was blurred, then closed
	// without being brought to the foreground
	if (restartTime) {
		var viewTime = (getTime() - restartTime);
		if (viewTime > 0) {
			activeTime += viewTime;
		}
	}

	return activeTime;
}

function collectExitTime() {
	var activeTime = getCurrentActiveTime();
	var message = WH.pageName + ' btraw ' + (activeTime / 1000);
	var domain = getDomain();

	// No pinging for IE 6
	if (msieVersion && msieVersion <= 6) {
		return;
	}

	sendExitPing(DEFAULT_PRIORITY, domain, message, false);
}

function onUnload(e) {
	// Flowplayer fires unload events erroneously. We won't call
	// onUnload if triggered by flowplayer elements.
	if ( typeof e !== undefined && typeof e.target !== undefined && typeof e.target.getAttribute !== 'undefined') {
		var target = e.target.getAttribute('id');
		if ( target !== undefined && target.indexOf('whvid-player') !== 0 ) {
			return;
		}
	}

	if (!exitSent) {
		exitSent = true;
		collectExitTime();
	}
}

function onBlur() {
	// Only record the elapsed time since last onblur if there is a period to
	// record.
	if (restartTime) {
		var viewTime = getTime() - restartTime;
		activeElapsed += viewTime;
		restartTime = false;
	}
}

function onFocus() {
	// We make sure not to reset restartTime if it's already been set. This
	// should not happen because onfocus and onblur events should be interleaved
	// but we have seen it occasionally.
	if (!restartTime) {
		restartTime = getTime();
	}
}

function checkFromGoogle() {
	var ref = typeof document.referrer === 'string' ? document.referrer : '';
	// googleSource: try to detect whether user came from google
	var googleSource = !!(ref.match(/^[a-z]*:\/\/[^\/]*google/i));
	// output an int so that it can be sent with pings without conversion
	return googleSource ? 1 : 0;
}

function start() {
	// we set events in IE 9 or less in a different way so it works
	// reference: http://stackoverflow.com/questions/1060008/is-there-a-way-to-detect-if-a-browser-window-is-not-currently-active
	var m = navigator.userAgent.match(/MSIE (\d+)/);
	if (m) {
		msieVersion = m[1];
	}

	// We just set this once for every page session.
	// Note: lg(36^12) = 62 bits of randomization should be enough to avoid collisions
	// almost all the time.
	randPageViewSessionID = makeID(12);

	// Check if user is coming in from Google (since those are the exiting timings
	// we care most about. Set startTime based on the WH.timeStart if it's been
	// set at the top of the page.
	fromGoogle = checkFromGoogle();
	if (typeof WH.timeStart == 'number' && WH.timeStart > 0) {
		startTime = WH.timeStart;
	} else {
		startTime = getTime();
	}

	restartTime = startTime;
	// Use page visibility API to detect whether page is currently visible.
	// If page is not visible at this point, don't start any timers.
	// reference: https://www.w3.org/TR/page-visibility/
	if ('visibilityState' in document && document.visibilityState == 'hidden') {
		restartTime = false;
	}

	// Set timers for Project LIA (Project "Log It All" and let a Norse god sort it out)
	if (pingTimersEnabled) {
		// Setup first timer ping, indexed by currentTimerIndex
		setupNextTimerPing();
	}

	// Use a different event for IE 7-9
	// reference: http://www.thefutureoftheweb.com/blog/detect-browser-window-focus
	if (msieVersion && 7 <= msieVersion && msieVersion <= 9) {
		document.onfocusin = onFocus;
		document.onfocusout = onBlur;
	} else {
		window.onfocus = onFocus;
		window.onblur = onBlur;
	}

	// If we are exit timing this page, set onUnload, onFocus and onBlur event handlers
	if (exitTimerEnabled) {
		window.onunload = onUnload;
		window.onbeforeunload = onUnload;
	}
}

// Ping our servers to collect data about how long the user might have stayed on the page
function eventPing(pingType, stats) {
	// location url of where to ping
	var loc = '/x/collect?t=' + pingType + '&' + stats;
	sendRestRequest(loc, true);
}

function customEventPing(attrs) {
	eventPing( 'event', basicStatsGen(attrs) );
}

// Set up a callback to external stu debug code. Callback is
// called whenever a ping
function registerDebug(func) {
	if (typeof func != 'function') {
		console.log('registerDebug: must be a function');
		return;
	}

	debugCallbackFunc = func;
	for (i = 0; i < debugQueue.length; i++) {
		debugCallbackFunc(debugQueue[i]);
	}
	debugQueue = [];
}

function pingDebug(url) {
	// Only turn on this logging if we think we're in debug mode.
	// See StuInspector.php for the php side of this Stu debug code.
	if (location.href.indexOf('stu=debug') === -1) {
		return;
	}

	if (debugCallbackFunc) {
		debugCallbackFunc(url);
	} else {
		debugQueue.push(url);
	}
}

// Set ping collection timers for the page, based on timers array defined above
// This function uses the currentTimerIndex global and works in conjunction
// with timerPingFunc() to set off these pings or wait for the next one.
function setupNextTimerPing() {
	var diff, currentTime = getTime();

	var timerPingFunc = function () {
		var attrs = { 'ti': timers[currentTimerIndex].t };

		// our first ping should include more stats from the browser, such as view port size
		if (currentTimerIndex === 0) {
			eventPing( 'first', fullStatsGen(attrs) );
		} else {
			eventPing( 'later', basicStatsGen(attrs) );
		}

		currentTimerIndex++;
		if (currentTimerIndex < timers.length) {
			// setup next timer ping, indexed by currentTimerIndex
			setupNextTimerPing();
		}
	};

	if (currentTimerIndex < timers.length) {
		diff = startTime + 1000 * timers[currentTimerIndex].t - currentTime;

		// Send the ping immediately if the time since the page loaded
		// has passed the intended ping time
		if (diff <= 0) {
			timerPingFunc();
		} else {
			setTimeout(timerPingFunc, diff);
		}
	}
}

// spin our own object merging, since it's not in JS until ECMA6, and we
// can't assume access to jQuery yet (to use $.extend). Note: if an
// attribute exists in both object, the o1 version is kept. We don't
// do any deep-copy stuff.
function mergeObjects(o1, o2) {
	for (var key in o2) {
		if (!o2.hasOwnProperty(key)) continue;
		if (typeof o1[key] != 'undefined') continue;
		o1[key] = o2[key];
	}
	return o1;
}

// Generate a few stats only accessible via Javascript in the browser
function fullStatsGen(extraAttrs) {
	// Borrowed and modified from Google's very public analytics.js
	/* I'm disabling flash detection for now -- worried that instantiating these Flash objects
	 * take time.
	function flashDetect() {
		var a, b, c;
		if ((c = (c = window.navigator) ? c.plugins : null) && c.length)
			for (var d = 0; d < c.length && !b; d++) {
				//var e = c[d]; - 1 < e.name.indexOf('Shockwave Flash') && (b = e.description);
				var e = c[d];
				if (-1 < e.name.indexOf('Shockwave Flash')) { b = e.description; }
			}
		if (!b) try {
			//a = new ActiveXObject('ShockwaveFlash.ShockwaveFlash.7'), b = a.GetVariable('$version');
			a = new ActiveXObject('ShockwaveFlash.ShockwaveFlash.7');
			b = a.GetVariable('$version');
		} catch (xx) {}
		if (!b) try {
			//a = new ActiveXObject('ShockwaveFlash.ShockwaveFlash.6'), b = 'WIN 6,0,21,0', a.AllowScriptAccess = 'always', b = a.GetVariable('$version');
			a = new ActiveXObject('ShockwaveFlash.ShockwaveFlash.6');
			b = 'WIN 6,0,21,0';
			a.AllowScriptAccess = 'always';
			b = a.GetVariable('$version');
		} catch (xx) {}
		if (!b) try {
			//a = new ActiveXObject('ShockwaveFlash.ShockwaveFlash'), b = a.GetVariable('$version');
			a = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
			b = a.GetVariable('$version');
		} catch (xx) {}
		//b &&
		//	(a = b.match(/[\d]+/g)) && 3 <= a.length && (b = a[0] + '.' + a[1] + ' r' + a[2]);
		if (b) {
			a = b.match(/[\d]+/g);
			if (a && 3 <= a.length) {
				b = a[0] + '.' + a[1] + ' r' + a[2];
			}
		}
		return b || void 0;
	}
	 */

	// Calculate view port dimensions
	function viewPort() {
		var d = document,
			c = d.documentElement,
			e = d.body,
			g = e && e.clientWidth && e.clientHeight,
			ca = [];
		//c && c.clientWidth && c.clientHeight && ('CSS1Compat' === d.compatMode || !g) ? ca = [c.clientWidth, c.clientHeight] : g && (ca = [e.clientWidth, e.clientHeight]);
		if (c && c.clientWidth && c.clientHeight && ('CSS1Compat' === d.compatMode || !g)) {
			ca = [c.clientWidth, c.clientHeight];
		} else {
			if (g) {
				ca = [e.clientWidth, e.clientHeight];
			}
		}
		c = 0 >= ca[0] || 0 >= ca[1] ? '' : ca.join('x');
		return c;
	}

	try {
		var n = window.navigator,
			d = document,
			sc = window.screen,
			de = d && (d.characterSet || d.charset),
			//fl = flashDetect(),
			//je = n && 'function' === typeof n.javaEnabled && n.javaEnabled() || false,
			ul = (n && (n.language || n.browserLanguage) || '').toLowerCase(),
			sd = sc && sc.colorDepth + '-bit',
			sr = sc && sc.width + 'x' + sc.height,
			vp = viewPort(),
			pr = typeof window.devicePixelRatio != 'undefined' ? window.devicePixelRatio : 0;
		var attrs = {
			'de': de,
			'ul': ul,
			'sd': sd,
			'sr': sr,
			'vp': vp,
			'pr': pr
		};
		// removed for now (java and flash detection):
		//attrs['fl'] = fl;
		//attrs['je'] = (je ? 1 : 0);
		extraAttrs = mergeObjects(extraAttrs, attrs);
		return basicStatsGen(extraAttrs);
	} catch (e) {
		// for debugging
		//console.log('err',e);
	}
	return '';
}

function basicStatsGen(extraAttrs) {
	var totalElapsedSeconds = Math.round( (getTime() - startTime) / 1000.0 );
	var activeElapsedSeconds = Math.round( getCurrentActiveTime() / 1000.0 );

	var attrs = {
		'gg': fromGoogle,
		'to': totalElapsedSeconds,
		'ac': activeElapsedSeconds,
		'pg': WH.pageID,
		'ns': WH.pageNamespace,
		'ra': randPageViewSessionID,
		'cv': countableView,
		'cl': pageLang,
		'cm': isMobile,
		'dl': location.href,
		'b': STU_BUILD
	};

	extraAttrs = mergeObjects(extraAttrs, attrs);

	var encoded = '', first = true;
	for (var key in extraAttrs) {
		if (!extraAttrs.hasOwnProperty(key)) continue;

		encoded += (first ? '' : '&') + key + '=' + encodeURIComponent(extraAttrs[key]);
		first = false;
	}

	return encoded;
}

// Expose WH.Stu.start method
return {
	'start': start,
	'ping': customEventPing,
	'registerDebug': registerDebug
};

})();

// Start stu event handlers here now
WH.Stu.start();
