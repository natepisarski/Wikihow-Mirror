( function ( mw, $ ) {
	window.WH.loadfile = {
		init: function () {
					$('#ctitlesfile').change(function () {
					var filename = $('#ctitlesfile').val();
					if (!filename) {
						alert('No file selected!');
					} else {
						$('#ct-result').html('uploading file...');
						$('#classify-titles-upload-form').submit();
					}
					return false;
			 });
		}
	};
	$( function() {
		 WH.loadfile.init();
	 });
}( mediaWiki, jQuery ) );
