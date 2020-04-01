( function(/*$, mw*/) {
'use strict';

// We make sure this module exists first
window.WH.ImageUpload = window.WH.ImageUpload || {};

/*
 * WHCursorHelper class.
 *
 * Deals with manipulation of raw wikiHow article text,
 * such as dialogs with steps, etc.
 */

function WHCursorHelper() {
	// default
	this.browser = null;
}

WHCursorHelper.prototype.getBrowser = function (txtarea) {
	if (!this.browser) {
		this.browser = (txtarea.selectionStart || txtarea.selectionStart == '0' ?
			'ff' : (document.selection ? 'ie' : false ) );
	}
	return this.browser;
};

WHCursorHelper.prototype.getCursorPos = function (txtarea) {
	var strPos = '';
	var browser = this.getBrowser(txtarea);
	if (browser == 'ie') {
		strPos = this.ieGetCursorPos(txtarea);
	}
	else if (browser == 'ff') {
		strPos = txtarea.selectionStart;
	}
	return strPos;
};

// this method was copied from here (contains explanation there):
// http://linebyline.blogspot.com/2006/11/textarea-cursor-position-in-internet.html
WHCursorHelper.prototype.ieGetCursorPos = function (textarea) {

	textarea.focus();
	var selection_range = document.selection.createRange().duplicate();

	// Create three ranges, one containing all the text before the selection,
	// one containing all the text in the selection (this already exists), and one containing all
	// the text after the selection.
	var before_range = document.body.createTextRange();
	before_range.moveToElementText(textarea);                    // Selects all the text
	before_range.setEndPoint('EndToStart', selection_range);     // Moves the end where we need it

	var after_range = document.body.createTextRange();
	after_range.moveToElementText(textarea);                     // Selects all the text
	after_range.setEndPoint('StartToEnd', selection_range);      // Moves the start where we need it

	var before_finished = false, selection_finished = false, after_finished = false;
	var before_text, untrimmed_before_text, selection_text, untrimmed_selection_text, after_text, untrimmed_after_text;

	// Load the text values we need to compare
	before_text = untrimmed_before_text = before_range.text;
	selection_text = untrimmed_selection_text = selection_range.text;
	after_text = untrimmed_after_text = after_range.text;

	// Check each range for trimmed newlines by shrinking the range by 1 character and seeing
	// if the text property has changed.  If it has not changed then we know that IE has trimmed
	// a \r\n from the end.
	do {
		if (!before_finished) {
			if (!before_range.compareEndPoints('StartToEnd', before_range)) {
				before_finished = true;
			} else {
				before_range.moveEnd('character', -1);
				if (before_range.text == before_text) {
					untrimmed_before_text += '\r\n';
				} else {
					before_finished = true;
				}
			}
		}
		if (!selection_finished) {
			if (!selection_range.compareEndPoints('StartToEnd', selection_range)) {
				selection_finished = true;
			} else {
				selection_range.moveEnd('character', -1);
				if (selection_range.text == selection_text) {
					untrimmed_selection_text += '\r\n';
				} else {
					selection_finished = true;
				}
			}
		}
		if (!after_finished) {
			if (!after_range.compareEndPoints('StartToEnd', after_range)) {
				after_finished = true;
			} else {
				after_range.moveEnd('character', -1);
				if (after_range.text == after_text) {
					untrimmed_after_text += '\r\n';
				} else {
					after_finished = true;
				}
			}
		}

	} while (!before_finished || !selection_finished || !after_finished);

	// Untrimmed success test to make sure our results match what is actually in the textarea
	// This can be removed once you're confident it's working correctly
	var untrimmed_text = untrimmed_before_text + untrimmed_selection_text + untrimmed_after_text;
	var untrimmed_successful = false;
	if (textarea.value == untrimmed_text) {
		untrimmed_successful = true;
	}
	// ** END Untrimmed success test

	var startPoint = untrimmed_before_text.length;
	//var endPoint = startPoint + untrimmed_selection_text.length;
	//var selected_text = untrimmed_selection_text;

	//alert("Start Index: " + startPoint + "\nEnd Index: " + endPoint + "\nSelected Text\n'" + selected_text + "'");
	return startPoint;
};

// Utility function to insert text into a textarea control
//
// Different similar functin at: http://alexking.org/blog/2003/06/02/inserting-at-the-cursor-using-javascript
// From: http://www.scottklarr.com/topic/425/how-to-insert-text-into-a-textarea-where-the-cursor-is/ blog
WHCursorHelper.prototype.insertAtCursor = function (areaId, text) {
	var txtarea = document.getElementById(areaId);
	//var scrollPos = txtarea.scrollTop;
	var strPos = this.getCursorPos(txtarea);

	var front = (txtarea.value).substring(0, strPos);
	var back = (txtarea.value).substring(strPos, (txtarea.value).length);
	txtarea.value = front + text + back;
	strPos = strPos + text.length;
	this.setFocusAndScroll(txtarea, strPos);
};

WHCursorHelper.prototype.setFocusAndScroll = function (txtarea, strPos) {
	var browser = this.getBrowser(txtarea);
	if (browser == 'ie') {
		txtarea.focus();
		var range = document.selection.createRange();
		range.moveStart('character', -(txtarea.value).length);
		range.moveStart('character', strPos);
		range.moveEnd('character', 0);
		range.select();
	} else { //if (browser == 'ff') {
		txtarea.selectionStart = strPos;
		txtarea.selectionEnd = strPos;
		txtarea.focus();
	}
	//txtarea.scrollTop = scrollPos;
};

window.WH.ImageUpload.WHCursorHelper = WHCursorHelper;

}(jQuery, mediaWiki) );
