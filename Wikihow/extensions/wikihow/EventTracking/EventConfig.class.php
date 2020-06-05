<?php

/**
 * This class defines allowed events and their parameteres, as per the PM sheet:
 * https://docs.google.com/spreadsheets/d/1SOZU7nRDz4Vkn_1UOkunNsgY8P_Tm_NSTZk1pqllGLc/
 */
class EventConfig
{
	// 0 => requester name
	// 1 => additional parameters (optional)
	const EVENTS = [
		// please add new events at the beginning of this array,
		// so they are sorted chronologically in descending order
		'cat_promo_concepttest_02_click_open_em' => [ 'Emily', ['type', 'user_state'] ],
		'article_popup_expertQandA_test_click2_choose_ecd' => [ 'Elizabeth', ['type'] ],
		'article_button_expertQandA_test_click2_open_ecd' => [ 'Elizabeth', [] ],
		'article_promo_slider_click_go_ecd' => [ 'Elizabeth', ['type', 'dest_url'] ],
		'article_popup_expertQandA_test_click_choose_ecd' => [ 'Emily', ['type'] ],
		'article_button_expertQandA_test_click_open_ecd' => [ 'Emily', [] ],
		'article_section_TOC_click_go_em' => [ 'Emily', [ 'link_title' ] ],
		'article_section_communityQandA_seemore_click_expand_em' => [ 'Emily', [] ],
		'hp_promo_concepttest_01_click_open_em' => [ 'Emily', [ 'type', 'user_state' ] ],
		'prebid_ad_prerender' => [ 'Aaron', [] ],
		'gpt_ad_rendered' => [ 'Aaron', [] ],
		'all_button_create_account_click_go_em' => [ 'Emily', [ 'account_type' ] ],
		'article_byline_hover_learnmore_click_go_em' => [ 'Emily', [] ],
		'all_footer_social_links_click_go_em' => [ 'Emily', [ 'type' ] ],
		'article_meta_print_click_go_em' => [ 'Emily', [] ],
		'all_popup_ccpa_optout_apply_click_go_em' => [ 'Emily', [] ],
		'all_nav_search_query_submit_go_em' => [ 'Emily', [] ],
		'all_nav_search_box_click_focus_em' => [ 'Emily', [] ],
		'article_heading_recipe_ingredients_list_click_go_em' => [ 'Emily', [] ],
		'all_footer_sitewide_links_click_go_em' => [ 'Emily', [ 'dest_url' ] ],
		'lp_section_teachers_corner_downloadable_resources_click_go_em' => [ 'Emily', [ 'dest_url' ] ],
		'lp_section_teachers_corner_for_your_students_click_go_em' => [ 'Emily', [ 'dest_url' ] ],
		'lp_section_teachers_corner_math_science_click_go_em' => [ 'Emily', [ 'dest_url' ] ],
		'lp_section_teachers_corner_ELA_click_go_em' => [ 'Emily', [ 'dest_url' ] ],
		'lp_section_teachers_corner_learning_difficulties_click_go_em' => [ 'Emily', [ 'dest_url' ] ],
		'lp_section_teachers_corner_classroom_mgmt_click_go_em' => [ 'Emily', [ 'dest_url' ] ],
		'lp_section_teachers_corner_general_help_click_go_em' => [ 'Emily', [ 'dest_url' ] ],
		'lp_section_teachers_corner_digital_learning_click_go_em' => [ 'Emily', [ 'dest_url' ] ],
		'lp_section_teachers_corner_whats_new_click_go_em' => [ 'Emily', [ 'dest_url' ] ],
		'lp_section_teachers_corner_feat_click_go_em' => [ 'Emily', [ 'dest_url' ] ],
		'article_section_alt_health_med_disclaimer_click_go_em' => [ 'Emily', [] ],
		'hp_section_new_articles_click_go_em' => [ 'Emily', [] ],
		'hp_section_expert_interviews_click_go_em' => [ 'Emily', [] ],
		'hp_section_featured_videos_click_go_em' => [ 'Emily', [] ],
		'hp_section_featured_articles_click_go_em' => [ 'Emily', [] ],
		'hp_section_trending_click_go_em' => [ 'Emily', [] ],
		'hp_section_expert_coauthor_click_go_em' => [ 'Emily', [] ],
		'article_promo_newsletter_slider_click_go_em' => [ 'Emily', [] ],
		'lp_resource_parents_guide_covid_click_download_em' => [ 'Emily', [] ],
		'cat_promo_parents_guide_covid_click_go_em' => [ 'Emily', [] ],
		'hp_promo_amazon_ignite_click_go_ecd' => [ 'Elizabeth', [] ],
		'hp_promo_cover_letter_course_click_go_ecd' => [ 'Elizabeth', [] ],
		'hp_section_covid_resources_click_go_em' => [ 'Emily', [] ],
		'all_banner_covid_click_expand_em' => [ 'Emily', [] ],
		'all_banner_covid_click_close_em' => [ 'Emily', [] ],
		// please add new events AT THE BEGINNING of this array
	];

	const DOMAINS = [
		'www.wikihow-fun.com' => true,
		'www.wikihow.com' => true,
		'www.wikihow.fitness' => true,
		'www.wikihow.health' => true,
		'www.wikihow.legal' => true,
		'www.wikihow.life' => true,
		'www.wikihow.mom' => true,
		'www.wikihow.pet' => true,
		'www.wikihow.tech' => true,
		'ar.wikihow.com' => true,
		'www.wikihow.cz' => true,
		'de.wikihow.com' => true,
		'es.wikihow.com' => true,
		'fr.wikihow.com' => true,
		'hi.wikihow.com' => true,
		'id.wikihow.com' => true,
		'www.wikihow.it' => true,
		'www.wikihow.jp' => true,
		'ko.wikihow.com' => true,
		'nl.wikihow.com' => true,
		'pt.wikihow.com' => true,
		'ru.wikihow.com' => true,
		'th.wikihow.com' => true,
		'www.wikihow.com.tr' => true,
		'www.wikihow.vn' => true,
		'zh.wikihow.com' => true,
	];
}
