(function($, mw) {

WH.ImportVideo = {};

WH.ImportVideo.changeUrl = function() {
	var url = document.getElementById('url').value;
	var params = url;
	var base = url;
	if (params.indexOf("?") > 0 ) {
		params = params.substring(params.indexOf("?") + 1);
		base = base.substring(0, base.indexOf("?") );
	} else {
		params = "";
	}
	var parts = params.split("&");
	var newparams = "";
	for (var i = 0; i < parts.length; i++) {
		var x = parts[i].split("=");
		if (x[0] != "orderby") newparams += x[0] + "=" + x[1] + "&";
	}
	url = base+ '?orderby=' + document.getElementById('orderby').value + "&" + newparams;
	if (window.location.pathname == '/index.php') {
		getRemoteContent(url, 'winpop_outer', winPopW, winPopH);
	} else {
		window.location.href = url;
	}
};

WH.ImportVideo.importvideo = function(id) {
	document.videouploadform.video_id.value = id;
	if (window.location.pathname == '/index.php') {
		// see winpop.js
		postForm('//' + window.location.hostname + '/Special:ImportVideoPopup', 'videouploadform', 'POST');
		return;
	}
	$('#dialog-box').load('/Special:ImportVideoPopup',function() {
		$('#dialog-box').dialog({
			modal: true,
			width: 600,
			title: 'Add Description',
			closeText: 'x',
		});
	});
};

var hidden = false;
WH.ImportVideo.showhidesteps = function() {
	if (hidden) {
		$('#stepsarea, #hidesteps').css('display', 'inline');
		$('#showsteps').css('display', 'none');
		hidden = false;
	} else {
		$('#stepsarea, #hidesteps').css('display', 'none');
		$('#showsteps').css('display', 'inline');
		hidden = true;
	}
};

WH.ImportVideo.throwdesc = function() {
	var word = $('#importvideo_comment').val();
	window.top.document.videouploadform.description.value = word;
	$('#dialog-box').dialog('close');
	window.top.document.videouploadform.submit();
};

})(jQuery, mw);
