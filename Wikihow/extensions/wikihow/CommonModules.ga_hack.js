/**
 * @file Temporary hack - see CommonModules.body.php
 *
 * Please remove this file once the hack is reverted.
 */

(function(window, document) {
'use strict';

/**
 * Polyfill for array.includes() (we can't use jQuery here, and includes() doesn't work on old IE)
 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/includes#Polyfill
 */
Array.prototype.includes||Object.defineProperty(Array.prototype,"includes",{value:function(f,g){if(null==this)throw new TypeError('"this" is null or not defined');var e=Object(this),b=e.length>>>0;if(0===b)return!1;var a=g|0;for(a=Math.max(0<=a?a:b-Math.abs(a),0);a<b;){var c=e[a],d=f;if(c===d||"number"===typeof c&&"number"===typeof d&&isNaN(c)&&isNaN(d))return!0;a++}return!1}});

/**
 * Function adapted from wikihow_common_top.js
 */
function loadGoogleAnalytics(siteVersion, propertyId, config) {

	window._ga_preloaded = true; // Prevent default setup on wikihow_common_top.js

	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m);
	})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

	// Do the main GA ping
	ga('create', propertyId, 'auto', { 'allowLinker': true });
	ga('linker:autoLink', [/^.*wikihow\.(com|cz|it|jp|vn|com\.tr)$/]);
	ga('send', 'pageview');

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
		if (abrCnf && abrCnf.accounts.includes(id)) {
			(function setABRtimeout(category, action, timeout) {
				setTimeout(function() {
					// Don't trigger the event unless the current browser tab is active
					if (typeof document.hidden !== 'undefined' && document.hidden) {
						setABRtimeout(category, action, timeout);
					} else {
						ga(name + '.send', 'event', category, action);
						//want to send a machnify event at the same time to compare
						WH.maEvent(
							'GA_abr_mimic',
							{
								articleId: mw.config.get('wgArticleId'),
								articleTitle: mw.config.get('wgTitle'),
							});
					}
				}, timeout);
			})(abrCnf.eventCategory, abrCnf.eventAction, abrCnf.timeout * 1000)
		}
	}

};

var siteVersion = {{{siteVersion}}};
var propertyId = {{{propertyId}}};
var gaConfig = {{{gaConfig}}};

loadGoogleAnalytics(siteVersion, propertyId, gaConfig);

}(window, document));
