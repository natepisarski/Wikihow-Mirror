/*global WH, mw*/
WH.video = (function () {
	'use strict';
	var videos = [];
	var VISIBILITY_PERCENT = 75;
	var TOP_MENU_HEIGHT = 80;
	var autoPlayVideo = false;
	// Note: this is the same as php global WH_CDN_VIDEO_ROOT
	var cdnRoot = 'https://www.wikihow.com/video';
	var mobile = false;
	var imageFallback = false;
	var okToLoadVideos = false;

	function logAction(action) {
		var xmlHttp = new XMLHttpRequest();
		var url = '/x/event' +
			'?action=' + encodeURIComponent( action ) +
			'&page=' + encodeURIComponent( mw.config.get( 'wgArticleId' ) );
		xmlHttp.open( 'GET', url, true );
		xmlHttp.send( null );
	}

	function visibilityChanged(video) {
		if (video.isVisible == true && video.isPlaying == false) {
			video.play();
		} else if (video.isPlaying == true) {
			video.pause();
		}
	}

	function drawWatermark(el) {
		if(!el) {
			return;
		}
		var txt = el.getAttribute('data-wm-title-text');
		if(!txt) {
			return;
		}
		var c = document.createElement("canvas");
		c.width = 400;
		c.height = 51;
		var ctx = c.getContext("2d");
		ctx.font="bold 50px Helvetica";
		var width = ctx.measureText(txt).width;
		c.width = width;
		ctx.font="bold 50px Helvetica";
		ctx.fillStyle = 'white';
		ctx.fillText(txt,0,42);
		el.appendChild(c);
	}

	function updateItemVisibility(item) {
		var wasVisible = item.isVisible;
		var viewportHeight = (window.innerHeight || document.documentElement.clientHeight);
		var rect = item.element.getBoundingClientRect();

		if (!isInViewport(rect, viewportHeight, false, item.autoplay)) {
			item.isVisible = false;
		} else {
			var visiblePercent = getVisibilityPercent(rect, viewportHeight) * 100;
			item.isVisible = visiblePercent >= VISIBILITY_PERCENT;
		}
		// check viewport size + additional 20% so we load before the video is in view
		if ( item.isLoaded == false && isInViewport(rect, viewportHeight, true)) {
			item.load();
		}
		if (item.isVisible != wasVisible && item.autoplay) {
			visibilityChanged(item);
		}
	}

	// this function is only called if we know the element is in view
	// therefore we can do fewer checks on the element in order to calculate
	// what percentage is visible
	// @param rect - the result of calling  of getBoundingClientRect() on the target element
	// @param viewportHeight - the current viewport height
	function getVisibilityPercent(rect, viewportHeight) {

		if (rect.height == 0) return 0;

		// if the element is larger than the viewport and the full thing is in view
		// we will call that 100% for purposes of playing the videos
		if (rect.top < TOP_MENU_HEIGHT && rect.bottom > viewportHeight) {
			return 1;
		}
		// if top is in view
		if (rect.top > TOP_MENU_HEIGHT && rect.top < viewportHeight) {
			if (rect.bottom < viewportHeight ) {
				// if bottom is also in view.. then the entire thing is in view
				return 1;
			} else {
				// bottom must be below the bottom of the viewport
				return (viewportHeight - rect.top) / rect.height;
			}
		} else if (rect.bottom > TOP_MENU_HEIGHT ) {
			// if the top is not in view .. then the bottom must be in view
			return (rect.bottom - TOP_MENU_HEIGHT)  / rect.height;
		}
	}

	// this is registered by the scroll handler
	function updateVisibility() {
		for (var i = 0; i < videos.length; i++ ) {
			updateItemVisibility(videos[i]);
		}
	}

	// check if either the top or the bottom of the video element is in view
	// taking into account the 40px header and 40px TOC
	// of loading the video, in which case we add 20% to the size of the viewport
	// so the videos load before you scroll to them
	// @param rect - the result of calling  of getBoundingClientRect() on the target element
	// @param viewportHeight - the current viewport height
	// @param forLoading - adds 20% to viewport size
	function isInViewport(rect, viewportHeight, forLoading, noAutoplayTest) {
		var screenTop = TOP_MENU_HEIGHT;

		if (rect.height == 0) return false;

		if (forLoading) {
			var offset = viewportHeight * 0.2;
			if (noAutoplayTest == true) {
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
		return false;
	}

	function scrollTo(x) {
		setTimeout(function() {
			window.scrollTo(0, window.pageYOffset + 50);
			if (window.pageYOffset >= x) return;
			scrollTo(x);
		}, 10);
	};

	function videoControlSetup(video) {
		if (video.replay) {
			video.replay.addEventListener('click', function() {
				video.play();
			});
		}
		if (video.replay && video.helpfulwrap) {
			video.helpfulwrap.addEventListener('click', function(event) {
				if (event.target.tagName == 'BUTTON') {
					video.showHelpfulness = false;
				}
				if (event.target.tagName == 'INPUT') {
					video.playButton.style.visibility = 'hidden';
				}
				event.stopPropagation();
			});
		}

		if (video.playButton) {
			video.playButton.addEventListener('click', function() {
				video.toggle();
			});
			if (video.summaryVideo) {
				var introReadmore = document.getElementById('m-video-intro-readmore');
				if (introReadmore) {
					introReadmore.addEventListener('click', function() {
						var pos = document.getElementById('steps_1').getBoundingClientRect().top + window.pageYOffset - 120;
						scrollTo(parseInt(pos));
					});
				}
			}
			if (video.summaryVideo && !video.isLoaded && "onloadstart" in window) {
				video.element.addEventListener( 'loadstart', function() {
					setTimeout(function(){
						if ( !video.isPlaying ) {
							video.playButton.style.visibility = 'visible';
							if (video.textOverlay) {
								video.textOverlay.style.visibility = 'visible';
							}
						}
					}, 200);
				});
			} else {
				if (video.inlinePlayButton) {
					video.playButton.style.visibility = 'visible';
				}
				if (video.textOverlay) {
					video.textOverlay.style.visibility = 'visible';
				}
			}
		}
		video.element.addEventListener('ended', function() {
			video.isPlaying = false;
		});
		if (video.summaryOutro) {
			video.element.addEventListener('ended', function() {
				video.element.load();
			});
		}
		video.element.addEventListener('play', function() {
			if (video.playButton) {
				if (video.inlinePlayButton || video.summaryVideo) {
					video.playButton.style.visibility = 'hidden';
				}
				if (video.textOverlay) {
					video.textOverlay.style.visibility = 'hidden';
				}
			}
			if (video.summaryOutro) {
				video.element.poster = video.summaryOutro;
			}
			if (video.helpfulwrap) {
				video.helpfulwrap.style.display = 'none';
			}
			if (video.replay) {
				video.replay.style.display = 'none';
			}
		});
		video.element.addEventListener('pause', function() {
			if (video.playButton) {
				if (video.inlinePlayButton || video.summaryVideo) {
					video.playButton.style.visibility = 'visible';
				}
			}
		});

		video.element.addEventListener('ended', function feedback() {
			if (video.replay) {
				video.replay.style.display = 'block';
			}
			if (video.showHelpfulness) {
				video.helpfulwrap.style.display = 'block';
			} else {
				if (video.replayOverlay) {
					video.replayOverlay.style.display = 'block';
				}
			}
		});
	}

	function setVideoAutoplay(value) {
		if (autoPlayVideo == false) {
			return;
		}
		for (var i = 0; i < videos.length; i++ ) {
			var video = videos[i];
			if (video.summaryVideo == true) {
				continue;
			}
			video.inlinePlayButton = !value;
			video.autoplay = value;
			if (value == false && video.playButton && !video.isPlaying) {
				video.playButton.style.visibility = 'visible';
			}
			if (value == true && video.playButton) {
				video.playButton.style.visibility = 'hidden';
			}
			// set isvisible to false to force recalculation of visibility
			video.isVisible = false;
			updateItemVisibility(video);
		}
	}

	// gets the controls element and sets up the helpfulness and watermarks if present
	function getVideoControls(video) {
		if (video.element.parentNode.parentNode.className == 'video-player') {
			video.videoPlayer = video.element.parentNode.parentNode;
		}
		for (var i = 0; i < video.element.parentNode.parentNode.children.length; i++) {
			var el = video.element.parentNode.parentNode.children[i];
			if (el.className == 'm-video-controls') {
				video.controls = el;
				for (var j = 0; j < video.controls.children.length; j++) {
					var child = video.controls.children[j];
					if (child.className == 'm-video-play') {
						video.playButton = child;
					} else if (child.className == 'm-video-play-old') {
						video.playButton = child;
					} else if (child.className == 'm-video-intro-over') {
						video.textOverlay = child;
						for (var k = 0; k < video.textOverlay.children.length; k++) {
							var overlayChild = video.textOverlay.children[k];
							if (overlayChild.className == 'm-video-play') {
								video.playButton = overlayChild;
							}
						}
					}
				}
			} else if (el.className == 'm-video-helpful-wrap') {
				video.helpfulwrap = el;
			} else if (el.className == 's-video-replay') {
				video.replay = el;
			} else if (el.className == 's-video-replay-overlay') {
				video.replayOverlay = el;
				video.replayOverlay.addEventListener('click', function(event) {
					event.stopPropagation();
				});
			} else if (el.className == 'm-video-wm') {
				drawWatermark(el);
			} else if (el.className == 'video-ad-container') {
				video.adContainer = el;
			}
		}
	}

	function playVideoElement(video) {
		video.playPromise = video.element.play();
		if (video.playPromise !== undefined) {
			video.playPromise.then(function(value) {
				video.isPlaying = true;
			}).catch(function(error) {
				console.log(error)
			});
		} else {
			video.isPlaying = true;
		}
		if (video.summaryVideo) {
			video.element.setAttribute('controls', 'true');
			video.element.style.filter = "none";
			if (!video.played) {
				video.played = true;
				logAction('svideoplay');
			}
		}
	}

	function Video(mVideo) {
		this.played = false;
		this.isLoaded = false;
		this.posterLoaded = false;
		this.isVisible = false;
		this.isPlaying = false;
		this.pausedQueued = false;
		this.element = mVideo;
		this.summaryVideo = false;
		this.adContainer = null;
		this.controls = null;
		this.helpfulwrap = null;
		this.poster = this.element.getAttribute('data-poster');
		this.inlinePlayButton = false;
		this.autoplay = autoPlayVideo;
		this.replayOverlay = null;
		this.showHelpfulness = !window.WH.isMobile;
		this.hasPlayedOnce = false;
		if (this.element.getAttribute('data-video-no-autoplay') == 1) {
			this.inlinePlayButton = true;
			this.autoplay = false;
		}
		this.summaryOutro = this.element.getAttribute('data-summary-outro');
		this.poster = WH.shared.getCompressedImageSrc(this.poster);
		if (this.element.getAttribute('data-no-poster-images') == 1) {
			okToLoadVideos = true;
		}
		this.summaryVideo = this.element.getAttribute('data-summary') == 1;
		this.linearAd = this.element.getAttribute('data-ad-type') == 'linear';
		if (this.summaryVideo) {
			this.autoplay = false;
			// Wait for other dependant scripts to have loaded
			document.addEventListener( 'DOMContentLoaded', function () { 
				logAction( 'svideoview' );
			}, false );
		}
		getVideoControls(this);
		if (this.inlinePlayButton == false && !this.summaryVideo) {
			this.playButton.style.visibility = 'hidden';
		}
		this.play = function() {
			if (!okToLoadVideos) {
				return;
			}
			if (this.inlinePlayButton == true && !this.isLoaded) {
				var videoUrl = cdnRoot + this.element.getAttribute('data-src');
				this.isLoaded = true;
				this.element.setAttribute('src', videoUrl);
			}
			if (this.replayOverlay) {
				this.replayOverlay.style.display = 'none';
			}

			var video = this;
			if ( this.hasPlayedOnce == false ) {
				//load the ads
				this.hasPlayedOnce = true;
				if (video.adContainer) {
					video.adDisplayContainer.initialize();
				}
				try {
					video.shouldInitAdsManager = true;
					if (WH.videoads) {
						WH.videoads.initAdsManager(this);
					}
				} catch (adError) {
					// An error may be thrown if there was a problem with the VAST response.
					console.log("ad error", adError);
					playVideoElement(video);
				}
				if (video.adContainer) {
					return;
				}
			}
			playVideoElement(video);
		};
		this.pause = function() {
			var video = this;
			if (this.playPromise !== undefined && !this.pausedQueued) {
				this.pausedQueued = true;
				this.playPromise.then(function(value) {
					video.element.pause();
					video.pausedQueued = false;
					video.isPlaying = false;
				}).catch(function() {});
			} else {
				this.element.pause();
				this.isPlaying = false;
				this.pausedQueued = false;
			}
		};
		this.toggle = function() {
			if (!this.isLoaded) {
				this.load();
				if (this.summaryVideo) {
					this.element.removeAttribute('muted');
					// start the ad
					this.play();
					return;
				}
			}
			if (this.isPlaying) {
				this.pause();
			} else {
				this.play();
			}
		}
		this.adComplete = function() {
			this.adContainer.parentElement.removeChild(this.adContainer);
		}
		this.adStarting = function() {
			if (video.textOverlay) {
				video.textOverlay.style.visibility = 'hidden';
			}
			if (video.playButton) {
				video.playButton.style.visibility = 'hidden';
			}
		}
		this.load = function() {
			var video = this;
			// for summary videos do not show the loading dots
			if (video.poster && !video.posterLoaded) {
				video.element.setAttribute('poster', video.poster);
				video.posterLoaded = true;
				// show loading dots
				if (!video.summaryVideo) {
					var loader = document.createElement("div");
					loader.className = 'loader';
					for (var i = 0; i < 3; i++) {
						var loaderDot = document.createElement("div");
						loaderDot.className = 'loader-dot';
						loader.appendChild(loaderDot);
					}
					var loadingContainer = document.createElement("div");
					loadingContainer.className = 'loading-container';
					loadingContainer.appendChild(loader);
					video.element.parentElement.appendChild(loadingContainer);
					video.loadingContainer = loadingContainer;
					var image = new Image();
					image.onload = function () {
						// remove the loading dots when the poster image loads
						if (loadingContainer.parentNode == video.element.parentElement) {
							video.element.parentElement.removeChild(loadingContainer);
						}
					}
					image.src = video.poster;
				}
			}
			if (video.inlinePlayButton == false) {
				if (okToLoadVideos) {
					if (video.poster && !video.summaryVideo) {
						var image = new Image();
						image.src = video.poster;
						image.setAttribute('class', 'm-video content-fill');
						video.element.parentNode.insertBefore(image, video.element);
						video.overlayImage = image;
					}
					var videoUrl = cdnRoot + video.element.getAttribute('data-src');
					video.element.setAttribute('src', videoUrl);
					video.isLoaded = true;
					video.element.addEventListener("canplay", function() {
						if (loadingContainer && loadingContainer.parentNode == video.element.parentElement) {
							video.element.parentElement.removeChild(loadingContainer);
						}
					}, true);
				}
			}
		};

		if (this.videoPlayer) {
			var video = this;
			this.videoPlayer.addEventListener('click', function() {
				video.toggle();
			});
		}

		if (this.controls) {
			videoControlSetup(this);
		}
	}

	function Gif(mVideo) {
		this.isLoaded = false;
		this.isVisible = false;
		this.isPlaying = false;
		this.gifSrc = mVideo.getAttribute('data-gifsrc');
		this.gifFirstSrc = mVideo.getAttribute('data-giffirstsrc');
		this.play = function() {
			this.element.setAttribute('src', this.gifSrc);
			this.isPlaying = true;
		};
		this.pause = function() {
			// we could switch back to the static image for a fake pause effect
			// but for now do nothing
		};
		this.load = function() {
			// this will pre load the gif
			var image = new Image();
			image.src = this.gifSrc;
			this.isLoaded = true;
		}

		// set height of parent so no flicker when we replace the element
		mVideo.parentNode.parentNode.style.minHeight = mVideo.offsetHeight + "px";

		// create an img element to show the gif
		var image = window.document.createElement('img');
		image.setAttribute('class', 'whvgif whcdn');
		image.setAttribute('src', mVideo.getAttribute('data-giffirstsrc'));
		mVideo.parentNode.replaceChild(image, mVideo);
		this.element = image;
	}

	function pageLoaded() {
		okToLoadVideos = true;
		for (var i = 0; i < videos.length; i++ ) {
			var video = videos[i];
			video.isVisible = false;
			video.isPlaying = false;

			if (WH.videoads && video.adContainer) {
				// set up ads for this video
				WH.videoads.setUpIMA(video);
			}
		}
		updateVisibility();

		// Trevor, 10/30/18 - Dirty hack to track CTR of summary video links
		// Trevor, 5/30/19 - Disabling since Machinify is being slow
		// var link = document.getElementById( 'summary_video_link' );
		// if ( link ) {
		// 	link.onclick = function ( e ) {
		// 		e.preventDefault();
		// 		var href = this.href;
		// 		WH.maEvent( 'videoBrowser_article_video_click', {
		// 			origin: location.hostname,
		// 			videoTarget: href,
		// 			articleOrigin: location.pathname
		// 		}, function () {
		// 			window.location = href;
		// 		} );
		// 	};
		// }
	}

	function start() {
		if (window.WH.isMobile) {
			mobile = true;
		}
		if (WH.shared) {
			autoPlayVideo = WH.shared.autoPlayVideo;
			WH.shared.addResizeFunction(updateVisibility);
		}
		var isHTML5Video = (typeof(document.createElement('video').canPlayType) != 'undefined');
		if (!isHTML5Video) {
			imageFallback = true;
		}

		if (window.location.href.indexOf("gif=1") > 0) {
			autoPlayVideo = false;
		}
		// we can use the dev bucket for testing if the video is in the dev s3 account (uncommon)
		//cdnRoot= '//d2mnwthlgvr25v.cloudfront.net'
		if (WH.shared) {
			window.addEventListener('scroll', WH.shared.throttle(updateVisibility, 100));
		}

		document.addEventListener('DOMContentLoaded', function() {pageLoaded();}, false);
	}

	function add(mVideo) {
		var item = null;
		if (imageFallback) {
			var newId = "img-" + mVideo.id;
			var src = mVideo.getAttribute('data-poster');
			mVideo.parentElement.innerHTML = "<img id='" + newId + "' src='"+ src + "'></img>";
		} else if (mVideo) {
			item = new Video(mVideo);
			videos.push(item);
			updateItemVisibility(item);
		}
	}

	// loads all scroll load items. will be called if the user prints the page
	function loadAllVideos() {
		for (var i = 0; i < videos.length; i++) {
			var video = videos[i];
			if (video.isLoaded) {
				continue;
			}
			video.load();
		}
	}

	return {
		'start':start,
		'add': add,
		'updateVideoVisibility': updateVisibility,
		'setVideoAutoplay': setVideoAutoplay,
		'loadAllVideos': loadAllVideos,
	};
})();
WH.video.start();
