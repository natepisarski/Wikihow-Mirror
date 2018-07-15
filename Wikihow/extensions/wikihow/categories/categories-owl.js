(function($, mw) {
	'use strict';

	$(document).ready(function() {
		$(document).on("click", "#cat_more", function(e){
			e.preventDefault();
			$(this).hide();
			$("#top_level_more").css("display", "inline");
		});
		$("#cat_featured_articles").slick(
			{
				slidesToShow: 3,
				centerMode: true,
				centerPadding: '116px'
			}
		);
		$("#cat_related_articles").slick(
			{
				slidesToShow: 3,
				centerMode: true,
				centerPadding: '116px'
			}
		)
	});

}(jQuery, mediaWiki));
