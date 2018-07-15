( function($, mw) {

// Note: this (terrible) code is still used for ye ol' Weave Links in guided and
// advanced editing as of May 2017. It should be converted to use jQuery for
// everything, include the REST request. - Reuben

var activeElement = null;
var resetAccessKey = false;
var targetObj;
var searchtext;
var lastKeyUpHandler;
var requester;
var sStart = -1;
var sEnd = -1;

function setSelectionRange(input, start, end) {
	var test_gecko = /gecko/i.test(navigator.userAgent);
	if (test_gecko) {
		input.setSelectionRange(start, end);
	} else {
		// assumed IE
		var range = input.createTextRange();
		range.collapse(true);
		range.moveStart('character', start);
		range.moveEnd('character', end - start);
		range.select();
	}
}

function countLines(strText) {
	var count = 0;
	for (var i = 0; i < strText.length; i++) {
		if (strText.charAt(i) == '\n') {
			count++;
		}
	}
	return count;
}

function getSelectionStartEnd(input) {
	var range = document.selection.createRange();
	var stored_range = range.duplicate();
	stored_range.moveToElementText( input );
	stored_range.setEndPoint( 'EndToEnd', range );
	sStart = stored_range.text.length - range.text.length;
	sStart = sStart - countLines(stored_range.text);
	sEnd = sStart + range.text.length;
}

function focusHandler(evt) {
	if (!resetAccessKey && navigator.userAgent.indexOf('MSIE') >= 0 && document.getElementById('weave_button')) {
		document.getElementById('weave_button').accessKey  = '';
	}
	resetAccessKey = true;
	var e = evt ? evt : window.event;
	if (!e) return;
	if (e.target) {
		activeElement = e.target;
	} else if (e.srcElement) {
		activeElement = e.srcElement;
	}
}

function PopItFromGuided() {
	if (!activeElement) {
		alert(mw.msg('popbox_noelement'));
		return;
	}
	PopIt(activeElement);
}

/*
function guidedMSIEKeys() {
	if (navigator.userAgent.indexOf('MSIE')) {
		document.onkeyup = function (e) {
			if (!e) {
				//if the browser did not pass the event information to the
				//function, we will have to obtain it from the event register
				if ( window.event ) {
					//Internet Explorer
					e = window.event;
				} else {
					//total failure, we have no way of referencing the event
					return;
				}
			}

			if (e.altKey && e.keyCode == 82) {
				document.getElementById('weave_button').onclick();
			}
		};
	}
}
*/

function processResponse() {
	if (!requester.status || requester.status == 200) {
		var string = requester.responseText;
		var arr = string.split('\n');
		var count = 0;
		var obj = targetObj;
		var text;
		if (document.selection) {
			var range = document.selection.createRange();
			text =  range.text;
			if (sStart < 0) {
				if (!activeElement && document.getElementById('wpTextbox1')) {
					activeElement = document.getElementById('wpTextbox1');
				}
				getSelectionStartEnd(activeElement);
			}
		} else {
			text  = (obj.value).substring(obj.selectionStart, obj.selectionEnd);
		}
		var html = '<p class="popbox_header">Results for ' + searchtext + ':</p><ol>';
		var i, y, key, x, line;
		for (i = 0; i < arr.length && count < 8; i++) {
			y = arr[i].replace(/^\s+|\s+$/, '');
			key = y.replace(/^https?:\/\/[^\/]+\//, '');
			if (key == mw.config.get('wgPageName')) {
				continue;
			}
			key = key.replace(mw.config.get('wgServer'), '');
			x = unescape(key.replace(/-/g, ' '));
			y = x.replace(/'/g, '\\\'');
			if (y) {
				if (!y.indexOf('Category')) {
					y = ':' + y;
				}
				line = '<li><a id="link' + (count+1) + '" class="popbox_category_link" href="#" data-article="' + y + '">' + x + '</a></li>\n';
				html += line;
				count++;
			}
		}

		html += '</ol>';
		if (count === 0) {
			html += mw.msg('popbox_noresults');
		}

		$('#dialog-box').html(html);

		$('.popbox_category_link').click( function() {
			var article = $(this).data('article');
			WH.Editor.insertTagsWH(targetObj, '[[' + article + '|',']]', ''); 
			updateSummary();
			$('#dialog-box').dialog('close');
		} );

		$('#dialog-box').dialog({
			modal: true,
			width: 400,
			title: mw.msg('popbox_related_articles'),
			minHeight: 200,
			closeText: 'x',
			buttons: [
				{ text: mw.msg('popbox_revise'), click: function() { return Revise(); } },
				{ text: mw.msg('popbox_nothanks'), click: function() { $( this ).dialog('close'); } }
			],
		});
	}
}

function handleResponse() {
	if (!requester) {
		alert('Error encountered.');
		return;
	}
	if (requester.readyState == 4) {
		processResponse();
	}
}

function search(text) {
	requester = null;
	try {
		requester = new XMLHttpRequest();
	} catch (e) {
		try {
			requester = new ActiveXObject('Microsoft.XMLHTTP');
		} catch (e2) {
			return false;
		}
	}
	requester.onreadystatechange = handleResponse;
	var url = mw.config.get('wgServer') + '/Special:LSearch?fulltext=Search&search=' + encodeURIComponent(text) + '&raw=true';
	requester.open('GET', url);
	requester.send(' ');
	searchtext = text;
}

function updateSummary() {
	var updateText = mw.msg('popbox_editdetails');
	if (updateText && document.editform.wpSummary.value.indexOf(updateText) < 0) {
		if (document.editform.wpSummary.value) {
			document.editform.wpSummary.value += ', ';
		}
		document.editform.wpSummary.value += updateText;
	}
	return true;
}

function searchFormSubmit() {
	search(document.getElementById('revise_text').value);
	return false;
}

function fakeSubmit(e) {
	var key;
	if (window.event) {
		// for IE, e.keyCode or window.event.keyCode can be used
		key = e.keyCode;
	} else if (e.which) {
		// netscape
		key = e.which;
	} else {
		// no event, so pass through
		return true;
	}
	if (key == '13') {
		searchFormSubmit();
	}
}

function Revise() {
	var agent = navigator.userAgent.toLowerCase();
	if (document.getElementById('wpTextbox1') && ( (agent.indexOf('firefox') >= 0) || (agent.indexOf('msie 8.0') >= 0) )) {
		$('#dialog-box').html('<p id="popbox_inner"><input id="revise_text" type="text" name="revise" class="search_input search_button" value="' + searchtext + '"  /><img src="/images/a/a8/Search_button.png" class="pb_search_button" /></p>');
		$('#revise_text').keyup( function() {
			fakeSubmit(event);
		} );
		$('#revise_text').click( function() {} );
		$('.pb_search_button').click( function() {
			return searchFormSubmit();
		} );
	} else {
		$('#dialog-box').html('<p id="popbox_inner"><input id="revise_text" type="text" name="revise" class="search_input" value="' + searchtext + '" /><button class="search_button">' + mw.msg('popbox_search') + '</button></p>');
 		$('.search_input').keyup( function() {
			fakeSubmit(event);
		} );
		$('.search_button').click( function() {
			return searchFormSubmit();
		} );
		$('#revise_text').focus();
	}
	return false;
}

function PopIt(obj) {
	if (obj) {
		targetObj = obj;
	}
	/*
	if (!searchtext) {
		lastKeyUpHandler = document.onkeyup;
		document.onkeyup = function(e) {
			if (!e) {
				//if the browser did not pass the event information to the
				//function, we will have to obtain it from the event register
				if ( window.event ) {
					//Internet Explorer
					e = window.event;
				} else {
					//total failure, we have no way of referencing the event
					return;
				}
			}
			if ( typeof( e.keyCode ) == 'number' ) {
				e = e.keyCode;
			} else if ( typeof( e.which ) == 'number' ) {
				e = e.which;
			} else if ( typeof( e.charCode ) == 'number'  ) {
				e = e.charCode;
			} else {
				return;
			}

			if (e >= 48 && e <= 57) {
				var i = e - 48;
				var link = document.getElementById('link' + i);
				if (link && link.href) {
					window.location = link.href;
					return;
				}
			} else if (e == 27) {
				PopIt(this);
			} else if (e == 86 && !document.getElementById('revise_text')) {
				//86 is v
				Revise();
			}
		};
	} else {
		targetObj.focus();
		document.onkeyup = lastKeyUpHandler;
		lastKeyUpHandler = '';
		if (sEnd >= 0) {
			 setSelectionRange(activeElement,sStart, sEnd);
		}
		sStart = sEnd = -1;
		$('#dialog-box').dialog('close');
		searchtext = '';
		return;
	}
	*/
	var text = '';
	if (document.selection) {
		text =  document.selection.createRange().text;
	} else {
		text  = (obj.value).substring(obj.selectionStart, obj.selectionEnd);
	}
	if (!text) {
		html = '<p id="popbox_inner">' + mw.msg('popbox_no_text_selected') + '</p>';

		$('#dialog-box').html(html);
		$('#dialog-box').dialog({
			modal: true,
			width: 400,
			title: mw.msg('popbox_related_articles'),
			minHeight: 200,
			closeText: 'x',
		});
		return;
	}
	search(text);
}

/*
function findPosPopBox(obj) {
	var curleft = curtop = 0;
	if (obj.offsetParent) {
		curleft = obj.offsetLeft
		curtop = obj.offsetTop
		while (obj = obj.offsetParent) {
			curleft += obj.offsetLeft
			curtop += obj.offsetTop
		}
	}
	return [curleft - 480,curtop + 20];
}
*/

$(document).ready( function() {
	// window.isGuided is set to true or false from guided or advanced editors (respectively)
	if (window.isGuided) {
		// Guided editing

		for (var j = 0; j < document.editform.elements.length; j++) {
			document.editform.elements[j].onfocus = focusHandler;
			document.editform.elements[j].onblur  = function() {};
		}

		$('#weave_button').click( PopItFromGuided );

		//guidedMSIEKeys();
	} else {
		// Advanced editing

		$('#weave_button').click( function() {
			var textbox = $('#wpTextbox1')[0];
			PopIt(textbox);
			return false;
		} );
	}
} );

}(jQuery, mediaWiki) );
