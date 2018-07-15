(function ( mw, $ )  { 
	function postVideoUpdate() {
		$.post('/Special:VideoEmbedHelperTool',
			{citation: $('#citation').val(),target:$('#target').val(),videoUrl:$('#videoUrl').val()},
			function(result) {
				var vidLink = $('<a>');
				vidLink.attr('href', result['videoUrl']);
				vidLink.text('Video');
				var pageLink = $('<a>');
				pageLink.attr('href', result['articleURL']);
				pageLink.text('page');
				if (result['success']){
					$('#results').html(vidLink.prop('outerHTML') + ' successfully embedded in ' + pageLink.prop('outerHTML'));
				} else {
					$('#results').html(vidLink.prop('outerHTML') + ' failed to embed in ' + pageLink.prop('outerHTML'));
				}
			},
			'json'
		);
	}
	function clearForm() {
		$('#citation').val('');
		$('#target').val('');
		$('#videoUrl').val('');
		$('#results').html('');
	}
	$(document).ready( function() {
		$('#formsubmit').click(postVideoUpdate);
		$('#formclear').click(clearForm);
	});
}( mediaWiki , jQuery ) );
