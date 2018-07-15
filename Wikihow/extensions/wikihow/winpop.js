( function($) {

// global variables
var winPopH, winPopW;
var resized = false;

var EXTRA_HEIGHT = 90;
var SPECIAL_WIDTH = 750;
var REGULAR_WIDTH = 679;


// isSpecial is for boxes that need to be wider than the standard.
// If isSpecial is true, width will be set to SPECIAL_WIDTH, otherwise
// not.
function popModal(url, w, h, isSpecial, onloadFunc) {
	// Add the HTML to the body
	var theBody = $('body')[0];
	if ($('#winpop_overlay').length) { return false; }

	// overlay
	var popmask = document.createElement('div');
	popmask.id = 'winpop_overlay';

	// window
	var popcont = document.createElement('div');

	popcont.id = 'winpop_outer';
	if (isSpecial) {
		popcont.className = 'winpop_special';
	}
	popcont.innerHTML = '<p style="font:1em Arial, Helvetica;"><img src="/extensions/wikihow/rotate.gif"/></p>';
	if (isSpecial) {
		popcont.style.width = SPECIAL_WIDTH + 'px';
	} else {
		popcont.style.width = REGULAR_WIDTH + 'px';
	}

	theBody.appendChild(popcont);
	theBody.appendChild(popmask);

	getRemoteContent(url, 'winpop_outer', w, h, isSpecial, onloadFunc);

	if (!resized) {
		try {
			if (window.attachEvent) {
				window.attachEvent('resize', resizeModal);
			} else if (window.addEventListener)  {
				window.addEventListener('resize', resizeModal, false);
			} else if (document.addEventListener)  {
				document.addEventListener('resize', resizeModal, false);
			}
		} catch (e) {}
		resized = true;
	}
}

function resizeModal() {
	var winWidth = 0;
	var winHeight = 0;

	if (navigator.userAgent.indexOf('MSIE') > 0) {
		winWidth = document.documentElement.clientWidth;
		winHeight = document.documentElement.clientHeight;
	} else {
		winWidth = window.innerWidth;
		winHeight = window.innerHeight;
	}

	if (winWidth === 0 || winHeight === 0) {
		return;
	}

	if (winPopH + EXTRA_HEIGHT > winHeight) {
		winPopH = winHeight - EXTRA_HEIGHT;
	}
	document.getElementById('winpop_inner').style.height = winPopH + 'px';
}

function getAvailableHeight() {
	if (navigator.userAgent.indexOf('MSIE') > 0) {
		return document.documentElement.clientHeight;
	} else {
		return window.innerHeight;
	}
}

function getRequestObject() {
	var http_request = false;
	if (window.XMLHttpRequest) { // Mozilla, Safari,...
		http_request = new XMLHttpRequest();
		if (http_request.overrideMimeType) {
			http_request.overrideMimeType('text/html');
		}
	} else if (window.ActiveXObject) { // IE
		try {
			http_request = new ActiveXObject('Msxml2.XMLHTTP');
		} catch (e) {
			try {
				http_request = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (f) {}
		}
	}
	return http_request;
}

function getRemoteContent(url, divID, w, h, isSpecial, onloadFunc) {
	var availableHeight = getAvailableHeight();
	if (parseFloat(h) + EXTRA_HEIGHT > availableHeight) {
		h = availableHeight - EXTRA_HEIGHT;
	}
	winPopH = parseFloat(h);
	winPopW = isSpecial ? SPECIAL_WIDTH : REGULAR_WIDTH;

	// add random parameter to prevent caching
	if (url.indexOf('?') == -1) {
		url += '?';
	} else {
		url += '&';
	}
	url += 'rpsc=' + (new Date()).getTime();

	http_request = getRequestObject();
	if (!http_request) {
		alert('Giving up :( Cannot create an XMLHTTP instance');
		return false;
	}
	http_request.open('GET', url, true);
	http_request.onreadystatechange = function() {
		getWinPopData(http_request, divID, winPopW, h, onloadFunc);
	};
	http_request.send(null);
}

function postForm(url, name, method) {
	var i;
	var params = '';
	var form = false;
	var e = document.getElementById('winpop_inner');
	var forms = e.getElementsByTagName('FORM');
	for (i = 0; i < forms.length; i++) {
		if (forms[i].name == name) {
			form = forms[i];
			break;
		}
	}
	method = method.toUpperCase();
	for (i = 0; i < form.elements.length; i++) {
		name = form.elements[i].name;
		value = form.elements[i].value;
		params += name + '=' + encodeURIComponent(value) + '&';
	}
	http_request = getRequestObject();
	if (!http_request) {
		alert('Giving up; cannot create an XMLHTTP instance');
		return false;
	}
	http_request.onreadystatechange = function() {
		getWinPopData(http_request, 'winpop_outer', winPopW, winPopH);
	};
	if (method === 'GET') {
		url += '?' + params;
	}
	http_request.open(method, url, true);
	if (method === 'POST') {
		http_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		//http_request.setRequestHeader('Content-length', params.length);
		http_request.send(params);
	} else {
		http_request.send(null);
	}
	return false;
}

function runCode() {
	var e = document.getElementById('winpop_inner');
	var scripts = e.getElementsByTagName('script');
	for (var i = 0; i < scripts.length; i++) {
		if (scripts[i].innerHTML) {
			eval(scripts[i].innerHTML);
		}
	}
}

function replaceLinks() {
	var i;
	var e = document.getElementById('winpop_inner');
	var links = e.getElementsByTagName('A');
	var server = mw.config.get('wgServer');
	for (i = 0; i < links.length; i++) {
		if (links[i].href.indexOf('javascript:') === 0) continue;
		if (links[i].href.indexOf(server) >= 0) {
			links[i].href = '#';
			links[i].onclick = 'window.getRemoteContent("' + links[i].href + '", "winpop_outer", ' + winPopW + ', ' + winPopH + '); return false;';
		} else {
			links[i].target = 'new';
		}
	}
	var forms = e.getElementsByTagName('FORM');
	for (i = 0; i < forms.length; i++) {
		forms[i].setAttribute('onsubmit', 'return window.postForm("' + forms[i].action + '", "' + forms[i].name + '", "' + forms[i].method + '");');
	}
	window.setTimeout(runCode, 500);
}

function getWinPopData(http_request, divID, w, h, onloadFunc) {
	if (http_request.readyState == 4) {
		if (http_request.status == 200) {
			var html = '' +
				'<a href="#" id="winpop_close" onclick="closeModal();" />Close</a>' +
				'<div id="winpop_inner">' +
				http_request.responseText + '</div>';

			// use $(divID).update(html) instead of $(divID).innerHTML = html
			// because update() runs any inline JS
			var div = document.getElementById(divID);
			if (typeof div.update == 'function') {
				div.update(html);
			} else {
				div.innerHTML = html;
			}
			window.setTimeout(replaceLinks, 500);
			resizeModal();
			if (typeof onloadFunc !== 'undefined' && onloadFunc) onloadFunc();
		} else {
			alert('There was a problem with the request.');
		}
	}
}

function closeModal() {
	$('#winpop_overlay, #winpop_outer').remove();

	try {
		if (window.detachEvent) {
			window.detachEvent('resize', resizeModal);
		} else if (window.removeEventListener)  {
			window.removeEventListener('resize', resizeModal, false);
		} else if (document.removeEventListener)  {
			document.removeEventListener('resize', resizeModal, false);
		}
	} catch (e) {}
}

// export these methods, since we're in a closure
window.popModal = popModal;
window.getRemoteContent = getRemoteContent;
window.postForm = postForm;
window.closeModal = closeModal;

}(jQuery) );
