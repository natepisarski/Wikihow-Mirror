( function ( mw, $ ) {

	var toolUrl = '/Special:SocialProof';

	function setClickListener() {
		$('#socialproof_stats_table').on("click", ".sortby", function(e) {
			$('#socialproof_stats_table').empty();
			e.preventDefault();

			var $spinner = $.createSpinner({ size: 'large', type: 'block' });
			$( '#socialproof_stats_table' ).append( $spinner )

			var sort = e.currentTarget.id;

			$.get(toolUrl, {'getstats': true, 'sortby': sort}, function(result) {
				$spinner.remove();
				$('#socialproof_stats_table').append(result);

				//toggle link
				if (sort == 'action_sort') {
					$('#action_sort').unwrap();
					$('#click_sort').wrap("<a href='sort_by_click'></a>");
				}
			});
		});
	}

	$(document).ready( function() {
		if ($('#socialproof_stats_table').length) {
			var $spinner = $.createSpinner({ size: 'large', type: 'block' });
			$( '#socialproof_stats_table' ).append( $spinner );

			$.get(toolUrl, {'getstats': true}, function(result) {
				$spinner.remove();
				$( '#socialproof_stats_table' ).append(result);
				setClickListener();
			});
		}
	});

}( mediaWiki, jQuery ) );
