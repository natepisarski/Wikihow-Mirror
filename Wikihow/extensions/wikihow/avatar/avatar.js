var button_edit = true;
var imgIn = '/images/avatarIn';
var imgOut = '/images/avatarOut';
var imgServer = '';
var extension = '';
var tmpFile = '';
var nonModalImageLoadFailed = false;

//var md5HashingFunction = $.md5;
function getImgDir(name) {
	var hash = jQuery.md5(name);
	return hash.substring(0,1) + "/" + hash.substring(0,2) + "/";
}

function changeMessage() {
	var reasonval = document.forms.rejectReason.reason[document.forms.rejectReason.reason.selectedIndex].value;

	if (reasonval == 'inappropriate') {
		document.forms.rejectReason.reason_msg.value = msg_inappropriate;
		$('reason_msg').innerHTML = msg_inappropriate;
		$('reason_msg').focus();
	} else if (reasonval == 'copyright') {
		document.forms.rejectReason.reason_msg.value = msg_copyright;
		$('reason_msg').innerHTML = msg_copyright;
		$('reason_msg').focus();
	} else if (reasonval == 'other') {
		document.forms.rejectReason.reason_msg.value = msg_other;
		$('reason_msg').innerHTML = msg_other;
		$('reason_msg').focus();
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
			$('div_'+uid).style.display = 'none';
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
	$('avatarModalPage').style.display = 'block';
	$('reason_msg').innerHTML = msg_inappropriate;
}

function avatarRejectReset() {
	document.forms.rejectReason.reason.selectedIndex = 0;
	document.forms.rejectReason.reasonUID.value = 0;
	document.forms.rejectReason.reason_msg.value = msg_inappropriate;
	$('reason_msg').innerHTML = msg_inappropriate;
	//document.forms.rejectReason.reasonOther.value = '';
	//$('reasonOtherSpan').style.display = 'none';
	$('avatarModalPage').style.display = 'none';
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
			$('div_'+uid).style.display = 'none';
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
		$('avatarModalPage').style.display = 'block';
		$('avatarModalContainer').style.height = "100px";
		*/
	}
}

function removeButton() {
	var ajaxUrl = "/Special:Avatar";
	var p2 = "type=unlink";
	var conf = confirm("Are you sure you want to permanently remove your user picture?");
	if (conf == true) {
		$.ajax({
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
	$('avatarImgBlock').innerHTML = imgblock;
	$('avatarCrop').style.display = 'none';
	$('avatarResponse').innerHTML = '';
	$('uploadedfile').value = '';

}

function closeButton() {
/*
	var initimg = new Image();
	initimg.name = imgOut + '/' + wgUserID + '.jpg';
	initimg.onerror = function() {
		avatarReset();
	};
	initimg.src = imgServer + imgOut + '/' + wgUserID + '.jpg';
	$('avatarModalPage').style.display = 'none';
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
			$('avatarPreview2').innerHTML = '';

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
	$('avatarPreview2').innerHTML = '';

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
		$('avatarCrop').style.display = 'block';
	}

	initimg.onerror = function() {
		if (avatarReload){
			loadFailure(this.name);
		}

		$('avatarCrop').style.display = 'none';
	}
	initimg.src = imageName;

	onCropCall();

	if (avatarNew) {$('avatarCrop').style.display = 'none';}
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
	$('avatarJS').innerHTML = newBlock;
	$('avatarPreview2').innerHTML = '';

	//AIM.submit(this, {'onStart' : startCallback, 'onComplete' : completeCallback})
	//document.avatarFileSelectForm.submit();
}


function cropperCenter() {
	var totwidth = $('avatarImgBlock').style.width.replace('px','');
	var leftmarg = $('avatarImgBlock').style.marginLeft.replace('px','');
	var newmarg = (totwidth - this.width - 100 )/2;
	$('avatarImgBlock').style.marginLeft = newmarg + 'px';
//	alert(this.name + ' is ' + this.width + ' by ' + this.height + ' pixels in size. centered:'+newmarg);

	return true;
}

function getWidthAndHeight() {
	var totwidth = $('avatarImgBlock').style.width.replace('px','');
	var leftmarg = $('avatarImgBlock').style.marginLeft.replace('px','');
	var newmarg = (totwidth - this.width - 100 )/2;

	$('avatarImgBlock').style.marginLeft = newmarg + 'px';
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
	url = "/Special:Avatar";

	var p2 = "x1="+$( 'x1' ).value ;
	p2 += "&y1="+$( 'y1' ).value ;
	p2 += "&x2="+$( 'x2' ).value ;
	p2 += "&y2="+$( 'y2' ).value ;
	p2 += "&image="+document.crop.image.value ;
	p2 += "&width="+$( 'width' ).value ;
	p2 += "&height="+$( 'height' ).value ;
	p2 += "&height="+$( 'height' ).value ;
	p2 += "&cropflag="+document.crop.cropflag.value;
	p2 += "&type=crop";

	new Ajax.Request(url, {
		method: 'get',
		parameters: p2,
		onSuccess: function(transport) {
			var response = transport.responseText || "No response.";
//alert(response);
			if (response.match("SUCCESS")) {
				$('avatarResponse').innerHTML = 'File cropped and resized.';
				closeButton();
			} else  if (response.match("FAILED")) {
//alert(response);
				$('avatarResponse').innerHTML = 'File could not be processed.';
			}
		},
		onFailure: function(){
			alert('Failed Crop.');
		}

	});
}

function onEndCrop( coords, dimensions ) {

	$( 'x1' ).value = coords.x1;
	$( 'y1' ).value = coords.y1;
	$( 'x2' ).value = coords.x2;
	$( 'y2' ).value = coords.y2;
	$( 'width' ).value = dimensions.width;
	$( 'height' ).value = dimensions.height;
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
		$('avatarCrop').style.display = 'block';
		$('avatarModalContainer').style.height = "475px";
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
