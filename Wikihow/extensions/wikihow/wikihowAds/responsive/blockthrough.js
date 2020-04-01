if (WH.shared.isDesktopSize) {
	var newScript = document.createElement('script');
	newScript.async = true;
	newScript.type = 'text/javascript';
	newScript.src = 'https://wikihow-com.videoplayerhub.com/galleryloader.js';
	var node = document.getElementsByTagName('script')[0];
	node.parentNode.insertBefore(newScript, node);
}
