WH.ads = (function () {
	"use strict";
	var RR_REFRESH_TIME = 30000;
	var VIEW_REFRESH_TIME = 5000;
	var INTRO_REFRESH_TIME = 12000;
	var BOTTOM_MARGIN = 114;
	var adLabelHeight = 39;
	var rightRailElements = [];

	// keep track of the state of the right rail elements size
	// only check the size MaxCount times
	var rrSizeChanged = false;
	var rrSizeCheckCount = 0;
	var RR_SIZE_MAX_COUNT = 40;
	var AD_INSERT_MAX_COUNT = 1000;
	var adInsertCount = 0;

    var rightRailExtra = null;

	var quizAds = {};
	var scrollToAd;
	var scrollToAdInsertCount = 0;
	var TOCAd;
	var bodyAds = {};
	var lastScrollPosition = window.scrollY;
	var scrollLoadingHandler = null;
	var scrollToAdLoadingHandler = null;
	var rightRailPositionHandler = null;

	var topMenuHeight = WH.shared.TOP_MENU_HEIGHT;
	var bottomMarginHeight = WH.shared.BOTTOM_MARGIN;
	var initialViewportHeight = (window.innerHeight || document.documentElement.clientHeight);

	var adLoadingObserver = null;
	var loadingMargin = initialViewportHeight * 2;
	var rootLoadingMargin = "0px 0px " + loadingMargin + "px 0px";

	if ("IntersectionObserver" in window) {
	//if (false) {
		adLoadingObserver = new IntersectionObserver(function(entries, observer) {
			entries.forEach(function(entry) {
				if (entry.isIntersecting) {
					loadElement(entry.target);
					adLoadingObserver.unobserve(entry.target);
				}
			});
		}, {
			rootMargin: rootLoadingMargin
		});
	}

	// use interesection observer for loading if the browser supports it
	function useIntersectionObserver() {
		if (adLoadingObserver == null) {
			return false;
		}
		return true;
	}

	if (WH.isMobile) {
		bottomMarginHeight = 314;
	}
	function log() {
	}

	function isDocumentHidden() {
		var hidden = false;
		if (null!=document.hidden) {
			hidden = document.hidden;
		} else if (null!=document.mozHidden) {
			hidden = document.mozHidden;
		} else if (null!=document.webkitHidden) {
			hidden = document.webkitHidden;
		} else if (null!=document.msHidden) {
			hidden = document.msHidden;
		}
		return hidden;
	}

	function apsFetchBids(slotValues, gptSlotIds, timeoutValue) {
		log("apsFetchBids", slotValues, gptSlotIds, timeoutValue);
		var gptSlots = [];
		for (var i = 0; i < gptSlotIds.length; i++) {
			gptSlots.push(gptAdSlots[gptSlotIds[i]]);
		}

		apstag.fetchBids({
			slots: slotValues,
			timeout: timeoutValue
		}, function(bids) {
			googletag.cmd.push(function(){
				apstag.setDisplayBids();
				for (var i = 0; i < gptSlots.length; i++ ) {
					var slot = gptSlots[i];
					setDFPTargeting(slot, dfpKeyVals);
				}
				googletag.pubads().refresh(gptSlots);
			});
		});
	}

	function apsLoad(ad) {
		var id = ad.adTargetId;
		var slotName = gptAdSlots[id].getAdUnitPath();
		var sizes = gptAdSlots[id].getSizes();
		var sizesArray = [];
		for (var i = 0; i < sizes.length; i++) {
			var sizesSub = [];
			sizesSub.push(sizes[i].getWidth());
			sizesSub.push(sizes[i].getHeight());
			sizesArray.push(sizesSub);
		}
		var slotsArray = [{slotID: id, slotName: slotName, sizes: sizesArray}];
		var gptSlotIds = [id];
		apsFetchBids(slotsArray, gptSlotIds, ad.apsTimeout);
	}

	function updateKeyVal(adId, key, value) {
		if (!gptAdSlots[adId]) {
			return;
		}
		if (!dfpKeyVals[gptAdSlots[adId].getAdUnitPath()]) {
			return;
		}

		dfpKeyVals[gptAdSlots[adId].getAdUnitPath()][key] = value;
	}

	function gptLoad(ad) {
		log('gptLoad', ad);
		var id = ad.adTargetId;
		var display = ad.gptLateLoad;
		var refreshValue = ad.getRefreshValue();
		googletag.cmd.push(function() {
			// optionally call display first if dfp late loading is active
			if (display) {
				googletag.display(id);
			}
			updateKeyVal(id, 'refreshing', refreshValue);
			setDFPTargeting(gptAdSlots[id], dfpKeyVals);
			// the refresh call actually loads the ad
			log("gptLoad calling pubads().refresh on", gptAdSlots[id]);
			googletag.pubads().refresh([gptAdSlots[id]]);
		});
	}

	function impressionViewable(slot) {
		log('impressionViewable: slot', slot);
		var ad;
		for (var i = 0; i < rightRailElements.length; i++) {
			var tempAd = rightRailElements[i];
			if (gptAdSlots[tempAd.adTargetId] == slot) {
				ad = tempAd;
			}
		}
		if (!ad) {
			return;
		}
		ad.height = ad.element.offsetHeight;

		if (ad.refreshable && ad.viewablerefresh) {
			setTimeout(function() {ad.refresh();}, ad.getRefreshTime());
		}
	}

	function slotRendered(slot, size, e) {
		log('slotRendered: slot, e', slot, e);
		// look for right rail ads which are the only ones that will be moved/refreshed
		var ad;
		for (var i = 0; i < rightRailElements.length; i++) {
			var tempAd = rightRailElements[i];
			if (gptAdSlots[tempAd.adTargetId] == slot) {
				ad = tempAd;
			}
		}
		if (!ad) {
			return;
		}
		ad.height = ad.element.offsetHeight;
		ad.element.classList.remove('blockthrough');
        // don't even bother checking the space unless the ad is less than 300px in height
		var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);
        if (ad.extraChild && size && parseInt(size[1]) < 300) {
			ad.extraChild.style.visibility = "visible";
        } else if (ad.extraChild) {
            ad.extraChild.style.visibility = "hidden";
        }
		if (!ad.notfixedposition) {
			updateFixedPositioning(ad, viewportHeight);
		}

		if (ad.refreshable && ad.renderrefresh) {
			setTimeout(function() {ad.refresh();}, ad.getRefreshTime());
		}
	}

	function DFPInit() {
		(function() {
			var gads = document.createElement('script');
			gads.async = true;
			gads.type = 'text/javascript';
			var useSSL = 'https:' == document.location.protocol;
			gads.src = 'https://securepubads.g.doubleclick.net/tag/js/gpt.js';
			var node = document.getElementsByTagName('script')[0];
			node.parentNode.insertBefore(gads, node);
		})();
		function setDFPTargeting(slot, data) {
			var slotData = data[slot.getAdUnitPath()];
			for (var key in slotData) {
			  slot.setTargeting(key, slotData[key]);
			}
		}
		googletag.cmd.push(function() {
			defineGPTSlots();
			googletag.pubads().addEventListener('slotRenderEnded', function(event) {
				if (WH.ads) {
					WH.ads.slotRendered(event.slot, event.size, event);
				}
			});
			googletag.pubads().addEventListener('impressionViewable', function(event) {
				if (WH.ads) {
					WH.ads.impressionViewable(event.slot);
				}
			});
		});
	}

	function ccpaOptOut() {
		var hasCookie = document.cookie.indexOf('ccpa_out=');
		if (hasCookie >= 0) {
			return true;
		}
		return false;
	}

	function insertAdsenseAd(ad) {
		// set the height of he ad to the adsense height
		var client = "ca-pub-9543332082073187";
		var i = window.document.createElement('ins');
		i.setAttribute('data-ad-client', client);
		if (ad.adLabelClass) {
			i.setAttribute('class', 'adsbygoogle' + ' ' + ad.adLabelClass);
		} else {
			i.setAttribute('class', 'adsbygoogle');
		}
		var slot = ad.slot;
		if (!slot) {
			return;
		}
		i.setAttribute('data-ad-slot', slot);

		var channels = null;

		// look for ccpa cookie
		if (ccpaOptOut()) {
			i.setAttribute('data-restrict-data-processing', 1);
			if (ad.type == 'intro') {
				channels = 2385774741;
			}
		} else {
			if (ad.type == 'intro') {
				channels = 2001974826;
			}
		}

		if (ad.adElement.getAttribute('data-ad-format')) {
			i.setAttribute('data-ad-format', ad.adElement.getAttribute('data-ad-format'));
		}
		if (ad.adElement.getAttribute('data-full-width-responsive')) {
			i.setAttribute('data-full-width-responsive', ad.adElement.getAttribute('data-full-width-responsive'));
		}

		if (ad.type == 'middlerelated') {
			i.setAttribute('data-ad-format', 'fluid');
			i.setAttribute('data-ad-layout-key', '-fb+5w+4e-db+86');
		}

		var css = 'display:inline-block;width:'+ad.width+'px;height:'+ad.height+'px;';
		var noWidthTypes = ["method", "qa", "tips", "warnings"];
		// TODO do not use includes
		if (ad.adSize == 'small' && noWidthTypes.includes(ad.type)) {
			css = 'display:block;height:'+ad.height+'px;';
		}
		i.style.cssText = css;
		var target = null
		if (ad.adTargetId) {
			window.document.getElementById(ad.adTargetId).appendChild(i);
		} else {
			return;
		}

		// if the ad has specific channels then add it
		if (ad.channels) {
			if (channels) {
				channels = ad.channels + "," + channels;
			} else {
				channels = ad.channels;
			}
		}

		// make sure the channels is an empty string if it would otherwise be null
		if (!channels) {
			channels = '';
		}

		if (typeof adsbygoogle === 'undefined') {
			window.adsbygoogle = [];
		}
		(window.adsbygoogle = window.adsbygoogle || []).push({
			params: {
				google_ad_channel: channels
			}
		});
	}

	function recalcAdHeights() {
		for (var i = 0; i < rightRailElements.length; i++) {
			var ad = rightRailElements[i];
			ad.height = ad.adElement.offsetHeight;
		}
	}

	function getMobileAdWidth(type) {
		var width = document.documentElement.clientWidth;

		switch(type) {
			case "intro":
				width = width - 30;
				break;
			case "method":
				width = width - 20;
				break;
			case "related":
				width = width - 14;
				break;
			default:
				width = width - 20;
		}
		return width;
	}

	function Ad(element) {
		// the ad element has all the data attributes about the ad
		// the element is the contained div which is the target of the ad insertion
		// it is nested due to making css easier
		var adElement = element.parentElement;

		this.element = element;
		this.adElement = adElement;
		this.height = this.adElement.offsetHeight;
		this.adTargetId = element.id;

		var small = this.adElement.getAttribute('data-small') == 1;
		var medium = this.adElement.getAttribute('data-medium') == 1;
		var large = this.adElement.getAttribute('data-large') == 1;

		var okForSize = false;
		if (small && WH.shared.isSmallSize || medium && WH.shared.isMedSize || large && WH.shared.isLargeSize) {
			okForSize = true;
		}

		if (!okForSize) {
			this.disabled = true;
			//adElement.parentElement.removeChild(adElement);
			adElement.style.display = 'none';
			return;
		}

		this.gptLateLoad = this.adElement.getAttribute('data-lateload') == 1;
		this.service = this.adElement.getAttribute('data-service');
		this.apsload = this.adElement.getAttribute('data-apsload') == 1;
		this.slot = this.adElement.getAttribute('data-slot');
		this.adunitpath = this.adElement.getAttribute('data-adunitpath');
		this.channels = this.adElement.getAttribute('data-channels');
		this.mobileChannels = this.adElement.getAttribute('data-mobilechannels');
		this.refreshable = this.adElement.getAttribute('data-refreshable') == 1;
		this.slotName = this.adElement.getAttribute('data-slot-name');
		this.refreshType = this.adElement.getAttribute('data-refresh-type');
		this.sizesArray = this.adElement.getAttribute('data-sizes-array');
		if (this.sizesArray) {
			this.sizesArray = JSON.parse(this.sizesArray);
		}
		this.type = this.adElement.getAttribute('data-type');
		if (this.type == 'rightrail') {
			this.position = 'initial';
		}

		this.notfixedposition = this.adElement.getAttribute('data-notfixedposition') == 1;
		this.viewablerefresh = this.adElement.getAttribute('data-viewablerefresh') == 1;
		this.renderrefresh = this.adElement.getAttribute('data-renderrefresh') == 1;
		this.width = this.adElement.getAttribute('data-width');
		this.height = this.adElement.getAttribute('data-height');

		//override any size specific settings
		if (small && WH.shared.isSmallSize) {
			this.adSize = 'small';
			this.channels = this.mobileChannels;
			this.slot = this.adElement.getAttribute('data-smallslot') || this.slot;
			this.height = this.adElement.getAttribute('data-smallheight') || this.height;
			this.width = getMobileAdWidth(this.type);
			this.service = this.adElement.getAttribute('data-smallservice') || this.service;
		}

		if (medium && WH.shared.isMedSize) {
			this.adSize = 'medium';
			this.slot = this.adElement.getAttribute('data-mediumslot') || this.slot;
			this.height = this.adElement.getAttribute('data-mediumslot') || this.height;
			this.width = this.adElement.getAttribute('data-mediumwidth') || this.width;
			this.service = this.adElement.getAttribute('data-mediumservice') || this.service;
		}

		if (large && WH.shared.isLargeSize) {
			this.adSize = 'large';
		}

		if (this.service == 'adsense' && !this.slot) {
			this.disabled = true;
			adElement.style.display = 'none';
			return;
		}

		this.instantLoad = this.adElement.getAttribute('data-instantload') == 1;
		this.adLabelClass = this.adElement.getAttribute('data-adlabelclass');
		this.instantLoad = this.adElement.getAttribute('data-instantload') == 1;
		this.apsTimeout = this.adElement.getAttribute('data-aps-timeout');
		this.refreshtimeout = false;
		this.refreshNumber = 1;
        this.maxRefresh = this.adElement.getAttribute('data-max-refresh');
        this.refreshTime = this.adElement.getAttribute('data-refresh-time');
		if (!this.refreshTime) {
			this.refreshTime = RR_REFRESH_TIME;
		} else {
			this.refreshTime = parseInt(this.refreshTime);
		}
		this.firstRefresh = true;
        this.firstRefreshTime = this.adElement.getAttribute('data-first-refresh-time');
		if (!this.firstRefreshTime) {
			this.firstRefreshTime = this.refreshTime;
		} else {
			this.firstRefreshTime = parseInt(this.firstRefreshTime);
		}

		this.useScrollLoader = true;
		this.observerLoading = this.adElement.getAttribute('data-observerloading') == 1;

		this.getRefreshTime = function() {
			if (this.firstRefresh == true ) {
				this.firstRefresh = false;
				return this.firstRefreshTime;
			} else {
				return this.refreshTime;
			}
		}

		this.getRefreshValue = function() {
			if (this.refreshNumber == 0 && !this.refreshable) {
				return 'not';
			}

			this.refreshNumber++;
			if (this.refreshNumber > 20) {
				return 'max';
			}
			return this.refreshNumber.toString();
		};
		this.load = function() {
			// if already loaded do nothing
			if (this.isLoaded == true) {
				return;
			}
			if (this.service == 'dfp') {
				// if dfp was not already initialized do so now
				if (gptRequested == false) {
					DFPInit();
					gptRequested = true;
				}
				if (this.apsload) {
					var id = this.adTargetId;
					var slot = gptAdSlots[id];
					var ad = this;
					// if gpt slots haven't bene defined yet then queue up this command
					if (!slot) {
						googletag.cmd.push(function() {
							apsLoad(ad);
						});
					} else {
						apsLoad(ad);
					}
				} else {
					gptLoad(this);
				}
			} else if (this.service == 'dfplight') {
				insertDFPLightAd(this);
			} else {
				insertAdsenseAd(this);
			}
			this.isLoaded = true;
		};

		this.refresh = function() {
			log('refresh: ad', this);
			var ad = this;
			if (isDocumentHidden()) {
				// check again later
				setTimeout(function() {ad.refresh();}, VIEW_REFRESH_TIME);
				return;
			}
			var lastScrollY = this.lastRefreshScrollY;
			this.lastRefreshScrollY = window.scrollY;
			// check if ad is in viewport
			var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);
			var rect = this.element.getBoundingClientRect();
			if (!isInViewport(rect, viewportHeight, false, ad)) {
				// check again later
				log("refresh: not in viewport", rect, viewportHeight);
				setTimeout(function() {ad.refresh();}, VIEW_REFRESH_TIME);
				return;
			}
			var refreshValue = this.getRefreshValue();
			if (this.maxRefresh && refreshValue > this.maxRefresh) {
				log("max refreshes reached returning");
				this.refreshable = false;
				return;
			}
			if (this.service != 'adsense') {
				updateKeyVal(this.adTargetId, 'refreshing', refreshValue);
			}
			if (this.apsload) {
				apsLoad(this);
			} else {
				var id = this.adTargetId;
				var display = this.gptLateLoad;
				googletag.cmd.push(function() {
					setDFPTargeting(gptAdSlots[id], dfpKeyVals);
					googletag.pubads().refresh([gptAdSlots[id]]);
				});
			}
		};
		this.show = function() {
			this.adElement.style.display = 'block';
		};

		if (this.instantLoad) {
			this.load();
		}
	}

	function BodyAd(element) {
		Ad.call(this, element);
	}

	function isNearEndOfStep(rect, viewportHeight) {
		if (rect.bottom >= screenTop && rect.bottom <= viewportHeight) {
			return true;
		}
		return false;
	}

	function getStepForScrollPosition(viewportHeight, scrollPosition, steps, ad) {
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
			if (isInViewport(rect, viewportHeight, false, ad)) {
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

	function getInsertTargetForScrollPosition(ad) {
		var scrollPosition = ad.lastScrollPositionY;

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
				section = sectionText[0];
				var rect = section.getBoundingClientRect();
				if (isNearEndOfStep(rect, viewportHeight)) {
					continue;
				}
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
				var stepsTarget = getStepForScrollPosition(viewportHeight, scrollPosition, steps[0].childNodes, ad);
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
			if (isInViewport(rect, viewportHeight, false, ad)) {
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

	function ScrollToAd(element) {
		Ad.call(this, element);
		element.parentElement.style.display = 'none';

		this.scrollToTimer = null;
		this.lastScrollPositionY = 0;

		this.maxNonSteps = parseInt(this.adElement.getAttribute('data-maxnonsteps'));
		this.maxSteps = parseInt(this.adElement.getAttribute('data-maxsteps'));
		this.updateVisibility = function() {
			// handle scrollTo ad if we have one
			if (this.maxNonSteps < 1 && this.maxSteps < 1) {
				if (scrollToAdLoadingHandler) {
					window.removeEventListener('scroll', scrollToAdLoadingHandler);
					scrollToAdLoadingHandler = null;
				}
				return;
			}

			this.lastScrollPositionY = window.scrollY;
			if (this.lastScrollPositionY > 10) {
				if (this.scrollToTimer !== null) {
					clearTimeout(this.scrollToTimer);
				}
				var ad = this;
				this.scrollToTimer = setTimeout(function() {
					ad.load();
				}, 1000);
			}
		};
		this.load = function() {
			var insertTarget = getInsertTargetForScrollPosition(this);
			if (!insertTarget) {
				return;
			}

			var isStep = insertTarget.tagName == "LI";

			if (isStep && this.maxSteps < 1) {
				return;
			} else if (!isStep && this.maxNonSteps < 1) {
				return;
			}

			var existingAds = insertTarget.getElementsByTagName("INS")
			if (existingAds.length > 0) {
				return;
			}
			existingAds = insertTarget.getElementsByClassName("wh_ad_inner")
			if (existingAds.length > 0) {
				return;
			}

			var addTips = insertTarget.getElementsByClassName("addTipElement");
			if (addTips.length > 0) {
				insertTarget.classList.add('has_scrolltoad');
			}

			var wrap = document.createElement('div');
			if (isStep) {
				wrap.className = "wh_ad_inner step_ad scrollto_wrap";
			} else {
				wrap.className = "wh_ad_inner scrollto_wrap";
			}
			insertTarget.appendChild(wrap);
			insertTarget = wrap;
			// give it an id for inserting
			if ( !insertTarget.id ) {
				insertTarget.id = 'scrollto-ad-'+scrollToAdInsertCount;
			}
			this.adTargetId = insertTarget.id;

			insertAdsenseAd(this);

			if (isStep) {
				this.maxSteps--;
			} else {
				this.maxNonSteps--;
			}

			scrollToAdInsertCount++;

			return;
		};
	}

    function RightRailAd(element) {
		Ad.call(this, element);
		// store the right rail container element and height for use later
		this.height = element.offsetHeight;
		this.position = 'initial';
	}

	/*
	 *  check if either the top or the bottom of the element is in view
	 *  taking into account header
	 *  if for loading we add 20% to the size of the viewport
	 *  @param rect - the result of calling  of getBoundingClientRect() on the target element
	 *  @param viewportHeight - the current viewport height
	 *  @param forLoading - adds 20% to viewport size
	 *  @param ad - the ad we are checking
	 */
	function isInViewport(rect, viewportHeight, forLoading, ad) {
		var screenTop = topMenuHeight;

		// TODO not sure if we should leave this or comment out...
		//if (rect.height == 0) return false;

		if (forLoading) {
			var offset = viewportHeight;
			// for body ads load them even sooner
			if (ad instanceof BodyAd) {
				offset = viewportHeight * 2;
			}
			screenTop = 0 - offset;
			viewportHeight = viewportHeight + offset;
		}
		if (rect.top >= screenTop && rect.top <= viewportHeight) {
			return true;
		}
		if (rect.bottom >= screenTop && rect.bottom <= viewportHeight) {
			return true;
		}
		if (rect.top <= screenTop && rect.bottom >= viewportHeight) {
			return true;
		}

		// if this is the last ad, then if the top of the rec is less than screen top
		// meaning the top of the ad container is above the page, then we will
		// set the add to always in the viewport..faking that it's height is infinite
		if (ad.last && rect.top <= screenTop) {
			return true;
		}
		return false;
	}

	function updateAdLoading(ad, viewportHeight) {
		if (ad.isLoaded) {
			return;
		}
		if (ad.useScrollLoader == false) {
			return;
		}
		var rect = ad.element.getBoundingClientRect();
		// check viewport size + additional 20% so we load before the video is in view
		if (isInViewport(rect, viewportHeight, true, ad)) {
			ad.load();
		}
	}

	function finishIntroSlide(ad) {
		ad.isAnimating = false;
		ad.hasAnimated = true;
		ad.adElement.style.position = 'static';
		ad.adElement.style.top = 'auto';
		ad.adElement.style.zIndex = 'auto';
		ad.adElement.style.backgroundColor = '#fff';
		ad.isFixed = false;
		ad.position = 'static';
		if (ad.stickingHeaderElement) {
			var rect = ad.stickingHeaderElement.getBoundingClientRect();
			if (rect.top < 150) {
				ad.stickingHeaderElement.className = 'sticking';
				ad.stickingHeaderElement.style.top = null;
			}
		}
	}
	function slideIntroAdUp(ad, start, end) {
		// check if we should just stop early
		var rect = ad.element.getBoundingClientRect();
		if (rect.top > adLabelHeight || start <= end) {
			finishIntroSlide(ad);
		} else {
			ad.isAnimating = true;
			var headerVal = start + 132;
			if (ad.stickingHeaderElement) {
				//ad.stickingHeaderElement.setAttribute('data-animating', 1);
				ad.stickingHeaderElement.style.top = headerVal+'px';
			}
			ad.adElement.style.top = start+'px';
			setTimeout(function(){
				slideIntroAdUp(ad, start - 1, end);
			}, 3);
		}
	}

	function updateFixedPositioningIntro(ad, viewportHeight) {
		var rect = ad.element.getBoundingClientRect();

		if (ad.isAnimating == true) {
			return;
		}
		if (rect.top <= adLabelHeight) {
			// pick some random spot for the thing to slide back up
			if (rect.top > -500) {
				if (ad.hasAnimated == true) {
					return;
				}
				ad.adElement.style.position = 'fixed';
				ad.adElement.style.top = adLabelHeight + 'px';
				ad.adElement.style.zIndex = '1000';
				ad.isFixed = true;
				ad.position = 'fixed';
			} else if (ad.position == 'fixed'){
				slideIntroAdUp(ad, adLabelHeight, -94);
			}
		} else {
			ad.adElement.style.position = 'static';
			ad.adElement.style.top = 'auto';
			ad.adElement.style.zIndex = 'auto';
			ad.adElement.style.backgroundColor = '#fff';
			ad.isFixed = false;
			ad.position = 'static';
		}
	}

	/*
	 * returns the height of the element (in this case the ad) which is
	 * useful so we do not have to call getBoundingClientRect multiple times
	 * when trying to get the height of all three ads
	 */
	function updateFixedPositioning(ad, viewportHeight) {
		var rect = ad.adElement.getBoundingClientRect();

		if (!isInViewport(rect, viewportHeight, false, ad)) {
			// if the container is not in the viewport then make sure it is not fixed pos
			if (ad.position == 'fixed') {
				ad.element.style.position = 'absolute';
				ad.element.style.top = '0';
				ad.element.style.bottom = 'auto';
				ad.position = 'top';
			}
			return rect.height;
		}

		var bottom = topMenuHeight + parseInt(ad.height);
		if (rect.bottom < bottom && !ad.last) {
			if (ad.position != 'bottom') {
				ad.element.style.position = 'absolute';
				ad.element.style.top = 'auto';
				ad.element.style.bottom = '0';
				ad.position = 'bottom';
			}
		} else if (rect.top <= topMenuHeight) {
			if (ad.position != 'fixed') {
				ad.element.style.position = 'fixed';
				ad.isFixed = true;
				ad.position = 'fixed';
			}
			var topPx = topMenuHeight;
			if (ad.last) {
				var adBottom = window.scrollY + topMenuHeight + parseInt(ad.height);
				var offsetBottom = document.documentElement.scrollHeight - bottomMarginHeight;
				if ( adBottom > offsetBottom ) {
					topPx = topPx - (adBottom - offsetBottom);
				}
			}
			ad.element.style.top = topPx + 'px';
		} else {
			if (ad.position != 'top') {
				ad.element.style.position = 'absolute';
				ad.element.style.top = '0';
				ad.element.style.bottom = 'auto';
				ad.position = 'top';
			}
		}

		return rect.height;
	}

	/*
	 * if we have 3 right rail elements check sidebar height vs article height
	 * to make sure the rr ads are not longer than the article
	 */
	function checkSidebarHeight(rightRailElements, adHeights) {
		if (!WH.shared.isLargeSize) {
			return;
		}
		if (rrSizeChanged) {
			return;
		}
		if (rrSizeCheckCount >= RR_SIZE_MAX_COUNT) {
			return;
		}
		if (document.readyState != 'complete') {
			return;
		}
		rrSizeCheckCount++;
		var sidebarHeight = 0;
		var sidebar = document.getElementById('sidebar');
		if (sidebar) {
			sidebarHeight = sidebar.offsetHeight;
		}
		var articleHeight = 0;
		var article = document.getElementById('article');
		if (article) {
			articleHeight = article.offsetHeight;
		}

		// subtract from each RR element if the sidebar is bigger than the article
		if (articleHeight > 0 && sidebarHeight > 0 && sidebarHeight > articleHeight) {
			var diff = sidebarHeight - articleHeight;
			diff = parseInt((diff + 10)/3);
			var removeElements = false;
			for (var i = 0; i < rightRailElements.length; i++) {
				var value = adHeights[i] - diff;
				if (value < 600) {
					removeElements = true;
					break;
				}
				rightRailElements[i].element.style.height = value + 'px';
			}
			if (removeElements == true) {
				for (var i = 1; i < rightRailElements.length; i++) {
					var el = rightRailElements[i].element;
					el.parentElement.removeChild(el);
				}
				rightRailElements.length = 1;
			}
			// set flag to not resize again
			rrSizeChanged = true;
		}
	}

	// this is registered by the scroll handler
	function rightRailFixedPosition() {
		var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);
		// keep track of ad heights for possible use if they are too tall for the article
		var adHeights = [];
		for (var i = 0; i < rightRailElements.length; i++) {
			var ad = rightRailElements[i];
			if (ad.notfixedposition) {
				continue;
			}
			adHeights[i] = updateFixedPositioning(ad, viewportHeight);
		}
		checkSidebarHeight(rightRailElements, adHeights);
	}

	function updateVisibility() {
		var allAdsLoaded = true;
		lastScrollPosition = window.scrollY;
		var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);

		// update ad loading on right rail ads
		for (var i = 0; i < rightRailElements.length; i++) {
			var ad = rightRailElements[i];
			if (!ad.isLoaded) {
				allAdsLoaded = false;
				updateAdLoading(ad, viewportHeight);
			}
		}

		// update ad loading on regular article ads
		for (var i in bodyAds) {
			var ad = bodyAds[i];
			if (!ad.isLoaded) {
				allAdsLoaded = false;
				updateAdLoading(ad, viewportHeight);
			}
		}

		if (allAdsLoaded ) {
			window.removeEventListener('scroll', scrollLoadingHandler);
			scrollLoadingHandler = null;
		}
	}

	function updateScrollToAdVisibility() {
			scrollToAd.updateVisibility();
	}

	function init() {
		var viewportWidth = (window.innerWidth || document.documentElement.clientWidth);
		var hasRightRail = viewportWidth >= WH.largeScreenMinWidth;

		if ( !adLoadingObserver ) {
			scrollLoadingHandler = WH.shared.throttle(updateVisibility, 100);
			window.addEventListener('scroll', scrollLoadingHandler);
		}

		if (hasRightRail == true ) {
			rightRailPositionHandler = WH.shared.throttle(rightRailFixedPosition, 10);
			window.addEventListener('scroll', rightRailPositionHandler);
		}

		document.addEventListener('DOMContentLoaded', function() {updateVisibility();}, false);
		if (WH.shared) {
			WH.shared.addResizeFunction(updateVisibility);
		}
	}

	// requires jquery to have been loaded
    function loadTOCAd(anchor) {
		if (!TOCAd) {
			return;
		}
		var target = $(anchor).next('.section').find('.steps_list_2 > li:first');
		if ($(anchor).hasClass("mw-headline")) {
			target = $(anchor).parents('.section:first').find('.steps_list_2 > li:first');
		}
		if (!target.length) {
			return;
		}
		target.append($(TOCAd.adElement));
		TOCAd.load();
		TOCAd.adElement.style.display = "block";
		TOCAd = null;
	}

    function addBodyAd(id) {
        var element = document.getElementById(id);
        var ad = new BodyAd(element);

		var useObserver = useIntersectionObserver();
		// check if ad is disabled for this size screen
		if (ad.disabled) {
			return;
		}

		if (ad.type == 'rightrail') {
			ad.last = true;
			if (rightRailElements.length > 0) {
				rightRailElements[rightRailElements.length -1].last = false;
			}
			rightRailElements.push(ad);
			if (useObserver && ad.observerLoading && ad.instantLoad == false) {
				ad.useScrollLoader = false;
				adLoadingObserver.observe(ad.element);
			}
		} else if (ad.type == 'toc') {
			TOCAd = ad;
			ad.adElement.style.display = "none";
		} else if (ad.type == 'scrollto') {
			scrollToAd = new ScrollToAd(element);
			if (scrollToAd && !scrollToAd.disabled) {
				scrollToAdLoadingHandler = WH.shared.throttle(updateScrollToAdVisibility, 100);
				window.addEventListener('scroll', scrollToAdLoadingHandler);
			}
		} else if (ad.type == 'quiz') {
			quizAds[ad.adElement.parentElement.id] = ad;
			ad.adElement.parentElement.addEventListener("change", function(e) {
				var id = this.id;
				if (quizAds[id]) {
					ad.adElement.classList.remove("hidden");
					quizAds[id].load()
				}
			});
		} else {
			bodyAds[ad.element.id] = ad;
			if (useObserver && ad.observerLoading && ad.instantLoad == false) {
				ad.useScrollLoader = false;
				adLoadingObserver.observe(ad.element);
			}
		}
		if (ad.service == 'dfp') {
			googletag.cmd.push(function() { googletag.display(ad.adTargetId); });;
		}
	}

	// finds the ScrollLoad item matching the element and loads it
	// used by intersection observer ad loading
	// TODO can be improved by storing assoc array of the ads w element as key
	function loadElement(element) {
		var item = bodyAds[element.id];
		if (item) {
			item.load()
		}
		for (var i = 0; i < rightRailElements.length; i+=1) {
			var item = rightRailElements[i];
			if (item.element == element) {
				item.load()
			}
		}
	}

	return {
		'init' :init,
		'addBodyAd': addBodyAd,
		'loadTOCAd': loadTOCAd,
		'slotRendered' : slotRendered,
		'impressionViewable' : impressionViewable,
		'apsFetchBids' : apsFetchBids,
	};

})();
WH.ads.init();
