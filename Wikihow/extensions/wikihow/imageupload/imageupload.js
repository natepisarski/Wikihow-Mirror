( function($, mw) {
'use strict';

// We make sure this module exists first
window.WH.ImageUpload = window.WH.ImageUpload || {};

/*
 * Used to display and use the Image Upload dialog box on the edit pages.
 */

// Class for image uploads
function ImageUploader() {

	// pseudo-private vars (we want them accessible by public methods,
	// so we don't use Crockford's private var pattern)
	this.m_textAreaID = 0;

	this.m_stashFileKey = '';
}

// put up the modal dialog
ImageUploader.prototype.doEIUModal = function (origin) {
	if (origin === 'advanced') {
		this.m_textAreaID = 'wpTextbox1';
	} else if (origin === 'intro') {
		this.m_textAreaID = 'summary';
	} else if (origin === 'steps') {
		this.m_textAreaID = 'steps_text';
	} else if (origin === 'ingredients') {
		this.m_textAreaID = 'ingredients_text';
	} else if (origin === 'tips') {
		this.m_textAreaID = 'tips_text';
	} else if (origin === 'warnings') {
		this.m_textAreaID = 'warnings_text';
	} else if (origin === 'thingsyoullneed') {
		this.m_textAreaID = 'thingsyoullneed_text';
	}

	$('#iu_initial').show();
	$('#iu_upload').hide();
	$('#iu_error_message, .iu_wheel').hide();
	$('#iu_choose_file').removeProp('disabled');

	// set up after-dialog load callback
	var onloadFunc = function () {
		if (!mw.config.get('wgUserName')) {
			var msg = mw.message('eiu-user-name-not-found-error').plain();
			$('#iu_start_error')
				.show()
				.html(msg);
			// disable all possible actions on this page
			$('#iu_initial>*').not('#iu_start_error').hide();
		}

		// move down 90px so it doesn't interfere w/ our toolbar
		var dialog_box = $('#iu_dialog').parent('.ui-dialog');
		if (dialog_box.offset().top < 92)  {
			dialog_box.offset({top: 92});
		}
	};

	$('#iu_dialog').dialog({
		width: 750,
		height: 600,
		modal: true,
		closeText: 'x',
		dialogClass: 'modal2',
		open: onloadFunc
	});

	this.logAction('Show_img_dialog', origin);
};

ImageUploader.prototype.getUploadStashFileKey = function () {
	return this.m_stashFileKey;
};

ImageUploader.prototype.insertWikiTag = function (wikitag) {
	WH.ImageUpload.whCursorHelper.insertAtCursor(this.m_textAreaID, wikitag);
};

ImageUploader.prototype.onFileUploaded = function (response) {
	var result, error = false;

	$('.iu_wheel').hide();

	if (!response) {
		error = true;
	} else {
		try {
			result = JSON.parse(response);
			if (!result) {
				error = true;
			} else {
				if (result.error) {
					error = result.error;
				}
			}
		} catch (e) {
			console.log('ImageUploader.onFileUploaded: Error parsing JSON from server: ' + response, e);
			error = true;
		}
	}

	if (!error) {
		// we now show the licensing html

		// hide initial form; show license selection form
		$('#iu_initial').hide();
		$('#iu_upload').show();

		if (result.url) {
			var img = $('<img src="' + result.url + '" />').attr({'width': 200});
			$('#iu_preview_image')
				.show()
				.html( img );
		}

		// set default filename to be filename of what the user uploaded
		if (result.mwname) {
			this.checkMediawikiImage(result.mwname);
			$('#iu_mw_name')
				.val(result.mwname)
				.focus();
			$('#iu_image_description span').html(result.ext);
		}

		// store the stash filekey for the "insert image" next step
		if (result.filekey) {
			this.m_stashFileKey = result.filekey;
		}
	} else {
		var msg;
		if (typeof error === 'string') {
			msg = error;
		} else {
			msg = mw.message('eiu-network-error').plain();
		}
		$('#iu_error_message')
			.show()
			.html(msg);
	}
};

ImageUploader.prototype.logAction = function (action, value) {
	var category = 'Registered_Editing';
	var label = 'Add_image_dialog';
	if (typeof value !== 'undefined') {
		gatTrack(category, action, label, value);
	} else {
		gatTrack(category, action, label);
	}
};

ImageUploader.prototype.checkMediawikiImage = function (mwname) {
	$('#iu_wheel_exists_check').show();
	$.post( '/Special:ImageUploader',
		{ 'action': 'checkfilename', 'mwname': mwname } )
		.done( function (data) {
			var result, error;
			try {
				result = JSON.parse(data);
				if (!result || result.valid) {
					error = '';
				} else {
					error = result.error;
				}
			} catch (e) {
				console.log('ImageUploader.checkMediawikiImage: Error parsing JSON from server: ' + data, e);
				error = '';
			}
			if (error) {
				$('#iu_file_exists')
					.html(error)
					.show();
			} else {
				$('#iu_file_exists')
					.html('')
					.hide();
			}
		} )
		.fail( function () {
			// show nothing if existence check fails
			$('#iu_file_exists').html('');
		} )
		.always( function () {
			$('.iu_wheel').hide();
		} );
};

ImageUploader.prototype.insertImage = function (action, params) {
	$('#iu_wheel_insert').show();

	var that = this;
	$.post( action, params )
		.done( function (data) {
			var result, error;
			try {
				result = JSON.parse(data);
				if (result && !result.error) {
					error = '';
				} else {
					error = result.error;
				}
			} catch (e) {
				console.log('ImageUploader.insertImage: Error parsing JSON from server: ' + data, e);
				error = mw.message('eiu-network-error').plain();
			}

			if (error) {
				$('#iu_insert_error')
					.html(error)
					.show();
			} else {
				var wikitag = result.tag;
				that.insertWikiTag(wikitag);

				// auto-populate edit summary if empty
				var node = $('#wpSummary1');
				if (node.length && node.val() === '') {
					var newSummary = mw.message('added-image').plain();
					var chosenImageFilename = $('#iu_filename');
					if (chosenImageFilename.length) {
						newSummary += ': ' + chosenImageFilename.val();
					}
					node.val(newSummary);
				}

				$('#iu_insert_error')
					.html('')
					.hide();

				$('#iu_dialog').dialog('close');

				that.logAction('Add_img');
			}
		} )
		.fail( function () {
			var error = mw.message('eiu-network-error').plain();
			$('#iu_insert_error')
				.html(error)
				.show();
		} )
		.always( function () {
			$('#iu_insert_image').removeProp('disabled');
			$('.iu_wheel').hide();
		} );
};

// globally publish this class
window.WH.ImageUpload.ImageUploader = ImageUploader;

$(document).ready( function() {

	// singleton instance
	WH.ImageUpload.whCursorHelper = new WH.ImageUpload.WHCursorHelper();

	// singleton instance of this class
	var imageUploader = new ImageUploader();
	// this instance needs to be referenced globally on occasion
	WH.ImageUpload.imageUploader = imageUploader;

	$('.add_image_button').click( function() {
		// reads <a data-section='...'> attribute in Guided Editor html
		var section = $(this).data('section');
		imageUploader.doEIUModal(section);
		return false;
	} );

	$('#iu_uploadform').submit( function() {
		return AIM.submit( this, {
			onStart: function () {
				$('#iu_wheel_upload').show();
				$('#iu_error_message').hide();
			},
			onComplete: function (response) {
				imageUploader.onFileUploaded(response);
			}
		} );
	} );

	$('#iu_choose_file').click( function() {
		// We create a new hidden <input type=file> element every time this button
		// is pushed, because this DOM node would stop emitting the "change" event
		// after its first usage.
		$('.iu_uploadfile').remove();
		$('#iu_uploadform').append($("<input type='file' class='iu_uploadfile' name='wpUploadFile' size='1' />"));
		$('.iu_uploadfile').change( function() {
			if ($(this).val()) {
				$('#iu_uploadform').submit();
			}
		} );
		$('.iu_uploadfile').click();
		return false;
	} );

	$('input[name="license"]').change( function() {
		$('#iu_insert_image').removeProp('disabled');
	} );

	$('#iu_insert_image').click( function() {
		$('#iu_insert_image').prop('disabled', true);
		$('#iu_detailsform').submit();
		return false;
	} );

	$('#iu_detailsform').submit( function() {
		var action = this.getAttribute('action');
		var params = $(this).serialize() + '&filekey=' + imageUploader.getUploadStashFileKey();
		imageUploader.insertImage(action, params);
		return false;
	} );

	var timer = null;
	var WAIT_AFTER_KEYSTROKE = 500; // milliseconds
	$('#iu_mw_name').keyup( function () {
		var mwname = $(this).val();
		if (timer) {
			clearTimeout(timer);
			timer = null;
		}
		timer = setTimeout( function () {
			timer = null;
			imageUploader.checkMediawikiImage(mwname);
		}, WAIT_AFTER_KEYSTROKE);
	} );
} );

// For the advanced edit, the button is added later than the dom-ready event,
// so we need to use the window-load event.
$(window).load( function() {
	$('#imageupload_button').click( function() {
		WH.ImageUpload.imageUploader.doEIUModal('advanced');
		return false;
	} );
} );

}(jQuery, mediaWiki) );
