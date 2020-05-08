(function( window, document, $) {
	'use strict';

	function trackEvent(name, obj) {
		var href = $(obj).prop('href');
		WH.event(name, { 'dest_url': href } );
	}

	$('#tc_featured_section .responsive_thumb a').click(function() {
		trackEvent('lp_section_teachers_corner_feat_click_go_em', this);
	});

	$('#tc_whats_new_section .sd_thumb a').click(function() {
		trackEvent('lp_section_teachers_corner_whats_new_click_go_em', this);
	});

	$('#tc_digital_learning_section .responsive_thumb a').click(function() {
		trackEvent('lp_section_teachers_corner_digital_learning_click_go_em', this);
	});

	$('#tc_general_help_section .responsive_thumb a').click(function() {
		trackEvent('lp_section_teachers_corner_general_help_click_go_em', this);
	});

	$('#tc_classroom_mgmt_section .responsive_thumb a').click(function() {
		trackEvent('lp_section_teachers_corner_classroom_mgmt_click_go_em', this);
	});

	$('#tc_learning_diff_section .responsive_thumb a').click(function() {
		trackEvent('lp_section_teachers_corner_learning_difficulties_click_go_em', this);
	});

	$('#tc_ela_section .responsive_thumb a').click(function() {
		trackEvent('lp_section_teachers_corner_ELA_click_go_em', this);
	});

	$('#tc_math_science_section .responsive_thumb a').click(function() {
		trackEvent('lp_section_teachers_corner_math_science_click_go_em', this);
	});

	$('#tc_for_your_students_section .responsive_thumb a').click(function() {
		trackEvent('lp_section_teachers_corner_for_your_students_click_go_em', this);
	});

	$('#tc_downloadable_resources_section .sd_thumb a').click(function() {
		trackEvent('lp_section_teachers_corner_downloadable_resources_click_go_em', this);
	});

}(window, document, jQuery));
