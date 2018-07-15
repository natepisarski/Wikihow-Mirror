(function($) {
	window.WH = window.WH || {};
	window.WH.TestAnalytics = {
		
		cat: 'mobile_menu_tool_combos',
		group: '',
		cookie_name: 'ta_',
		
		initTest: function(test) {
			if (typeof(this[test]) === 'function') {
				this[test]();
				this.group = test;
				this.addHandlers();
			}
		},
		
		defaultView: function() {
			$('#icon-techfeedback').hide();
			$('#icon-rclite').hide();
			$('#icon-unitguardian').hide();
		},
			
		easy_3: function() {
			//this uses our default view
		},
			
		hard_3: function() {
			$('#icon-categoryguardian').hide();
			$('#icon-tipsguardian').hide();
			$('#icon-techfeedback').show();
			$('#icon-rclite').show();
			$('#icon-unitguardian').show();
		},
			
		easy_2: function() {
			$('#icon-techfeedback').hide();
			$('#icon-rclite').hide();
			$('#icon-unitguardian').hide();
			$('#icon-categoryguardian').show();
			$('#icon-tipsguardian').show();
		},
		
		hard_2: function() {
			$('#icon-categoryguardian').hide();
			$('#icon-tipsguardian').hide();
			$('#icon-rclite').hide();
			$('#icon-techfeedback').show();
			$('#icon-unitguardian').show();
		},
		
		addHandlers: function() {
			//menu should already be open (via optimizely)
			if ($('#mw-mf-page-left').is(':visible')) {
				WH.whEvent(WH.TestAnalytics.cat, 'mm_menu_view', WH.TestAnalytics.group, '');
			}
			
			//menu click handlers
			var toolname = '';
			$('#icon-categoryguardian a').click(function() {
				toolname = 'CategoryGuardian';
				WH.whEvent(WH.TestAnalytics.cat, 'mm_menu_click_tool', WH.TestAnalytics.group, toolname);
				$.cookie(WH.TestAnalytics.cookie_name+toolname, toolname+','+WH.TestAnalytics.group+','+1, { expires: 1 });
			});
			
			$('#icon-tipsguardian a').click(function() {
				toolname = 'TipsGuardian';
				WH.whEvent(WH.TestAnalytics.cat, 'mm_menu_click_tool', WH.TestAnalytics.group, toolname);
				$.cookie(WH.TestAnalytics.cookie_name+toolname, toolname+','+WH.TestAnalytics.group+','+1, { expires: 1 });
			});
			
			$('#icon-rclite a').click(function() {
				toolname = 'RCPatrol';
				WH.whEvent(WH.TestAnalytics.cat, 'mm_menu_click_tool', WH.TestAnalytics.group, toolname);
				$.cookie(WH.TestAnalytics.cookie_name+toolname, toolname+','+WH.TestAnalytics.group+','+1, { expires: 1 });
			});
			
			$('#icon-techfeedback a').click(function() {
				toolname = 'TechFeedback';
				WH.whEvent(WH.TestAnalytics.cat, 'mm_menu_click_tool', WH.TestAnalytics.group, toolname);
				$.cookie(WH.TestAnalytics.cookie_name+toolname, toolname+','+WH.TestAnalytics.group+','+1, { expires: 1 });
			});
			
			$('#icon-unitguardian a').click(function() {
				toolname = 'UnitGuardian';
				WH.whEvent(WH.TestAnalytics.cat, 'mm_menu_click_tool', WH.TestAnalytics.group, toolname);
				$.cookie(WH.TestAnalytics.cookie_name+toolname, toolname+','+WH.TestAnalytics.group+','+1, { expires: 1 });
			});
		},
		
		initTracking: function() {
			if ($.cookie(this.cookie_name+wgTitle)) {
				//category guardian, all other tools
				$(document).on('mousedown', '.answer-options a, .mt_button_bar a', function() {
					WH.TestAnalytics.clickCount();
				});
			}
		},
		
		clickCount: function() {
			//are we counting for this tool?
			if (!$.cookie(this.cookie_name+wgTitle)) return;
			
			//break down that cookie
			var kooky = $.cookie(this.cookie_name+wgTitle).split(',');
			var k_tool = kooky[0];
			var k_group = kooky[1];
			var k_num = parseInt(kooky[2], 10);
			
			if (k_num == 1) {
				//first vote event
				WH.whEvent(WH.TestAnalytics.cat, 'mm_tool_vote_1', k_group, k_tool);
			}
			else if (k_num >= 5) {
				//fifth (and final) vote event
				WH.whEvent(WH.TestAnalytics.cat, 'mm_tool_vote_5', k_group, k_tool);
				$.removeCookie(this.cookie_name+wgTitle);
				return;
			}
			
			//add another click
			$.cookie(this.cookie_name+wgTitle, k_tool+','+k_group+','+(k_num+1), { expires: 1 });
		}

	};
	
	$(document).ready(function() {
		WH.TestAnalytics.defaultView();
		WH.TestAnalytics.initTracking();
	});
})(jQuery);
