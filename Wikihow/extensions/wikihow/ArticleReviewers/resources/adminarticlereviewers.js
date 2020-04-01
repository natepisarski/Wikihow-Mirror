(function(mw, $) {
$(document).ready(function() {

$("#ImageUploadFile").change(function() {
	var fileName = $(this).val();
	if ( !fileName ) {
		return;
	}

	$.ajax({
		type: 'POST',
		data: new FormData($('#AdminArticleReviewers')[0]),
		cache: false,
		contentType: false,
		processData: false
	})
	.done(function(data, textStatus, jqXHR) {
		if (data['success']) {
			$("#ex_completed").append("<li>" + data['imgLink'] + "</li>");
		} else {
			$("#ex_error").append("<li>" + fileName.replace(/^.*\\/, '') + " - " + data['errorMsg'] + "</li>");
		}
	})
	.fail(function(jqXHR, textStatus, errorThrown) {
		var data = JSON.parse(jqXHR.responseText);
		alert('The file ' + fileName.replace(/^.*\\/, '') + ' could not be uploaded: ' + data);
	});
});

});
}(mediaWiki, jQuery));
