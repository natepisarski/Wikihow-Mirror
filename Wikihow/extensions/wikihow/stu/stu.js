var STU_BUILD = '6';

// EXIT TIMER MODULE
window['WH'] = window['WH'] || {};
window['WH']['Stu'] = (function () {
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
var WH = window['WH'];
var countableView = WH['stuCount'],
	pageLang = WH['pageLang'],
	isMobile = WH['isMobile'],
	dev = !!(location.href.match(/\.wikidogs\.com/)),
	exitTimerEnabled = (countableView && pageLang == 'en') || dev,
	pingTimersEnabled = (location.href.match(/\.wikihow\.[a-z]+\//) && pageLang == 'en') || dev,
	startTime = false,
	restartTime = false,
	activeElapsed = 0,
	DEFAULT_PRIORITY = 0,
	fromGoogle = 0,
	exitSent = false,
	randPageViewSessionID,
	msieVersion = false,
	currentTimerIndex = 0,
	CONTENT_SECTIONS_SELECTOR = '#intro, .section.steps, #quick_summary_section';

var activityTotalTime = 180.0,

	// this interval is the length of our sample (in time) for
	// how much activity we've seen in terms of user-generated
	// events like scrolling, clicking, etc. it must be > 0.
	ACTIVITY_INTERVAL_SECS = 3,

	activityIntervals = activityTotalTime / ACTIVITY_INTERVAL_SECS,
	activityIntervalCount = 0,
	activitySum = 0,
	activityEvents = 0,
	activityLastActiveTime = 0,

	// this represents the number of hidden sections that we break
	// the article up into, for the purposes of watching user/viewport
	// scroll activity. this value must be > 0.
	ACTIVITY_NUM_BUCKETS = 128,

	// scroll timing buckets
	activityTimings = [],

	// the activityRecalcScrollSectionsLastRun does a couple things: it
	// is used to throttle the scroll handler so that the section
	// length recomputations don't happen more than once every
	// 250ms. we don't want to recompute this more often because
	// it requires DOM lookups, which can be slow. eventually,
	// this value is set to -1, which means that we don't want to
	// do any more recomputation from within the scroll handler.
	activityRecalcScrollSectionsLastRun = 0,
	activityMin = 0, activityMax = 0,
	activityLastActive = 0;

var debugCallbackFunc = null,
	debugQueue = [];

// Project LIA -- logging data on periodic visitors to the site so that we
// can eventually offer to edit articles that might be in their interests.
var timers = [{'t':1}, {'t':10}, {'t':20}, {'t':30}, {'t':45}, {'t':60}, {'t':90}, {'t':120}, {'t':180}, {'t':240}, {'t':300}, {'t':360}, {'t':420}, {'t':480}, {'t':540}, {'t':600} ];

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
}

function sendExitPing(priority, domain, message, doAsync) {
	var stats = basicStatsGen({});
	delete stats['dl']; // we don't send this longer attribute 'dl' with exit pings

	var attrs = {
		'd': domain,
		'm': message,
		'b': STU_BUILD
	};
	if (priority != DEFAULT_PRIORITY) {
		attrs['p'] = priority;
	}

	var loggerUrl = (!dev ? '/Special:Stu' : '/x/devstu') + '?v=' + STU_BUILD;
	loggerUrl += '&' + encodeAttrs(attrs) + '&' + encodeAttrs(stats);

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
	var message = WH['pageName'] + ' btraw ' + (activeTime / 1000);
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
	if ( typeof e !== 'undefined' && typeof e.target !== 'undefined' && typeof e.target.getAttribute !== 'undefined') {
		var target = e.target.getAttribute('id');
		if ( typeof target !== 'undefined' && target.indexOf('whvid-player') !== 0 ) {
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
	// we care most about. Set startTime based on the WH['timeStart'] if it's been
	// set at the top of the page.
	fromGoogle = checkFromGoogle();
	if (typeof WH['timeStart'] == 'number' && WH['timeStart'] > 0) {
		startTime = WH['timeStart'];
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

	// Set onFocus and onBlur event handlers to track "active" time on page
	//
	// Use a different event for IE 7-9
	// reference: http://www.thefutureoftheweb.com/blog/detect-browser-window-focus
	if (msieVersion && 7 <= msieVersion && msieVersion <= 9) {
		document.onfocusin = onFocus;
		document.onfocusout = onBlur;
	} else {
		window.onfocus = onFocus;
		window.onblur = onBlur;
	}

	// do a test where we enable exit timers on mobile for a set of articles
	//var testArticles = [6256, 273369, 2161942, 1215252, 1756524, 86484, 1099813, 703191, 1410426, 1151586, 33060, 1464781, 4063687, 2854494, 3037374, 2660480, 192336, 4458945, 1231084, 2115025, 2850868, 7495, 4019578, 373667, 2188607, 441133, 5868, 4082, 5884688, 25067, 45696, 26479, 237241, 129781, 2723288, 36973, 867321, 175672, 391387, 75604, 381649, 2768446, 1365615, 650388, 13498, 232692, 2053, 1685302, 784172, 47930, 3126454, 23163];
	//if (testArticles.indexOf(WH['pageID']) !== -1) {
	//}

	// If we are exit timing this page, set onUnload (and onBeforeUnload) event handlers
	if (exitTimerEnabled) {
		window.onunload = onUnload;
		window.onbeforeunload = onUnload;
	}

	if (exitTimerEnabled || pingTimersEnabled) {
		addActivityListeners();
	}
}

function activityRecalcScrollSections() {
	var nodes = document.querySelectorAll(CONTENT_SECTIONS_SELECTOR);
	if (!nodes) { return; }
	var min = 1000000, max = 0;
	Array.prototype.forEach.call( nodes, function(i) {
		var start = getYPosition(i);
		var height = getElementHeight(i);
		min = Math.min( start, min );
		max = Math.max( start+height, max );
	});

	// TODO: we could detect if these change here and adjust bucket values if necessary.
	// this isn't a high prio thing to do because we don't expect these numbers to change much,
	// and it's kinda complicated to do.
	activityMin = min;
	activityMax = max;
}

function activityRecordScrollTiming() {
	var activeTime = getCurrentActiveTime();
	var diff = activeTime - activityLastActive;
	activityLastActive = activeTime;
	if (diff <= 0) return;

	var scrollTop = getScrollTop();
	var windowHeight = getViewportHeight();
	var scrollBottom = scrollTop + windowHeight;
	if (scrollTop > activityMax || scrollBottom < activityMin) return;
	var pixelsHeight = activityMax - activityMin;
	if (pixelsHeight <= 0) return;
	var first = Math.floor( ACTIVITY_NUM_BUCKETS * (1.0*scrollTop - activityMin) / pixelsHeight );
	if (first < 0) first = 0;
	var last = Math.ceil( ACTIVITY_NUM_BUCKETS * (1.0*scrollBottom - activityMin) / pixelsHeight );
	if (last > ACTIVITY_NUM_BUCKETS - 1) last = ACTIVITY_NUM_BUCKETS - 1;

	var i = first;
	while (i <= last) {
		if (typeof activityTimings[i] === 'undefined') {
			activityTimings[i] = 0;
		}
		activityTimings[i] += diff;
		i++;
	}
}

// Gives the top of the window's vertical scroll position in the page, in CSS pixels.
// For example, if the page is 2500 CSS pixels tall, the window is 500 CSS pixels tall,
// and the window is scrolled to about the halfway point, this function would return
// about 1000.
//
// https://www.quirksmode.org/mobile/tableViewport.html
function getScrollTop() {
	return window.scrollY || window.pageYOffset;
}

// Gives the window's viewport height in CSS pixels (not device pixels).
//
// https://www.quirksmode.org/mobile/tableViewport.html
function getViewportHeight() {
	return window.innerHeight || document.documentElement.clientHeight;
}

// Gets the Y position (vertical offset) of a DOM element relative to the top of
// the page. This value is in CSS pixels.
//
// Simplified for what we need from:
// https://www.kirupa.com/html5/get_element_position_using_javascript.htm
function getYPosition(el) {
	var yPos = 0;
	while (el) {
		yPos += (el.offsetTop - el.clientTop);
		el = el.offsetParent;
	}
	return yPos;
}

// Gets the height of a DOM element in CSS pixels
function getElementHeight(i) {
	return i.offsetHeight || i.clientHeight;
}

// Added user input event listening for activity metrics
function addActivityListeners() {

	// Feature test for passive event listener support. From:
	// https://github.com/WICG/EventListenerOptions/blob/gh-pages/explainer.md

	// Test via a getter in the options object to see if the passive property is accessed
	var supportsPassive = false;
	try {
		var opts = Object.defineProperty({}, 'passive', {
			get: function() {
				supportsPassive = true;
			}
		});
		window.addEventListener('testPassive', null, opts);
		window.removeEventListener('testPassive', null, opts);
	} catch (e) {}

	window.addEventListener('scroll', function(/*e*/) {
		activityEvents++;

		// we run this at the start of the page load every 250ms, when
		// the setInterval for 3s isn't active yet
		if (activityRecalcScrollSectionsLastRun != -1) {
			// don't allow this method to run more often than every 250ms
			var time = getTime() - startTime;
			if (time >= activityRecalcScrollSectionsLastRun + 250) {
				activityRecalcScrollSectionsLastRun = time;
				activityRecalcScrollSections();
			}
		}

		// record how long viewport stays active over important parts of the page
		activityRecordScrollTiming();
	});

	window.addEventListener('resize', function(/*e*/) { activityEvents++; });
	window.addEventListener('click', function(/*e*/) { activityEvents++; });

	// touchpad events
	var passiveParam = supportsPassive ? { passive: true } : false;
	window.addEventListener('touchstart', function(/*e*/) { activityEvents++; }, passiveParam);
	window.addEventListener('touchend', function(/*e*/) { activityEvents++; });
	window.addEventListener('touchcancel', function(/*e*/) { activityEvents++; });
	window.addEventListener('touchmove', function(/*e*/) { activityEvents++; }, passiveParam);

	// keyboard events
	document.addEventListener('keydown', function(/*e*/) { activityEvents++; });
	document.addEventListener('keyup', function(/*e*/) { activityEvents++; });
	document.addEventListener('keypress', function(/*e*/) { activityEvents++; });

	// every n seconds, update counts to see if there was activity in that interval
	setInterval(function () {
		// stop this from running in scroll handler
		activityRecalcScrollSectionsLastRun = -1;
		activityRecalcScrollSections();

		if (activityEvents > 0) {
			activitySum++;
			activityEvents = 0;
		}

		// if window was not active, we don't count it as an interval
		var activeTime = getCurrentActiveTime();
		if (activeTime > activityLastActiveTime) {
			activityLastActiveTime = activeTime;
			if (activityIntervalCount < activityIntervals) {
				activityIntervalCount++;
			}
		}
	}, 1000 * ACTIVITY_INTERVAL_SECS);
}

// Ping our servers to collect data about how long the user might have stayed on the page
function eventPing(pingType, stats) {
	// location url of where to ping
	var loc = '/x/collect?t=' + pingType + '&' + encodeAttrs(stats);
	sendRestRequest(loc, true);
}

function customEventPing(attrs) {
	// Only send custom pings if either exit or ping timers are enabled.
	// I made this change to make it so that Stu plugins don't send events
	// out of the context of all the events generated.
	if (exitTimerEnabled || pingTimersEnabled) {
		eventPing( 'event', basicStatsGen(attrs) );
	}
}

// Set up a callback to external stu debug code. Callback is
// called whenever a ping
function registerDebug(func) {
	if (typeof func != 'function') {
		console.log('registerDebug: must be a function');
		return;
	}

	debugCallbackFunc = func;
	for (var i = 0; i < debugQueue.length; i++) {
		debugCallbackFunc(debugQueue[i]);
	}
	debugQueue = [];
}

var pingDebugFirst = true;
function pingDebug(line) {

	// debugCallbackFunc only gets set when Stu is in debug mode.
	// See StuInspector.php for the php side of this Stu debug code.
	if (debugCallbackFunc) {
		debugCallbackFunc(line);
		if (pingDebugFirst) {
			setInterval( function() {
				basicStatsGen({});
			}, 1000);
			pingDebugFirst = false;
		}
	} else {
		debugQueue.push(line);
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
	return {};
}

function calcTotalElapsedSeconds() {
	var totalElapsedSeconds = Math.round( (getTime() - startTime) / 1000.0 );
	return totalElapsedSeconds;
}

function calcActiveElapsedSeconds() {
	var activeElapsedSeconds = Math.round( getCurrentActiveTime() / 1000.0 );
	return activeElapsedSeconds;
}

function basicStatsGen(extraAttrs) {

	var wordCount = getWordCount();
	if (wordCount < 250) {
		// guess at it if it's wrong
		wordCount = 1500;
	}

	var attrs = {
		'gg': fromGoogle,
		'to': calcTotalElapsedSeconds(),
		'ac': calcActiveElapsedSeconds(),
		'pg': WH['pageID'],
		'ns': WH['pageNamespace'],
		'ra': randPageViewSessionID,
		'cv': countableView,
		'cl': pageLang,
		'cm': isMobile,
		'dl': location.href,
		'b': STU_BUILD
	};

	attrs = mergeObjects(extraAttrs, attrs);

	if (WH['pageNamespace'] === 0) {
		attrs['a1'] = calcActivityScore(wordCount);
	}

	return attrs;
}

function encodeAttrs(attrs) {
	var encoded = '', first = true;
	for (var key in attrs) {
		if (!attrs.hasOwnProperty(key)) continue;

		encoded += (first ? '' : '&') + key + '=' + encodeURIComponent(attrs[key]);
		first = false;
	}

	return encoded;
}

// Count the words of text (roughly) in all content sections of the page.
//
// https://techstacker.com/posts/jxqYn8vEuPyWK9SYi/vanilla-javascript-count-all-words-on-a-webpage
function getWordCount() {

	// Word count here can contain non-word things, such as the content of <noscript> tags.
	// We're ok with this because these non-words counted as words should be reasonably
	// constant across articles. If there are more images than average, word count might
	// be artificially a little higher, but maybe "reading" time should be a little longer
	// with lots of images too.
	var nodes = document.querySelectorAll(CONTENT_SECTIONS_SELECTOR);
	if (!nodes) { return 0; }

	var wordCount = 0;
	Array.prototype.forEach.call( nodes, function(i) {
		var count = i.textContent.split(/\s/).filter(function(n) { return n !== ''; }).length;
		wordCount += count;
	});

	return wordCount;
}

function calcActivityScore(wordCount) {
	// Activity metric uses the number of user-generated events (such as scroll events,
	// clicks, touches to the screen, keys being pressed, etc) and measures whether there
	// was any activity within (roughly) a series of 3s windows. If there was any activity
	// in the window, the score goes up. We expect activity based on the amount of time
	// that we expect it would take to read on the article, which is based on its word count.
	//
	// We'll say average word count is 1500 words, and we adjust as a ratio upwards
	// (or downwards) if more (or less) words than that. We watch for 180 seconds
	// when there are 1500 words, so we adjust, using the ratio, that number of seconds.
	var ratio = (wordCount / 1500.0);
	activityTotalTime = ratio * 180.0;
	activityIntervals = activityTotalTime / ACTIVITY_INTERVAL_SECS;
	var score1 = Math.round(100.0 * activitySum / activityIntervals);
	if (score1 < 0) score1 = 0;
	if (score1 > 100) score1 = 100;

	// This activity metric is calculated using a sort of heat map about where the
	// user views on the important parts of the article page. All the parts between
	// the intro, quick summary and steps sections are split up into ACTIVITY_NUM_BUCKETS
	// parts, and every moment of activity when the viewport is over these parts
	// is logged. For any given parts, if the user stays <= 1s on that parts, the
	// score for it is 0. Is the user stays >= 10s, the score is 100. The score is
	// the average of the ACTIVITY_NUM_BUCKETS parts.
//	var score2 = 0.0;
//	for (var i = 0; i < ACTIVITY_NUM_BUCKETS; i++) {
//		var ms = 0;
//		if (typeof activityTimings[i] !== 'undefined') {
//			ms = activityTimings[i];
//		}
//		var pct = (ms/1000.0 - 1.0) / (10.0 - 1.0);
//		if (pct < 0.0) pct = 0.0;
//		if (pct > 1.0) pct = 1.0;
//		score2 += pct;
//	}
//	score2 = Math.round(100 * score2 / ACTIVITY_NUM_BUCKETS);

	// Elizabeth wants 100% of score1 now...
	var score = Math.round(score1);
//	pingDebug('<span class="replace_line">score: ' + score + ' = 50% of ' + score1 + ' + 50% of ' + score2 + '</span>');
	return score;
}

// Expose WH['Stu'].start method
return {
	'start': start,
	'ping': customEventPing,
	'registerDebug': registerDebug
};

})();

// Start stu event handlers here now
window['WH']['Stu'].start();
