<?php

/**
 * This class defines allowed events and their parameteres, as per the PM sheet:
 * https://docs.google.com/spreadsheets/d/1SOZU7nRDz4Vkn_1UOkunNsgY8P_Tm_NSTZk1pqllGLc/
 */
class EventConfig
{
	// 0 = requester name
	// 1 = additional parameters
	const EVENTS = [
		// please add new events at the beginning of this array,
		// so they are sorted chronologically in descending order
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

		// TODO: remove old aliases:
		'covid_guide_download' => [ 'Emily', [], 'lp_resource_parents_guide_covid_click_download_em' ],
		'covid_parents_guide' => [ 'Emily', [], 'cat_promo_parents_guide_covid_click_go_em' ],
		'hp_amazon_ignite_btn' => [ 'Elizabeth', [], 'hp_promo_amazon_ignite_click_go_ecd' ],
		'hp_cover_letter_btn' => [ 'Elizabeth', [], 'hp_promo_cover_letter_course_click_go_ecd' ],
		'hp_covid_resources_btn' => [ 'Emily', [], 'hp_section_covid_resources_click_go_em' ],
		'covid_readmore' => [ 'Emily', [], 'all_banner_covid_click_expand_em' ],
		'covid_close' => [ 'Emily', [], 'all_banner_covid_click_close_em' ],

		'svideoview' => [ 'Aaron', [] ],
		'svideoplay' => [ 'Aaron', [] ],
	];
}
