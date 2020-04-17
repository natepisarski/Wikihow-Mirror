( function($, mw) {
	'use strict';

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

	$('#hp_covid_resources_btn').click(function() { WH.event('hp_section_covid_resources_click_go_em'); });
	$('#hp_cover_letter_btn').click(function()    { WH.event('hp_promo_cover_letter_course_click_go_ecd'); });
	$('#hp_amazon_ignite_btn').click(function()   { WH.event('hp_promo_amazon_ignite_click_go_ecd'); });
	$('#hp_coauthor_container > .hp_thumb > a').click(function()  { WH.event('hp_section_expert_coauthor_click_go_em'); });
	$('#hp_popular_container > .hp_thumb > a').click(function()   { WH.event('hp_section_trending_click_go_em'); });
	$('#hp_featured_container > .hp_thumb > a').click(function()  { WH.event('hp_section_featured_articles_click_go_em'); });
	$('#hp_watch_container > .hp_thumb > a').click(function()     { WH.event('hp_section_featured_videos_click_go_em'); });
	$('#hp_expert_container > .hp_thumb > a').click(function()    { WH.event('hp_section_expert_interviews_click_go_em'); });
	$('#hp_newpages_container > .hp_thumb > a').click(function()  { WH.event('hp_section_new_articles_click_go_em'); });

}(jQuery, mediaWiki) );
