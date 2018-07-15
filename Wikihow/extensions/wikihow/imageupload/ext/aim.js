( function(/*$, mw*/) {
'use strict';

/*
 * Used by image upload to be able to submit an image upload via REST call
 * rather than using a page reload.
 */

/**
 *
 *  AJAX IFRAME METHOD (AIM)
 *  http://www.webtoolkit.info/
 *
 */
window.AIM = {

	frame : function(c) {

		var n = 'f' + Math.floor(Math.random() * 99999);
		var d = document.createElement('DIV');
		d.innerHTML = '<iframe style="display:none" src="about:blank" id="'+n+'" name="'+n+'" onload="AIM.loaded(\''+n+'\')"></iframe>';
		document.body.appendChild(d);

		var i = document.getElementById(n);
		if (c && typeof(c.onComplete) == 'function') {
			i.onComplete = c.onComplete;
		}

		return n;
	},

	form : function(f, name) {
		f.setAttribute('target', name);
	},

	submit : function(f, c) {
		AIM.form(f, AIM.frame(c));
		if (c && typeof(c.onStart) == 'function') {
			return c.onStart();
		} else {
			return true;
		}
	},

	loaded : function(id) {
		var d;
		var i = document.getElementById(id);
		if (i.contentDocument) {
			d = i.contentDocument;
		} else if (i.contentWindow) {
			d = i.contentWindow.document;
		} else {
			d = window.frames[id].document;
		}
		if (d.location.href == 'about:blank') {
			return;
		}

		if (typeof(i.onComplete) == 'function') {
			i.onComplete(d.body.innerHTML);
		}
	}

};

}(jQuery, mediaWiki) );
