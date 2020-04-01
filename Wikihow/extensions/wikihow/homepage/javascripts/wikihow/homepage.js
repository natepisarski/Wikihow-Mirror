( function($, mw) {

	$(document).ready(function() {
		// show the placeholder text here and hide the label
		$('#hp_search_label').hide();
		$('#hp_search').attr('placeholder', $('#hp_search').attr('ph'));


		$('#hp_search').on('focus', function(){
			$('#hp_search').attr('placeholder', '');
			$('#hp_search').attr('searching', 'on');
		});
		$('#hp_search').on('blur', function(){
			$('#hp_search').attr('searching', '');
		});
	});
}(jQuery, mediaWiki) );
