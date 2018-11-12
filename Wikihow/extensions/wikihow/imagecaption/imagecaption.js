(function() {
	'use strict';
	window.WH = window.WH || {};

	function getFirstChild(el){
		var firstChild = el.firstChild;
		while(firstChild != null && firstChild.nodeType == 3){ // skip TextNodes
			firstChild = firstChild.nextSibling;
		}
		return firstChild;
	}

	window.WH.captionText = function(id) {
		var caption = document.getElementById(id);
		if (caption == null) {
			return;
		}
		// see if we are in half size mode
		// keep track of the smallest font size we use
		var smallestFont = 1.35;
		var captionChild = caption.firstChild;
		while(captionChild != null && captionChild.nodeType == 3) {
			captionChild = captionChild.nextSibling;
		}
		for (var i = 0; i < captionChild.childNodes.length; i++) {
			var node = captionChild.childNodes[i];
			if (node.nodeType == 3) {
				continue;
			}

			// starting font size in em
			var size = 1.35;
			if (window.location.hostname.match(/\bm\./) != null) {
				size = 1.0;
			}
			node.style.fontSize = size+'em';
			var fullWidth = node.scrollWidth;
			var innerWidth = node.offsetWidth;

			// reduce font until text fits in the viewable container
			while (fullWidth > innerWidth && size >= 0.3) {
				size = size - 0.01;
				node.style.fontSize = size+'em';
				fullWidth = node.scrollWidth;

				if (fullWidth <= innerWidth) {
					size = size - 0.04;
					node.style.fontSize = size+'em';
				}
			}

			// font limit test
			size = size + 0.04;
			node.style.fontSize = size+'em';
			fullWidth = node.scrollWidth;
			if (fullWidth > innerWidth) {
				size = size - 0.1;
			} else {
				size = size - 0.04;
			}
			if ( size < smallestFont) {
				smallestFont = size;
			}
			node.style.fontSize = size+'em';
		}

		for (var i = 0; i < captionChild.childNodes.length; i++) {
			var node = captionChild.childNodes[i];
			if (node.nodeType == 3) {
				continue;
			}
			node.style.fontSize = smallestFont+'em';
		}
		caption.className += " mwimg-caption-show";
	};

	window.WH.addCaption = function(id) {
		var caption = document.getElementById(id);
		var mwimg = caption.parentElement;
		var src = null;
		if (mwimg) {
			var img = mwimg.querySelector('.image');
			if (img) {
				img = img.querySelector('img');
				if (img) {
					src = img.getAttribute('data-src');
				}
			} else {
				var video = mwimg.querySelector('.m-video');
				if (video) {
					src = video.getAttribute('data-poster');
				}
			}
		}
		if (src) {
			if (src.split('.').pop() == 'jpg' && WH.shared.webpSupport) {
				src = src + '.webp';
			}
			var image = new Image();
			image.onload = function () {
				WH.captionText(id);
			}
			image.src = src;
		}
	};
}());
