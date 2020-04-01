
var postcomment_request;
var postcomment_target;
var postcomment_form;

function postcommentHandler() {
	if ( postcomment_request.readyState == 4) {
		if ( postcomment_request.status == 200 || postcomment_request.status == 409) {
			var targetBox = document.getElementById(postcomment_target);
			var errorBox = $('#error-box');
			if (!targetBox) {
				if(postcomment_request.status == 409) {
					targetBox = document.getElementById(postcomment_target + "_spam");
					if(!targetBox) {
						return;
					}
				}
				else {
					return;
				}
			}
			if (postcomment_target.indexOf("preview") > 0) {
				targetBox.innerHTML = '<p class="preview_header">' + gPreviewMsg + '</p>' +  postcomment_request.responseText;
			} else {
				if (gNewpage) {
					var article = document.getElementById('noarticletext');
					if (article) article.innerHTML = '';
				}
				if (postcomment_request.status == 200) {
					errorBox.hide();
					targetBox.innerHTML += postcomment_request.responseText;
				}
				var txtbox = document.getElementById("comment_text_" + postcomment_target.replace(/postcomment_newmsg_/, ''));
				//var txtbox = document.getElementById("postcommentForm_textarea");
				//
				if (typeof txtbox !== "undefined" && txtbox) {
					if (postcomment_request.status == 200)
						txtbox.value = '';
					txtbox.disabled = false;
					txtbox.focus();
				}
				var previewBox = document.getElementById(postcomment_target.replace(/newmsg/, "preview"));
				if (previewBox) previewBox.innerHTML = "";
   				var p = document.getElementById("postcomment_progress_" + postcomment_target.replace(/postcomment_newmsg_/, ''));
    			if (p) p.setAttribute('style', 'display: none;');
				var button = document.getElementById("postcommentbutton_" + postcomment_target.replace(/postcomment_newmsg_/, ''));
				if (button) button.disabled = false;

				if (postcomment_request.status == 409) {
					if (postcomment_request.responseText) {
						errorBox.html(postcomment_request.responseText);
						errorBox.show();
					}
					postCommentReloadCaptcha();
				}
			}
		}
	}
}

function postcommentPreview (target) {
	var strResult;
	var previewBox = document.getElementById("postcomment_preview_" +target);
	if (confirm) {
		previewBox.innerHTML = gPreviewText;
		try {
			postcomment_request = new XMLHttpRequest();
		} catch (error) {
			try {
				postcomment_request = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (error) {
				return false;
			}
		}
		//set globals
		postcomment_target = "postcomment_preview_" + target;

		var parameters = "comment=" + encodeURIComponent( document.getElementsByName("comment_text_" + target)[0].value );
		postcomment_request.open('POST', gPreviewURL,true);
		postcomment_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		postcomment_request.send(parameters);

		postcomment_request.onreadystatechange = postcommentHandler;
	}
}

function postcommentPublish(target, form) {
    var parameters = "";
    for (var i=0; i < form.elements.length; i++) {
        var element = form.elements[i];
        if (parameters != "") {
            parameters += "&";
        }
        if (element.name != 'wpPreview' && element.name != 'wpDiff')
            parameters += element.name + "=" + encodeURIComponent(element.value);
    }
    var strResult;
	//set globals
	postcomment_target = target;
	postcomment_form = form;

    try {
    	postcomment_request = new XMLHttpRequest();
    } catch (error) {
    	try {
        	postcomment_request = new ActiveXObject('Microsoft.XMLHTTP');
        } catch (error) {
        	return false;
		}
	}
	//var button = document.getElementByID('postcommentbutton_' +
	var button = document.getElementById("postcommentbutton_" + target.replace(/postcomment_newmsg_/, ''));
	if (button) {
		button.disabled = true;
	}
	var txtbox = document.getElementById("comment_text_" + target.replace(/postcomment_newmsg_/, ''));
	if (txtbox)  txtbox.disabled = true;
	var p = document.getElementById("postcomment_progress_" + target.replace(/postcomment_newmsg_/, ''));
	if (p) p.setAttribute('style', 'display: inline;');

	if (document.getElementById('wpCaptchaId')) {
		parameters += "&wpCaptchaId" + document.getElementById('wpCaptchaId').value;
		parameters += "&wpCaptchaWord" + document.getElementById('wpCaptchaWord').value;
	}

	// If coming from an rc patrol quicknote dialog, and thumbs up is checked, pass along thumbs up info
	var thumbParams = '';
	if ($('input[name="qn_thumbs_check"]:checked').length) {
		thumbParams = $('#thumbUp').html().split('?');
		thumbParams = thumbParams[1].replace(/\&amp;/g,'&');
		thumbParams = thumbParams + "&thumb=1";
		isThumbedUp = true;
		$('#thumbsup-status').css('background-color','#CFC').html(wfMsg('rcpatrol_thumb_msg_complete'));
		$('.thumbbutton').css('background-position', '0 0');
	}

    postcomment_request.open('POST', gPostURL + "?fromajax=true&" + thumbParams, true);
    postcomment_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    postcomment_request.send(parameters);
    postcomment_request.onreadystatechange = postcommentHandler;

	return false;
}

/**
 * Replace the captcha with a new one
 */
function postCommentReloadCaptcha() {
	$.get(gCaptchaURL, function(data) {
		$('.fancycaptcha-wrapper').html(data);
	});
}

$(document).ready(function() {
	//cool anchor link animation
	$('#leave_msg_link').click(function() {
		var destination = ($('#leave-a-message').offset().top - $('#header').height());
		$('html, body').animate({
			scrollTop: destination
		},1000,'easeInOutExpo');
		return false;
	});

	// Delete captcha error message on captcha input field focus
	var errorBox = $('#error-box');
	$('#bodycontents').on('focus', '#wpCaptchaWord', function() {
		errorBox.hide();
		errorBox.text('');
	});
});
