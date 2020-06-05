(function(mw, $) {

'use strict';

/* Common js for both our desktop and mobile sites
 * it is now loaded by the resource loader in it's own module
 * it is loaded before the javascript in whjs (groups config)
 */
window.WH = window.WH || {};
WH.isMobileDomain = window.location.hostname.match(/\bm(-|\.)/) !== null;
WH.isAndroidAppRequest = window.location.search.match(/wh_an=1/) !== null;

WH.ga = {};

/**
 * Used to track events. If Google Analytics is not initialized when this function is called,
 * then no event will be sent.
 *
 * @param String  category   The name you supply for the group of objects you want to track.
 * @param String  action     Commonly used to define the type of user interaction for the web object.
 * @param String  label      An optional string to provide additional dimensions to the event data.
 * @param String  value      An integer that you can use to provide numerical data about the user event.
 * @param boolean nonInter   When true, the event hit will not be used in bounce-rate calculation.
 */
WH.ga.sendEvent = function(category, action, label, value, nonInter, hitCallbackFunction) {
	if (typeof(ga) == typeof(Function)) {
		var fieldName = {
			nonInteraction: nonInter ? 1 : 0
		};
		if (hitCallbackFunction) {
			fieldName.hitCallback = hitCallbackFunction;
		}
		if (typeof value != 'undefined' && value !== null) {
			ga('send', 'event', category, action, label, value, fieldName);
		} else if (typeof(label) != 'undefined' && label !== null) {
			ga('send', 'event', category, action, label, fieldName);
		} else {
			ga('send', 'event', category, action, fieldName);
		}
	}
};

/**
 * Load Google Analytics
 * @param  String siteVersion Either 'desktop' or 'mobile'
 */
WH.ga.loadGoogleAnalytics = function(siteVersion, propertyId, config) {
	if (window._ga_preloaded) { // Temporary hack - see CommonModules.body.php
		return;
	}
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m);
	})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

	// Do the main GA ping
	ga('create', propertyId, 'auto', { 'allowLinker': true });
	ga('linker:autoLink', [/^.*\.wikihow\.(com|cz|it|jp|vn|com\.tr)$/]);
	ga('send', 'pageview');
	ga('set', 'anonymizeIp', true);
	// ... and extra events if we got any
	for (var id in config.extraPropertyIds) {
		var name = config.extraPropertyIds[id];

		ga('create', {
			trackingId: id,
			cookieDomain: 'auto',
			name: name,
			allowLinker: true
		});

		ga(name + '.send', 'pageview');

		// Adjusted bounce rate (https://moz.com/blog/adjusted-bounce-rate)

		var abrCnf = config.adjustedBounceRate;
		if (abrCnf && $.inArray(id, abrCnf.accounts) !== -1) {
			(function setABRtimeout(category, action, timeout) {
				setTimeout(function() {
					// Don't trigger the event unless the current browser tab is active
					if (typeof document.hidden !== 'undefined' && document.hidden) {
						setABRtimeout(category, action, timeout);
					} else {
						ga(name + '.send', 'event', category, action);
					}
				}, timeout);
			})(abrCnf.eventCategory, abrCnf.eventAction, abrCnf.timeout * 1000);
		}
	}

	//ping Optimize if we should
	if(WH.useOptimize == 1) {
		ga('create', 'UA-2375655-30', 'auto');
		ga('require', 'GTM-MDDNK99');
		ga('send', 'pageview');
	}

	//var load_t_delta = (new Date()).getTime() - WH.timeStart;
	//setTimeout('ga("send","event","Adjusted Bounce","60 seconds on page (' + siteVersion + ')")',
		//Math.max(60000 - load_t_delta, 0));
	//var pv_user_type = mw.config.get('wgUserName') === null ? 'anon' : 'logged_in';
	//ga('send', 'event', 'pvs-' + siteVersion[0], pv_user_type, {
		//'nonInteraction': 1
	//});
};

// TODO: (Reuben, Jan 2017) maybe this method should be in wikihow_common_bottom.js.
// Doesn't seem like it really needs to be here, since it's only used by a couple
// randomish tools.
WH.xss = {
	addToken: function() {
		mw.loader.using( 'mediawiki.cookie', function() {
			var js_sessid = mw.user.sessionId();
			var cookie_sessid = mw.cookie.get('js_sessid');
			if ( !cookie_sessid || cookie_sessid != js_sessid ) {
				mw.cookie.set('js_sessid', js_sessid);
			}
			$.ajaxSetup({
				beforeSend: function(xhr /*, settings*/) {
					xhr.setRequestHeader('x-csrf-token', js_sessid);
				}
			});
		} );
	}
};

WH.event = function(name, params) {
	if ( mw.config.get('wgContentLanguage') != 'en' || typeof URLSearchParams === "undefined" ) {
		return;
	}
	params = params || {};
	params.action = name;
	params.screen = WH.shared.getScreenSize();
	params.page = params.page || mw.config.get('wgArticleId');
	var url = '/x/event?' + new URLSearchParams(params).toString();

	var isDev = window.location.href.indexOf(".wikidogs.") >= 0;
	if (isDev) {
		console.log("ping: " + url);
		return;
	}

	var xhr = new XMLHttpRequest();
	xhr.open( 'GET', url, true );
	xhr.send( null );
};

/**
 * Use this method to add your own throttled scroll event handler
 */
WH.addThrottledScrollHandler = function(handler) {
	WH.scrollHandlers = WH.scrollHandlers || [];
	WH.scrollHandlers.push(handler);
};

/**
 * Rate limiting adopted from:
 *   http://stackoverflow.com/a/9617517
 * additional reference here:
 *   setTimeout example: https://developer.mozilla.org/en-US/docs/Web/Events/resize#Example
 *   requestAnimationFrame: https://developer.mozilla.org/en-US/docs/Web/Events/scroll
 */
var scrollTimer = null, lastScrollFireTime = 0;
function handleScrollEvent() {
	var now = +(new Date());
	var MIN_SCROLL_TIME = 50;

	var execHandlers = function () {
		if (typeof WH.scrollHandlers != 'undefined') {
			for (var i = 0; i < WH.scrollHandlers.length; i++) {
				try {
					(WH.scrollHandlers[i])();
				} catch(e) {
					//console.log('handleScrollEvent exception', e);
				}
			}
		}
	};

	if (!scrollTimer) {
		if (now - lastScrollFireTime < 3 * MIN_SCROLL_TIME) {
			execHandlers();
			lastScrollFireTime = now;
		}

		scrollTimer = setTimeout(function () {
			scrollTimer = null;
			lastScrollFireTime = +(new Date());
			execHandlers();
		}, MIN_SCROLL_TIME);
	}
}

/**
 * We throttle scroll events because many, many events can be generated, and
 * it's safe to skip ones that happen in quick succession.
 *
 * See also the mobile code we use to throttle this, in scroll_handler.js
 *
 * Reference of this throttling code:
 *   https://developer.mozilla.org/en-US/docs/Web/Events/resize#Example
 * Mobile rate limiting was originally adopted from, but no longer:
 *   http://stackoverflow.com/a/9617517
 * additional reference here- same thing using requestAnimationFrame:
 *   https://developer.mozilla.org/en-US/docs/Web/Events/scroll
 */
/* NOTE: I experimented with this version but decided the mobile code performed
 * a little better, so using that instead.
var scrollTimeout = null;
function handleScrollEvent() {
	// ignore resize events as long as an actualScrollHandler execution is waiting to execute
	if ( !scrollTimeout ) {
		scrollTimeout = setTimeout(function() {
			scrollTimeout = null;

			if (typeof WH.scrollHandlers != 'undefined') {
				for (var i = 0; i < WH.scrollHandlers.length; i++) {
					(WH.scrollHandlers[i])();
				}
			}

			// The actualResizeHandler will execute at a rate of 15fps, since 1000ms / 66ms = 15fps
		}, 66);
	}
}
*/
//disable dragging images off the page
$('img').on('dragstart', false);

// set up scroll event handlers for normal platforms and iOS devices
var $jqWindow = $(window);
$jqWindow.scroll(handleScrollEvent);

var isIOS = navigator.userAgent.match(/(iPod|iPhone|iPad)/gi) !== null;
if (isIOS) {
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
	var passiveParam = supportsPassive ? { passive: true } : false;

	window.addEventListener('touchstart', handleScrollEvent, passiveParam);
	window.addEventListener('touchmove', handleScrollEvent, passiveParam);
	window.addEventListener('touchend', handleScrollEvent);
}

// Late-load youtube videos. We do this for SEO because youtube
// blocks some resources from being crawled, so this late loading
// is a way of hiding this blocked video from Googlebot. This
// method was recommended by Search Brothers in Feb. 2018. We
// saw the Googlebot blocked resources from Youtube in Google
// Search Console. And, it's better for page performance since
// this resource is at the bottom of the page and should be loaded
// late in the loading waterfall anyway. -Reuben, 2018
//
// Note: this is called below in this same file, and via QG and RCP
WH.showEmbedVideos = function() {
    $('iframe.embedvideo').each( function() {
        var $vid = $(this);
		WH.shared.addScrollLoadItemByElement($vid.get(0));
    });
};

if (document.readyState == 'complete') {
	WH.showEmbedVideos();
} else {
	$(window).load(WH.showEmbedVideos);
}

// Load GA if these variables are set
if ( typeof WH.gaType != "undefined" && typeof WH.gaID != "undefined" && typeof WH.gaConfig != "undefined" ) {
	WH.ga.loadGoogleAnalytics(WH.gaType, WH.gaID, WH.gaConfig);
	// We shouldn't need to delete these since this code is only run once
	//delete WH.gaType; delete WH.gaID; delete WH.gaConfig;
}

/**
 * Check if an avatar's URL is broken, and swap it for the default as needed.
 *
 * @param {HTMLImageElement} image Image to auto-replace src for
 */
WH.autoReplaceBrokenAvatar = function ( image ) {
	var test = new Image();
	test.onerror = function () {
		test.onerror = null;
		image.src = '/skins/WikiHow/images/80x80_user.png';
	};
	test.src = image.src;
};

// Progress bar

function showProgressBar() {
	var $progressBar = $( '<div id="wh-progress-bar"></div>' );
	$('#header_container').append( $progressBar );
	$progressBar.css( 'width', '100%' );
}

function hideProgressBar() {
	$( '#wh-progress-bar' ).remove();
}

// Dialog configurations
var dialogRegistry = {
	'wh-dialog-edit': {
		module: 'ext.wikihow.editDialog',
		window: 'edit',
		isAvailable: function () {
			return mw.user.isAnon() &&
				mw.config.get( 'wgContentLanguage' ) === 'en' &&
				mw.config.get( 'wgNamespaceNumber' ) === 0;
		}
	},
	'wh-dialog-login': {
		module: 'ext.wikihow.loginDialog',
		window: 'login',
		isAvailable: function () {
			return mw.user.isAnon();
		}
	}
};

// Safe to remove after [RELEASE DATE + 1 WEEK]
dialogRegistry['edit'] = dialogRegistry['wh-dialog-edit'];

function onHashChange() {
	var nextDialogKey = window.location.hash.substr( 1 );
	var prevDialogKey = previousHash && previousHash.substr( 1 );
	var prevDialogClosed;
	var dialogConfig;

	// Close previous dialog
	dialogConfig = dialogRegistry[prevDialogKey];
	if ( dialogConfig && dialogConfig.isAvailable() ) {
		mw.loader.using( dialogConfig.module )
			.then( function () {
				var instance = OO.ui.getWindowManager().closeWindow( dialogConfig.window );
				prevDialogClosed = instance.closed;
			} );
	}
	$.when( prevDialogClosed ).then( function () {
		// Open next dialog
		dialogConfig = dialogRegistry[nextDialogKey];
		if ( dialogConfig && dialogConfig.isAvailable() ) {
			showProgressBar();
			mw.loader.using( dialogConfig.module )
				.then( function () {
					var instance = OO.ui.getWindowManager().openWindow( dialogConfig.window );
					instance.opened.then( function () {
						hideProgressBar();
					} );
				} );
		}
		previousHash = window.location.hash;
	} );
}

// Handle hash changes to open/close dialogs, only allowing one dialog open at a time
var previousHash = null;
$( window ).on( 'hashchange', onHashChange );

// Auto-open on load
onHashChange();

}(mediaWiki, jQuery));
