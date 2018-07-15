(function(mw, $) {
	window.WH = window.WH || {};

	$(document).ready(function () {
		$('#ImageUploadFile').uploadify({
			'swf': '/extensions/uploadify/uploadify.swf',
			'uploader': '/Special:AdminArticleReviewers',
			'onUploadSuccess': function (file, data, response) {
				info = JSON.parse(data);
				if (info['success']) {
					$("#ex_completed").append("<li>" + info['url'] + "</li>");
				} else {
					$("#ex_error").append("<li>" + file.name + " - " + info['message'] + "</li>");
				}
			},
			'onUploadError': function (file, errorCode, errorMsg, errorString) {
				alert('The file ' + file.name + ' could not be uploaded: ' + errorString);
			}
		});
	});

}(mediaWiki, jQuery));