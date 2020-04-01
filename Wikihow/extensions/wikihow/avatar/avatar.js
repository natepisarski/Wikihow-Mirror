var button_edit = true;
var imgIn = '/images/avatarIn';
var imgOut = '/images/avatarOut';
var imgServer = '';
var extension = '';
var tmpFile = '';
var nonModalImageLoadFailed = false;


/**
 * Check if an avatar's URL is broken, and swap it for the default as needed.
 *
 * @param {HTMLImageElement} image Image to auto-replace src for
 */
function autoReplaceBrokenAvatar( image ) {
	var test = new Image();
	test.onerror = function () {
		test.onerror = null;
		image.src = '/skins/WikiHow/images/80x80_user.png';
	};
	test.src = image.src;
}


//var md5HashingFunction = $.md5;
function getImgDir(name) {
	var hash = jQuery.md5(name);
	return hash.substring(0,1) + "/" + hash.substring(0,2) + "/";
}

function changeMessage() {
	var reasonval = document.forms.rejectReason.reason[document.forms.rejectReason.reason.selectedIndex].value;

	if (reasonval == 'inappropriate') {
		document.forms.rejectReason.reason_msg.value = msg_inappropriate;
		jQuery('reason_msg').html( msg_inappropriate );
		jQuery('reason_msg').focus();
	} else if (reasonval == 'copyright') {
		document.forms.rejectReason.reason_msg.value = msg_copyright;
		jQuery('reason_msg').html( msg_copyright );
		jQuery('reason_msg').focus();
	} else if (reasonval == 'other') {
		document.forms.rejectReason.reason_msg.value = msg_other;
		jQuery('reason_msg').html( msg_other );
		jQuery('reason_msg').focus();
	}

}

function avatarAccept(uid) {
	var url = '/Special:Avatar?type=accept&uid='+uid;

	new Ajax.Request(url, {
		method: 'get',
		onSuccess: function(transport) {
			var response = transport.responseText || "No response.";
			if (response.match("SUCCESS")) {
				alert('Avatar accepted.');
			jQuery('div_'+uid).hide();
			} else  if (response.match("FAILED")) {
				alert('Avatar trying to accept failed.');
			}
		},
		onFailure: function(){
			alert('Failed ajax call accepting avatar.'+transport.responseText);
		}
	});
}

function avatarReject(item,uid) {
	document.forms.rejectReason.reasonUID.value = uid;
	document.forms.rejectReason.reason.selectedIndex = 0;
	jQuery('avatarModalPage').show();
	jQuery('reason_msg').html( msg_inappropriate );
}

function avatarRejectReset() {
	document.forms.rejectReason.reason.selectedIndex = 0;
	document.forms.rejectReason.reasonUID.value = 0;
	document.forms.rejectReason.reason_msg.value = msg_inappropriate;
	jQuery('reason_msg').html( msg_inappropriate );
	//document.forms.rejectReason.reasonOther.value = '';
	//jQuery('reasonOtherSpan').hide();
	jQuery('avatarModalPage').hide();
}
function avatarReject2() {
	var uid = document.forms.rejectReason.reasonUID.value;
	var reason = document.forms.rejectReason.reason[document.forms.rejectReason.reason.selectedIndex].value;
	var message = document.forms.rejectReason.reason_msg.value;

	if (uid == 0 ) {
		alert("Called with invalid ID."+uid);
		return false;
	}
	var url = '/Special:Avatar?type=reject&uid='+uid+'&r='+reason+'&m='+encodeURIComponent(message);

	new Ajax.Request(url, {
		method: 'get',
		onSuccess: function(transport) {
			var response = transport.responseText || "No response.";
			if (response.match("SUCCESS")) {
				alert('Avatar rejected.');
			} else  if (response.match("FAILED")) {
				alert('Avatar trying to reject failed.');
			}
			jQuery('div_'+uid).hide();
			avatarRejectReset();
		},
		onFailure: function(transport){
			alert('Failed ajax call rejecting avatar.'+transport.responseText);
		}
	});
}

function uploadImageLink() {
	gatTrack("Profile","Begin_avatar_upload","Begin_avatar_upload");

	if ((BrowserDetect.browser == 'Explorer') || (BrowserDetect.browser == 'Chrome')) {
		window.location.href = '/Special:Avatar?type=nonmodal&new=1';
	} else {
		window.location.href = '/Special:Avatar?type=nonmodal&new=1';
		/* REMOVE POPUP
		jQuery('avatarModalPage').show();
		jQuery('avatarModalContainer').css( 'height', "100px" );
		*/
	}
}

function removeButton() {
	var ajaxUrl = "/Special:Avatar";
	var p2 = "type=unlink";
	var conf = confirm("Are you sure you want to permanently remove your user picture?");
	if (conf == true) {
		jQuery.ajax({
			url: ajaxUrl,
			data: p2,
			success: function(transport) {
				var response = transport.responseText || "No response.";
				if (response.match("SUCCESS")) {
				} else  if (response.match("FAILED")) {
					alert('Avatar could not be removed.');
				}
				window.location.reload();
			},
			error: function(){
				alert('Failed while removing avatar.');
			}
		});
	}

}

function avatarReset() {
	var imgDir = getImgDir(wgUserID + ".jpg");
	var imgblock = "               <div id='avatarJS'> \n";
	imgblock += "                  <img src=\""+ imgServer + imgIn +"/"+ imgDir + wgUserID+".jpg' id='avatarIn' />\n";
	imgblock += "               </div> \n";
	imgblock += "               <div id='avatarPreview'>\n";
	imgblock += "               Cropped Preview:<br />\n";
	imgblock += "               <div id='avatarPreview2'>\n";
	imgblock += "               </div>\n";
	imgblock += "               </div>\n";
	jQuery('avatarImgBlock').html( imgblock );
	jQuery('avatarCrop').hide();
	jQuery('avatarResponse').html( '' );
	jQuery('uploadedfile').val( '' );

}

function closeButton() {
/*
	var initimg = new Image();
	initimg.name = imgOut + '/' + wgUserID + '.jpg';
	initimg.onerror = function() {
		avatarReset();
	};
	initimg.src = imgServer + imgOut + '/' + wgUserID + '.jpg';
	jQuery('avatarModalPage').hide();
*/
	if (nonModal) {
		window.location.href=wgServer+'/'+userpage;
	} else {
		location.reload();
	}
}

function dynLoadPrototype() {
	if (typeof jQuery != 'undefined') jQuery.noConflict();

	var proto=document.createElement('script');
	proto.setAttribute("type","text/javascript");
	proto.setAttribute("src", "/extensions/wikihow/common/cropper/lib/prototype.js");
	document.getElementsByTagName("head")[0].appendChild(proto); ;

	var scriptac=document.createElement('script');
	scriptac.setAttribute("type","text/javascript");
	scriptac.setAttribute("src", "/extensions/wikihow/common/cropper/lib/scriptaculous.js?load=builder,dragdrop");
	document.getElementsByTagName("head")[0].appendChild(scriptac);

	var cropper=document.createElement('script');
	cropper.setAttribute("type","text/javascript");
	cropper.setAttribute("src", "/extensions/wikihow/common/cropper/cropper.js");
	document.getElementsByTagName("head")[0].appendChild(cropper);

  var fileref=document.createElement("link")
  fileref.setAttribute("rel", "stylesheet")
  fileref.setAttribute("type", "text/css")
  fileref.setAttribute("href", "/extensions/wikihow/common/cropper/cropper.css")
	document.getElementsByTagName("head")[0].appendChild(fileref);
}

function editButton() {
	var initimg = new Image();

	if ((BrowserDetect.browser == 'Explorer') || (BrowserDetect.browser == 'Chrome')) {
		window.location.href = '/Special:Avatar?type=nonmodal';
	} else {
		window.location.href = '/Special:Avatar?type=nonmodal';
		/* REMOVE POPUP
		if (button_edit) {
			jQuery('avatarPreview2').html( '' );

			initimg.name = imgIn + '/' + wgUserID + '.jpg';
			initimg.onload = cropperCenter;
			initimg.onerror = loadFailure;
			initimg.src = imgServer + imgIn + '/' + wgUserID + '.jpg?' + Math.floor(Math.random() * 99999);
			onCropCall();

			button_edit = false;
		}
		document.getElementById('avatarModalPage').style.display = 'block';
		*/
	}
	return false;
}

function initNonModal() {
	var initimg = new Image();
	var imageName = '';
	jQuery('avatarPreview2').html( '' );

	var imgDir = '';
//	if (avatarReload){
		imgDir = getImgDir("tmp_" + wgUserID + ".jpg");
		imageName = imgServer + imgIn + '/' + imgDir + 'tmp_' + wgUserID + '.jpg?' + Math.floor(Math.random() * 99999);
//	} else {
//		imgDir = getImgDir(wgUserID + ".jpg");
//		imageName = imgServer + imgIn + '/' + imgDir + wgUserID + '.jpg?' + Math.floor(Math.random() * 99999);
//	}
	initimg.name = imageName;

	initimg.onload = function() {
		cropperCenter;
		jQuery('avatarCrop').show();
	}

	initimg.onerror = function() {
		if (avatarReload){
			loadFailure(this.name);
		}

		jQuery('avatarCrop').hide();
	}
	initimg.src = imageName;

	onCropCall();

	if (avatarNew) {jQuery('avatarCrop').hide();}
	return false;
}

function setActionDIV() {
	var initimg = new Image();
	var imgDir = getImgDir(wgUserID + '.jpg');
	initimg.name = imgOut + '/' + imgDir + wgUserID + '.jpg';
	initimg.onload = function() {
		jQuery('#avatarULaction').html("<a href onclick=\"removeButton();return false;\" onhover=\"background-color: #222;\" >remove</a> | <a href onclick=\"editButton();return false;\" onhover=\"background-color: #222;\">edit</a>");
		jQuery('#avatarULimg').attr("src", initimg.src);
		jQuery('#avatarULimg').show();
	};
	initimg.onerror = function() {
		jQuery('#avatarULimg').hide();
		jQuery('#avatarULaction').html("<div class='avatarULtextBox'><a href id='gatUploadImageLink' onclick=\"uploadImageLink();return false;\">Upload Image</a></div>");
	};
	initimg.src = imgServer + imgOut + '/' + imgDir + wgUserID + '.jpg?' + Math.floor(Math.random() * 99999);

}

function getNewPic() {
   var newBlock = "<img src='' alt='Source Image' id='avatarIn' />";
	jQuery('avatarJS').html( newBlock );
	jQuery('avatarPreview2').html( '' );

	//AIM.submit(this, {'onStart' : startCallback, 'onComplete' : completeCallback})
	//document.avatarFileSelectForm.submit();
}


function cropperCenter() {
	var totwidth = jQuery('avatarImgBlock').css( 'width' ).replace('px','');
	var leftmarg = jQuery('avatarImgBlock').css( 'marginLeft' ).replace('px','');
	var newmarg = (totwidth - this.width - 100 )/2;
	jQuery('avatarImgBlock').css( 'marginLeft', newmarg + 'px' );
//	alert(this.name + ' is ' + this.width + ' by ' + this.height + ' pixels in size. centered:'+newmarg);

	return true;
}

function getWidthAndHeight() {
	var totwidth = jQuery('avatarImgBlock').css( 'width' ).replace('px','');
	var leftmarg = jQuery('avatarImgBlock').css( 'marginLeft' ).replace('px','');
	var newmarg = (totwidth - this.width - 100 )/2;

	jQuery('avatarImgBlock').css( 'marginLeft', newmarg + 'px' );
	var imgDir = getImgDir('tmp_' + wgUserID + '.' + extension);
	document.getElementById('avatarIn').src = imgIn + '/' + getImgDir + 'tmp_' + wgUserID + '.'+ extension +'?'+ Math.floor(Math.random() * 99999);

	onCropCall();
	return true;
}

function loadFailure(name) {
	var mname = '';
	if (this.name == '') {
		mname = name;
	} else {
		mname = this.name;
	}

	alert(mname + ' failed to load.');
	return true;
}


function ajaxCropit() {
	jQuery.ajax( {
		url: '/Special:Avatar',
		data: {
			x1: jQuery( '#x1' ).val(),
			y1: jQuery( '#y1' ).val(),
			x2: jQuery( '#x2' ).val(),
			y2: jQuery( '#y2' ).val(),
			image: document.crop.image.value,
			width: jQuery( '#width' ).val(),
			height: jQuery( '#height' ).val(),
			cropflag: document.crop.cropflag.value,
			type: 'crop'
		},
		success: function(response) {
			var response = response || "No response.";
			if (response.match("SUCCESS")) {
				jQuery('avatarResponse').html( 'File cropped and resized.' );
				closeButton();
			} else  if (response.match("FAILED")) {
				jQuery('avatarResponse').html( 'File could not be processed.' );
			}
		},
		error: function() {
			alert('Failed Crop.');
		}
	} );
}

function onEndCrop( coords, dimensions ) {

	jQuery( '#x1' ).val( coords.x1 );
	jQuery( '#y1' ).val( coords.y1 );
	jQuery( '#x2' ).val( coords.x2 );
	jQuery( '#y2' ).val( coords.y2 );
	jQuery( '#width' ).val( dimensions.width );
	jQuery( '#height' ).val( dimensions.height );
	document.crop.cropflag.value = 'true';

}

function onCropCall() {
	cropperObj = new Cropper.ImgWithPreview(
		'avatarIn',
		{
			previewWrap: 'avatarPreview2',
			ratioDim: { x: 80, y: 80 },
			minWidth: 80,
			minHeight: 80,
			displayOnInit: true,
			onEndCrop: onEndCrop
		}
	);
}


function startCallback() {
	return true;
}

function completeCallback(response) {
	var status = '';
	var msg = '';
	var basename = '';

	var json = response.evalJSON(true);

	for (var i in json) {
		if (typeof(i) != 'undefined') {
			if (i == 'status'){
				status = json[i];
			} else if (i == 'msg') {
				msg = json[i];
			} else if (i == 'basename') {
				basename = json[i];
			} else if (i == 'extension') {
				extension = json[i];
			}
		}
	}

	if (msg.match('has been uploaded')) {
		jQuery('avatarCrop').show();
		jQuery('avatarModalContainer').css( 'height', "475px" );
		document.getElementById('avatarResponse').innerHTML = msg;

		var newImg = new Image();
		var imgDir = getImgDir('tmp_' + wgUserID + '.' + extension);
		newImg.name = imgServer + imgIn + '/' + imgDir + 'tmp_' + wgUserID + '.'+ extension +'?'+ Math.floor(Math.random() * 99999);
		newImg.onload = getWidthAndHeight;
		newImg.onerror = loadFailure;
		newImg.src = imgServer + imgIn + '/' + imgDir + 'tmp_' + wgUserID + '.'+ extension +'?'+ Math.floor(Math.random() * 99999);

		document.crop.image.value = 'tmp_' + wgUserID + '.'+ extension;
	} else {
		document.getElementById('avatarResponse').innerHTML = msg;
	}
}

/**
 * *
 * *  AJAX IFRAME METHOD (AIM)
 * *  http://www.webtoolkit.info/
 * *
 * **/

AIM = {

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

	loaded : function(id, c) {
		var i = document.getElementById(id);
		if (i.contentDocument) {
			var d = i.contentDocument;
		} else if (i.contentWindow) {
			var d = i.contentWindow.document;
		} else {
			var d = window.frames[id].document;
		}
		if (d.location.href == "about:blank") {
			return;
		}

		if (typeof(i.onComplete) == 'function') {
			i.onComplete(d.body.innerHTML);
		}
	}
}


var BrowserDetect = {
	init: function () {
		this.browser = this.searchString(this.dataBrowser) || "An unknown browser";
		this.version = this.searchVersion(navigator.userAgent)
			|| this.searchVersion(navigator.appVersion)
			|| "an unknown version";
		this.OS = this.searchString(this.dataOS) || "an unknown OS";
	},
	searchString: function (data) {
		for (var i=0;i<data.length;i++)	{
			var dataString = data[i].string;
			var dataProp = data[i].prop;
			this.versionSearchString = data[i].versionSearch || data[i].identity;
			if (dataString) {
				if (dataString.indexOf(data[i].subString) != -1)
					return data[i].identity;
			}
			else if (dataProp)
				return data[i].identity;
		}
	},
	searchVersion: function (dataString) {
		var index = dataString.indexOf(this.versionSearchString);
		if (index == -1) return;
		return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
	},
	dataBrowser: [
		{
			string: navigator.userAgent,
			subString: "Chrome",
			identity: "Chrome"
		},
		{ 	string: navigator.userAgent,
			subString: "OmniWeb",
			versionSearch: "OmniWeb/",
			identity: "OmniWeb"
		},
		{
			string: navigator.vendor,
			subString: "Apple",
			identity: "Safari",
			versionSearch: "Version"
		},
		{
			prop: window.opera,
			identity: "Opera"
		},
		{
			string: navigator.vendor,
			subString: "iCab",
			identity: "iCab"
		},
		{
			string: navigator.vendor,
			subString: "KDE",
			identity: "Konqueror"
		},
		{
			string: navigator.userAgent,
			subString: "Firefox",
			identity: "Firefox"
		},
		{
			string: navigator.vendor,
			subString: "Camino",
			identity: "Camino"
		},
		{		// for newer Netscapes (6+)
			string: navigator.userAgent,
			subString: "Netscape",
			identity: "Netscape"
		},
		{
			string: navigator.userAgent,
			subString: "MSIE",
			identity: "Explorer",
			versionSearch: "MSIE"
		},
		{
			string: navigator.userAgent,
			subString: "Gecko",
			identity: "Mozilla",
			versionSearch: "rv"
		},
		{ 		// for older Netscapes (4-)
			string: navigator.userAgent,
			subString: "Mozilla",
			identity: "Netscape",
			versionSearch: "Mozilla"
		}
	],
	dataOS : [
		{
			string: navigator.platform,
			subString: "Win",
			identity: "Windows"
		},
		{
			string: navigator.platform,
			subString: "Mac",
			identity: "Mac"
		},
		{
			   string: navigator.userAgent,
			   subString: "iPhone",
			   identity: "iPhone/iPod"
		},
		{
			string: navigator.platform,
			subString: "Linux",
			identity: "Linux"
		}
	]

};
BrowserDetect.init();

// Show the #uploadedfile button only if the user selected an image
jQuery(document).on('change', '#uploadedfile', function(e) {
	var submitButton = jQuery("#gatAvatarImageSubmit");
	if (jQuery(this).val()) {
		submitButton.removeClass("disabled").prop('disabled', false);
	} else {
		submitButton.addClass("disabled").prop('disabled', true);
	}
});

// Auto-repair broken avatar images
$( '#avatarULimg' ).each( function () {
	autoReplaceBrokenAvatar( this );
} );

jQuery( '#gatAvatarCropAndSave' ).click( ajaxCropit );
jQuery( '#gatAvatarCancel' ).click( closeButton );

$('.editAvatar').click(function() {
	editButton();
	return false;
});

$('.removeAvatar').click(function() {
	removeButton();
	return false;
});

$('.avatar_upload').click(function() {
	uploadImageLink();
	return false;
});

window.uploadImageLink = uploadImageLink;
window.removeButton = removeButton;
window.closeButton = closeButton;
window.editButton = editButton;
window.initNonModal = initNonModal;
window.ajaxCropit = ajaxCropit;

if ( window.initAvatarPage ) {
	window.initAvatarPage();
}
