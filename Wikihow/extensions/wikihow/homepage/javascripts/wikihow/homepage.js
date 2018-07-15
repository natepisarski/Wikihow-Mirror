( function($, mw) {

	$(document).ready(function() {
		// show the placeholder text here and hide the label
		$('#hp_search_label').hide();
		$('#hp_search').attr('placeholder', $('#hp_search').attr('ph'));

		$('.slider ul').hipsterSlider({
			infinite: true,
			autoplay: true,
			pager: true,
			orientation: $.hipsterSlider.HORIZONTAL,
			touch: true,
			touchTolerance: 1, // transition immediately on beginning of swipe
			touchDirectionTolerance: 1, // transition immediately on beginning of swipe
			autoresize: true,
			pagerTargetSelector: $('#hp_middle2'),
			pagerTargetInsertionMethod: $.hipsterSlider.METHOD_AFTER,
			onUpdate: update_title,
			buttonsWrap: true, // give the buttons their own div
			autoplayPause: 6000 // set to sync with desktop transition
		});
		$('#hp_search').on('focus', function(){
			$('#hp_search').attr('placeholder', '');
			$('#hp_search').attr('searching', 'on');
		});
		$('#hp_search').on('blur', function(){
			$('#hp_search').attr('searching', '');
		});
		// need this to deal with fractional widths
		$('#content .search').width(parseInt($('#content .search').width()));
	});

	/*
	 * Used in hipsterSlider to update the "howto" title banner
	 */
	function update_title() {
		// add a 500ms delay to wait for animation to finish
		setTimeout(function() {
			var selected = +($('.selected').text()); // convert to int
			var desiredText = $('#hp_top_' + selected + ' .hp_text').attr('title');
			if ($('#hp_search').attr('searching') != 'on') {
				$('#hp_search').attr('placeholder', desiredText);
			}
		}, 500); // 500ms should sync up with the transitions
	}
	
}(jQuery, mediaWiki) );
