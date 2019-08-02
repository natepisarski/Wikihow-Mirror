var gptAdSlots = [];
var googletag = googletag || {};
var dfpKeyVals = {"refreshing":"1"};
googletag.cmd = googletag.cmd || [];
WH.mobileads = (function () {
	var ANCHOR_REFRESH_TIME = 30000;
	var ANCHOR_HIDE_TIME = 18000;
	var TOP_MENU_HEIGHT = 80;
	var adData;
	var scrollHandler;
	var scrollHandlerRegistered = false;
	var anchorScrollHandlerRegistered = false;
	var anchorScrollHandler;
	var bodyAds = [];
	var gptRequested = false;
	var anchorAd = null;
	var scrollToAd = null;
	var scrollToTimer = null;
	var maxScrollToAdsSteps = 3;
	var maxScrollToAds = 6;
	var scrollToAdsLoadedSteps = 0;
	var scrollToAdsLoaded = 0;

	function setDFPTargeting(data) {
		for (var key in data) {
			googletag.pubads().setTargeting(key, data[key]);
		}
	}

	function impressionViewable(slot) {
		// for now only refresh anchor ad
		if (anchorAd && anchorAd.refreshable) {
			setTimeout(function() {anchorAd.refresh();}, ANCHOR_REFRESH_TIME);
		}
	}

	/**
	 * gets the ad data from json written to the page
	 * @return {json data} the json data which is the ad data
	 */
	function getAdData() {
		var el = document.getElementById("wh_ad_data");
		var data = JSON.parse(el.innerHTML);
		return data;
	}

	/**
	 * calculates the ad slot based on ad position and ad size
	 * @param {string} adPosition - either intro, method, related or footer
	 * @return {string} the list of channels as a string for adsense
	 */
	function getAdSlot(adPosition) {
		if (window.isBig) {
			slot = adData.slots.large[adPosition];
		} else {
			slot = adData.slots.small[adPosition];
		}
		return slot;
	}

	function getMaxNumAds(adPosition) {
		if (adPosition == 'method') {
			return 3;
		}

		if (adPosition == 'related' && window.isBig) {
			return 3;
		}

		if (adPosition == 'middlerelated') {
			return 20;
		}

		return 1;
	}

	/**
	 * calculates the channels for the given ad position based on
	 * the ad position and other global variables such as screen size
	 * @param {string} type - either intro, method, or related for now
	 * @return {string} the list of channels as a string for adsense
	 */
	function getAdChannels(type, target) {
		var channels = adData.channels.base;
		if (window.isBig) {
			channels += adData.channels.baselarge;
		}

		var adPosition = type;

		if (target == "wh_ad_method1") {
			channels += "+4557351379";
		}

		// special channel for old android
		if (window.isOldAndroid) {
			channels += "+8151239771";
		}

		// special channel for very large screens
		if (window.isBig && (screen.width < 738 || (window.isLandscape && screen.height < 738))) {
			channels +=  "+7355504173";
		}

		if (!window.isBig) {
			channels += adData.channels.small[adPosition];
		} else {
			channels += adData.channels.large[adPosition];
		}

		if (window.intlAds == true) {
			channels = "";
		}

		return channels;
	}

	function getIntroAdWidth(type) {
		var width = document.documentElement.clientWidth;

		// 320 or more is ideal ad width, 352 is 320 + 16 padding each side
		if (document.documentElement.clientWidth <= 352) {
			width = document.documentElement.clientWidth;
			var ad1 = document.getElementById("wh_ad_intro");
			var curClass = ad1.className;
			ad1.className = curClass + " wh_ad1_full";
			return width;
		}

		width = width - 30;
		return width;
	}

	function getAdWidth(type) {

		if (isOldAndroid && !window.isBig) {
			return 250;
		}

		var width = document.documentElement.clientWidth;

		switch(type) {
			case "intro":
				width = getIntroAdWidth(type);
				break;
			case "method":
				width = width - 20;
				break;
			case "related":
				width = width - 14;
				break;
			case "footer":
				break;
			default:
				width = width - 20;
		}
		if (width > 724) {
			width = 724;
		}

		return width;
	}

	function getAdHeight(type) {
		var width = document.documentElement.clientWidth;
		var height = 250;
		if (type == 'intro') {
			height = 120;
		} else if (type == 'method' || type == 'scrollto') {
			if (isBig) {
				height = 300;
			} else {
				height = 250;
			}
		} else if (type == 'related') {
			height = 280;
		} else if (type == 'footer') {
			height = 100;
		} else if (type == 'middlerelated') {
			height = 200;
		}
		if (width >= 728 && height > 200) {
			height = 200;
		}
		return height;
	}

	function getAdCss(type) {
		var width = getAdWidth(type);
		var height = getAdHeight(type);
		var css = 'display:inline-block;width:'+width+'px;height:'+height+'px;';
		var noWidthTypes = ["qa", "tips", "warnings"];
		var isNoWidthType = false;
		for (var i = 0; i < noWidthTypes.length; i++) {
			if (noWidthTypes[i] == type) {
				isNoWidthType = true;
			}
		}
		if (isNoWidthType) {
			css = 'display:block;height:'+height+'px;';
		}
		return css;
	}

	function insertAdsenseAd(type,targetElement) {
		var client = "ca-pub-9543332082073187";
		var i = window.document.createElement('ins');
		i.setAttribute('data-ad-client', client);
        var slot = getAdSlot(type);
		i.setAttribute('data-ad-slot', slot);
		i.setAttribute('class', 'adsbygoogle');
		if (type == 'middlerelated') {
			i.setAttribute('data-ad-format', 'fluid');
			i.setAttribute('data-ad-layout-key', '-fb+5w+4e-db+86');
		}
		var css = getAdCss(type);
		i.style.cssText = css;
		targetElement.appendChild(i);
	}

	function localDefineGPTSlots() {
		googletag.pubads().enableSingleRequest();
		googletag.pubads().disableInitialLoad();
		//googletag.pubads().collapseEmptyDivs();
		googletag.enableServices();
	}

	function GPTInit() {
		(function() {
			var gads = document.createElement('script');
			gads.async = true;
			gads.type = 'text/javascript';
			var useSSL = 'https:' == document.location.protocol;
			gads.src = (useSSL ? 'https:' : 'http:') +
			'//www.googletagservices.com/tag/js/gpt.js';
			var node = document.getElementsByTagName('script')[0];
			node.parentNode.insertBefore(gads, node);
		})();
		googletag.cmd.push(function() {
			localDefineGPTSlots();
			googletag.pubads().addEventListener('impressionViewable', function(event) {
				if (WH.mobileads) {
					WH.mobileads.impressionViewable(event.slot);
				}

			});
			setDFPTargeting(dfpKeyVals);
		});
	}
	function defineGPTSlot(ad) {
		googletag.cmd.push(function() {
			gptAdSlots[ad.target] = googletag.defineSlot(ad.path, ad.sizes, ad.target).addService(googletag.pubads());
		});
	}

	function loadGpt(ad) {
		if (gptRequested == false) {
			GPTInit();
			gptRequested = true;
		}
		if (!ad.slotDefined) {
			defineGPTSlot(ad);
			ad.slotDefined = true;
		}
		var id = ad.target;
		googletag.cmd.push(function() {
			googletag.display(id);
			googletag.pubads().refresh([gptAdSlots[id]]);
		});
	}

	function loadGptLight(ad) {
		//ad.adHeight = ad.asHeight;
		var i = window.document.createElement('div');
		i.setAttribute('data-glade', '');
		var path = ad.path;
		i.setAttribute('class', 'gptlight');
		i.setAttribute('data-ad-unit-path', path);
		var width = getAdWidth(ad.type);
		//var width = 300;
		//i.setAttribute('width', 'fill');
		i.setAttribute('width', width);
		var height = getAdHeight(ad.type);
		i.setAttribute('height', height);
		var target = ad.target;
		var glade = document.createElement('script');
		glade.async = true;
		glade.src = 'https://securepubads.g.doubleclick.net/static/glade.js';
		window.document.getElementById(target).appendChild(i);
		window.document.getElementById(target).appendChild(glade);
	}

	/**
	 * updates the target <ins> element with the ad channels
	 * then calls the google js function to load the ad
	 * @param {string} type - the ad type
	 */
	function loadAd(ad) {
		// first create and add the ins element
		var targetElement = window.document.getElementById(ad.target);
		insertAdsenseAd(ad.type, ad.adElement);
		var chans = getAdChannels(ad.type, ad.target);
		var maxNumAds = getMaxNumAds(ad.type);

		(adsbygoogle = window.adsbygoogle || []).push({
			params: {
				google_max_num_ads: maxNumAds,
				google_ad_region: "test",
				google_override_format: true,
				google_ad_channel: chans
			}
		});
	}

	function adTestingActive() {
		return true;
	}

	function getAdTestGroup(split) {
		var type = null;

		// check if ad testing is on
		if (adTestingActive() == false) {
			return type;
		}

		var r = Math.random();

		if (r > split) {
			type = 1;
		} else {
			type = 0;
		}

		return type;
	}

	function isInViewport(rect, viewportHeight, anchorAd) {
		var screenTop = TOP_MENU_HEIGHT;

		var offset = viewportHeight * 2;
		if (anchorAd) {
			offset = 0;
		}
		screenTop = 0 - offset;
		viewportHeight = viewportHeight + offset;
		if (rect.top >= screenTop && rect.top <= viewportHeight) {
			return true;
		}
		if (rect.bottom >= screenTop && rect.bottom <= viewportHeight) {
			return true;
		}
		if (rect.top <= screenTop && rect.bottom >= viewportHeight) {
			return true;
		}
		return false;
	}

	/*
	 * simple animation for anchor ad
	 * start must be below end for it to work
	 */
	function slideAnchorAd(ad, start, end, slideIn) {
		ad.isAnimating = true;
		if (start >= end) {
			ad.isAnimating = false;
			ad.stickyFooterVisible = !ad.stickyFooterVisible;
		} else {
			var val = start;
			if (slideIn) {
				val = 65 - start;
			}
			ad.adElement.parentElement.style.bottom = '-'+val+'px';
			setTimeout(function() {
				slideAnchorAd(ad, start + 1, end, slideIn);
			}, 3);
		}
	}

	function updateAnchorVisibility() {
		var ad = null;

		for (var i = 0; i < bodyAds.length; i++) {
			if (bodyAds[i].stickyFooter) {
				ad = bodyAds[i];
				break;
			}
		}
		if (!ad) {
			return;
		}
		if (!ad.isLoaded) {
			return;
		}
		if (ad.stickyFooterDisabled) {
			return;
		}

		var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);
		var cur =  window.scrollY + viewportHeight;
		var target = ad.showStickyFooterYPos;
		// slide the ad out of view
		if (cur < target && ad.stickyFooterVisible && !ad.isAnimating) {
			slideAnchorAd(ad, 0, 65, false);
			// stop refreshing
			ad.refreshable = false;
			return;
		}

		// slide the ad in to view
		if (cur > target && !ad.stickyFooterVisible && !ad.isAnimating) {
			slideAnchorAd(ad, 0, 65, true);
			return;
		}
	}

	function updateVisibility() {
		var unloadedAds = false;
		var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);
		for (var i = 0; i < bodyAds.length; i++) {
			var ad = bodyAds[i];
			if (ad.isLoaded == false) {
				var rect = ad.adElement.getBoundingClientRect();
				// special case, we can specify a different element to trigger ad loading
				if (ad.viewTargetElement) {
					rect = ad.viewTargetElement.getBoundingClientRect();
				}
				if (isInViewport(rect, viewportHeight, ad.stickyFooter)) {
					ad.load();
				}
				unloadedAds = true;;
			}
		}

		// handle scrollTo ad if we have one
		if (scrollToAd && scrollToAd.isLoaded == false) {
			var scrollPosition = window.scrollY;
			if (scrollPosition > 10) {
				if (scrollToTimer !== null) {
					clearTimeout(scrollToTimer);
				}
				scrollToTimer = setTimeout(function() {
					scrollToAd.load();
				}, 1000);
			}
			unloadedAds = true;
		}
		if (!unloadedAds) {
			window.removeEventListener('scroll', scrollHandler);
			scrollHandlerRegistered = false;
		}
	}
	function registerScrollHandler() {
		if (scrollHandlerRegistered) {
			return;
		}
		scrollHandler = WH.shared.throttle(updateVisibility, 500);
		window.addEventListener('scroll', scrollHandler);
		scrollHandlerRegistered = true;
	}

	// this is only used to remove the anchor ad if you are scrolled high on the page
	function registerAnchorScrollHandler() {
		if (anchorScrollHandlerRegistered) {
			return;
		}
		anchorScrollHandler = WH.shared.throttle(updateAnchorVisibility, 500);
		window.addEventListener('scroll', anchorScrollHandler);
		anchorScrollHandlerRegistered = true;
	}

	// any things to change when the page is loaded (like for ab test cleanup)
	function docLoad() {
		updateVisibility();
	}

	function start() {
		// set up ab testing data
		adData = getAdData();

		document.addEventListener('DOMContentLoaded', function() {docLoad();}, false);
		registerScrollHandler();
	};
	function isNearEndOfStep(rect, viewportHeight) {
		if (rect.bottom >= screenTop && rect.bottom <= viewportHeight) {
			return true;
		}
		return false;
	}

	function getStepForScrollPosition(viewportHeight, scrollPosition, steps) {
		var targetStep = null;
		var found = false;
		for (var i = 0; i < steps.length; i++) {
			var step = steps[i];
			if (step.nodeName != "LI") {
				continue;
			}
			if (found == true) {
				targetStep = step;
				break;
			}
			var rect = step.getBoundingClientRect();
			if (isInViewport(rect, viewportHeight, true)) {
				// if we are near the end of teh step now, then put the ad at the next step instead
				if (isNearEndOfStep(rect, viewportHeight)) {
					found = true;
				} else {
					targetStep = step;
					break;
				}
			}
		}
		return targetStep;
	}

	function getInsertTargetForScrollPosition() {
		var scrollPosition = window.scrollY;

		var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);
		var sections = document.getElementsByClassName("section");
		var target = null;
		var found = false;
		for (var i = 0; i <= sections.length; i++) {
			var section = null;
			if (i == sections.length) {
				section = document.getElementById('ur_mobile');
				if (!section) {
					break;
				}
			} else {
				section = sections[i];
			}
			if (section.id == "aiinfo") {
				continue;
			}

			if (found == true) {
				var sectionText = section.getElementsByClassName("section_text");
				if (!sectionText || !sectionText[0]) {
					return null;
				}
				target = sectionText[0];
				break;
			}

			// skip intro
			if (section.id == "intro") {
				continue;
			}
			if (section.classList.contains('steps')) {
				var steps = section.getElementsByClassName("steps_list_2");
				if (!steps || !steps[0]) {
					continue;
				}
				var stepsTarget = getStepForScrollPosition(viewportHeight, scrollPosition, steps[0].childNodes);
				if (!stepsTarget) {
					continue;
				}
				target = stepsTarget;
				break;
			}

			var sectionText = section.getElementsByClassName("section_text");
			if (!sectionText || !sectionText[0]) {
				continue;
			}
			section = sectionText[0];
			var rect = section.getBoundingClientRect();
			if (isInViewport(rect, viewportHeight, true)) {
				// if we are near the end of the section now, then put the ad at the next section instead
				if (isNearEndOfStep(rect, viewportHeight)) {
					found = true;
				} else {
					target = section;
					break;
				}
			}
		}
		return target;
	}

	// moves the ad to a position near where you are scrolled
	// intended to be called before loading it
	// returns true if it was moved, false if something went wrong
	function loadScrollToAd(ad) {
		var insertTarget = getInsertTargetForScrollPosition();
		if (!insertTarget) {
			return false;
		}
		var isStep = insertTarget.tagName == "LI";

		if (isStep) {
			if (scrollToAdsLoadedSteps >= maxScrollToAdsSteps) {
				return;
			}
		} else {
			if (scrollToAdsLoaded>= maxScrollToAds) {
				return;
			}
		}

		var existingAds = insertTarget.getElementsByTagName("INS")
		if (existingAds.length > 0) {
			return;
		}
		existingAds = insertTarget.getElementsByClassName("wh_ad")
		if (existingAds.length > 0) {
			return;
		}

		var addTips = insertTarget.getElementsByClassName("addTipElement");
		if (addTips.length > 0) {
			insertTarget.classList.add('has_scrolltoad');
		}

		// if we wrap the ad in a div it always seems to center itself better
		var wrap = document.createElement('div');
		wrap.className = "scrollto_wrap";
		insertTarget.appendChild(wrap);
		insertTarget = wrap;

		insertAdsenseAd(ad.type, insertTarget);
		insertTarget.appendChild(ad.adElement);

		// now insert label
		var label = document.createElement('div');
		label.className = 'ad_label_scrollto';
		label.innerHTML = "Advertisement";

		insertTarget.appendChild(label);

		var chans = getAdChannels(ad.type, ad.target);
		(adsbygoogle = window.adsbygoogle || []).push({
			params: {
				google_max_num_ads: 3,
				google_ad_region: "test",
				google_override_format: true,
				google_ad_channel: chans
			}
		});

		if (isStep) {
			scrollToAdsLoadedSteps++;
		} else {
			scrollToAdsLoaded++;
		}

		if (scrollToAdsLoaded >= maxScrollToAds && scrollToAdsLoadedSteps >= maxScrollToAdsSteps) {
			ad.isLoaded = true;
		}

		return true;
	}

	function Ad(target) {
		this.target = target;
		this.adElement = document.getElementById(target);
		this.type = this.adElement.getAttribute('data-type');
		this.isLoaded = false;
		this.scrollLoad = this.adElement.getAttribute('data-scroll-load') == 1;
		this.loadClass = this.adElement.getAttribute('data-load-class');
		this.scrollTo = this.adElement.getAttribute('data-scrollto') == 1;
		this.stickyFooter = this.adElement.getAttribute('data-sticky-footer') == 1;
		this.stickyFooterVisible = false;
		this.stickyFooterDisabled = false;
		this.autohide = this.adElement.getAttribute('data-autohide') == 1;
		this.service = this.adElement.getAttribute('data-service');
		this.path = this.adElement.getAttribute('data-path');
		this.sizes = JSON.parse(this.adElement.getAttribute('data-sizes'));
		// for testing fluid ads do this instead
		//this.sizes = this.adElement.getAttribute('data-sizes');
		this.viewTargetElement = null;
		if (this.stickyFooter) {
			this.showStickyFooterYPos = parseInt(this.adElement.getAttribute('data-stickyfooterypos'));
			registerAnchorScrollHandler();
		}

		//add ad height to the css so we can block out space for it on the page
		//(needed for animating anchor links in scroll_handler.js)
		var adHeight = getAdHeight(this.type);
		this.adElement.style.height = adHeight+'px';

		this.load = function() {
			if (this.scrollTo) {
				loadScrollToAd(this);
				return;
			} else if (this.service == 'gptlight') {
				loadGptLight(this);
			} else if (this.service == 'gpt') {
				loadGpt(this);
			} else {
				// adsense
				loadAd(this);
			}
			if (this.loadClass) {
				var curClass = this.adElement.parentElement.className;
				this.adElement.parentElement.className = curClass + " " + this.loadClass;
			}
			if (this.stickyFooter) {
				this.stickyFooterVisible = true;
			}
			this.isLoaded = true;
			// for now autohide only if it is the sticky footer ad
			if (this.autohide && this.stickyFooter) {
				var ad = this;
				// set a time out to hide the ad
				setTimeout(function() {
					slideAnchorAd(ad, 0, 65, false);
					ad.refreshable = false;
					ad.stickyFooterDisabled = true;
				}, ANCHOR_HIDE_TIME);
			}
		}
		this.refreshNumber = 0;
		this.getRefreshValue = function() {
			this.refreshNumber++;
			if (this.refreshNumber > 3) {
				return 'max';
			}
			return this.refreshNumber.toString();
		};
		this.refreshable = this.adElement.getAttribute('data-refreshable') == 1;
		this.refresh = function() {
			var ad = this;
			var id = this.target;
			var refreshValue = this.getRefreshValue();
			googletag.cmd.push(function() {
                dfpKeyVals['refreshing'] = refreshValue;
                setDFPTargeting(dfpKeyVals);
				googletag.pubads().refresh([gptAdSlots[id]]);
			});
            if (refreshValue == 'max') {
                this.refreshable = false;
            }
		};
		if (this.scrollLoad == false) {
			this.load();
		}
	}

	function add(target) {
		var ad = new Ad(target);
		if (ad.stickyFooter && window.isBig) {
			return;
		}
		if (ad.scrollTo) {
			scrollToAd = ad;
			return;
		}
		if (ad.stickyFooter) {
			anchorAd = ad;
		}
		bodyAds.push(ad);
		registerScrollHandler();
	}

	return {
		'start':start,
		'add' : add,
		'impressionViewable' : impressionViewable,
	};
})();
WH.mobileads.start();
