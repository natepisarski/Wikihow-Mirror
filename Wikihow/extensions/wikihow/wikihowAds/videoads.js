WH.videoads = (function () {
	'use strict';

	function onAdError(adErrorEvent, video) {
		// Handle the error logging.
		console.log(adErrorEvent.getError());
		if (video.adsManager) {
			video.adsManager.destroy();
		}
	}

	function onContentResumeRequested(video) {
		video.play();
		// This function is where you should ensure that your UI is ready
		// to play content. It is the responsibility of the Publisher to
		// implement this function when necessary.
		// setupUIForContent();
	}
	function createAdDisplayContainer(video) {
		if (video.linearAd) {
			video.adContainer.className = 'video-ad-container-linear';
		} else {
			video.adContainer.className = 'video-ad-container-nonlinear';
		}
		video.adDisplayContainer = new google.ima.AdDisplayContainer(
				video.adContainer, video.element);
	}

	function onContentPauseRequested(video) {
		video.pause();
		// This function is where you should setup UI for showing ads (e.g.
		// display ad timer countdown, disable seeking etc.)
		// setupUIForAds();
	}

	function initAdsManager(video) {
		if (video.adsManagerInitialized) {
			return;
		}
		if (!video.adContainer) {
			return;
		}
		if (!video.adsManager) {
			return;
		}
		if (!video.shouldInitAdsManager) {
			return;
		}
		if (video.linearAd) {
			video.adsManager.init(728, 410, google.ima.ViewMode.NORMAL);
		} else {
			video.adsManager.init(480, 100, google.ima.ViewMode.NORMAL);
		}
		// Call play to start showing the ad. Single video and overlay ads will start at this time
		video.adsManager.start();
		video.adsManagerInitialized = true;
	}

	function onAdEvent(adEvent, video) {
		// Retrieve the ad from the event. Some events (e.g. ALL_ADS_COMPLETED)
		// don't have ad object associated.
		var ad = adEvent.getAd();
		switch (adEvent.type) {
			case google.ima.AdEvent.Type.LOADED:
				// This is the first event sent for an ad - it is possible to
				// determine whether the ad is a video ad or an overlay.
				if (!ad.isLinear()) {
					// Position AdDisplayContainer correctly for overlay.
					// Use ad.width and ad.height.
					video.play();
				} else {
					video.adStarting();
				}
				break;
			case google.ima.AdEvent.Type.STARTED:
				// This event indicates the ad has started - the video player
				// can adjust the UI, for example display a pause button and
				// remaining time.
				if (ad.isLinear()) {
					// For a linear ad, a timer can be started to poll for
					// the remaining time.
					video.intervalTimer = setInterval(
							function() {
								var remainingTime = video.adsManager.getRemainingTime();
							},
							300); // every 300ms
				}
				break;
			case google.ima.AdEvent.Type.COMPLETE:
				// This event indicates the ad has finished - the video player
				// can perform appropriate UI actions, such as removing the timer for
				// remaining time detection.
				if (ad.isLinear()) {
					clearInterval(video.intervalTimer);
				}
				//video.adComplete();
				break;
			case google.ima.AdEvent.Type.ALL_ADS_COMPLETED:
				video.adComplete();
				break;
		}
	}
	function onAdsManagerLoaded(adsManagerLoadedEvent, video) {
		// Get the ads manager.
		var adsRenderingSettings = new google.ima.AdsRenderingSettings();
		adsRenderingSettings.restoreCustomPlaybackStateOnAdBreakComplete = true;
		adsRenderingSettings.useStyledLinearAds = true;
		adsRenderingSettings.autoAlign = false;
		adsRenderingSettings.useStyledNonLinearAds = true;
		video.adsManager = adsManagerLoadedEvent.getAdsManager(
				video.element, adsRenderingSettings);

		// Add listeners to the required events.
		video.adsManager.addEventListener(
				google.ima.AdErrorEvent.Type.AD_ERROR,
				function(event) {onAdError(event, video);});
		video.adsManager.addEventListener(
				google.ima.AdEvent.Type.CONTENT_PAUSE_REQUESTED,
				function() {onContentPauseRequested(video);});
		video.adsManager.addEventListener(
				google.ima.AdEvent.Type.CONTENT_RESUME_REQUESTED,
				function() {onContentResumeRequested(video);});
		video.adsManager.addEventListener(
				google.ima.AdEvent.Type.ALL_ADS_COMPLETED,
				function(event) {onAdEvent(event, video);});

		// Listen to any additional events, if necessary.
		video.adsManager.addEventListener(
				google.ima.AdEvent.Type.LOADED,
				function(event) {onAdEvent(event, video);});
		video.adsManager.addEventListener(
				google.ima.AdEvent.Type.STARTED,
				function(event) {onAdEvent(event, video);});
		video.adsManager.addEventListener(
				google.ima.AdEvent.Type.COMPLETE,
				function(event) {onAdEvent(event, video);});

		// try to init the ads manager
		initAdsManager(video);
	}

	function setUpIMA(video) {
		// Create the ad display container.
		createAdDisplayContainer(video);
		// Create ads loader.
		video.adsLoader = new google.ima.AdsLoader(video.adDisplayContainer);
		// Listen and respond to ads loaded and error events.
		video.adsLoader.addEventListener(
				google.ima.AdsManagerLoadedEvent.Type.ADS_MANAGER_LOADED,
				function(event) {
					onAdsManagerLoaded(event, video);
				},
				false);
		video.adsLoader.addEventListener(
				google.ima.AdErrorEvent.Type.AD_ERROR,
				function(event) {onAdError(event, video);},
				false);

		// An event listener to tell the SDK that our content video
		// is completed so the SDK can play any post-roll ads.
		var contentEndedListener = function() {video.adsLoader.contentComplete();};
		video.element.onended = contentEndedListener;

		// Request video ads.
		var adsRequest = new google.ima.AdsRequest();
		if (video.linearAd) {
			adsRequest.adTagUrl = 'https://pubads.g.doubleclick.net/gampad/live/ads?iu=/10095428/Test_Video_Ad_Instream&description_url=https%3A%2F%2Fwww.wikihow.com%2Fx%2Fvideo_description_page.html&env=vp&impl=s&correlator=&tfcd=0&npa=0&gdfp_req=1&output=vast&sz=480x70|480x70&unviewed_position_start=1';
		} else {
			adsRequest.adTagUrl = 'https://pubads.g.doubleclick.net/gampad/live/ads?iu=/10095428/Test_Video_Ad&description_url=https%3A%2F%2Fwww.wikihow.com%2Fx%2Fvideo_description_page.html&env=vp&impl=s&correlator=&tfcd=0&npa=0&gdfp_req=1&output=vast&sz=480x70&unviewed_position_start=1';
			// this is a test ad:
			//adsRequest.adTagUrl = 'https://pubads.g.doubleclick.net/gampad/ads?sz=640x480&iu=/124319096/external/single_ad_samples&ciu_szs=300x250&impl=s&gdfp_req=1&env=vp&output=vast&unviewed_position_start=1&cust_params=deployment%3Ddevsite%26sample_ct%3Dnonlinearvpaid2js&correlator=';
		}

		// Specify the linear and nonlinear slot sizes. This helps the SDK to
		// select the correct creative if multiple are returned.
		adsRequest.linearAdSlotWidth = 728;
		adsRequest.linearAdSlotHeight = 480;

		adsRequest.nonLinearAdSlotWidth = 480;
		adsRequest.nonLinearAdSlotHeight = 70;

		video.adsLoader.requestAds(adsRequest);
	}

	return {
		'setUpIMA': setUpIMA,
		'initAdsManager': initAdsManager,
	};
})();

