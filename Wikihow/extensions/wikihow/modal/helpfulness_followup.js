(function ($, mw) {
	window.WH = window.WH || {};
	window.WH.HelpfulnessFollowup = {
		
		cat: 'helpful_followup_prompt',
		
		popModal: function () {
			var url = "/extensions/wikihow/common/jquery.simplemodal.1.4.4.min.js";
			$.getScript(url, function() {
				$.get('/Special:BuildWikihowModal?modal=helpfulness', function(data) {
					$.modal(data, { 
						zIndex: 100000007,
						maxWidth: 400,
						minWidth: 400,
						overlayCss: { "background-color": "#000" }
					});
					WH.HelpfulnessFollowup.addHandlers();
			
					//modal fired
					WH.whEvent(window.WH.HelpfulnessFollowup.cat, WH.HelpfulnessFollowup.getAction('show'), $.cookie('hfpop').split("|")[1]);
				});
			});
		},
		
		addHandlers: function () {
			var label = $.cookie('hfpop') ? $.cookie('hfpop').split("|")[1] : $('#hfu_article').val();
			
			//x
			$('#wh_modal_close').click(function() {
				WH.whEvent(window.WH.HelpfulnessFollowup.cat, WH.HelpfulnessFollowup.getAction('x'), label);
				WH.HelpfulnessFollowup.closeDialog();
				return false;
			});
			
			//secondary button
			$(document).on('click', '#wh_modal_btn_skip', function() {
				if ($('#hfu_default').is(':visible')) {
					action = WH.HelpfulnessFollowup.getAction('no');
				}
				else if ($('#hfu_methods').is(':visible')) {
					action = WH.HelpfulnessFollowup.getAction('cancel');
				}
				WH.whEvent(window.WH.HelpfulnessFollowup.cat, action, label);
				WH.HelpfulnessFollowup.closeDialog();
			});
			
			//primary button
			$(document).on('click', '#wh_modal_btn_prompt', function() {
				if ($('#hfu_default').is(':visible')) {
					WH.HelpfulnessFollowup.getMethods();
					WH.whEvent(window.WH.HelpfulnessFollowup.cat, WH.HelpfulnessFollowup.getAction('yes'), label);
					
					$('#hfu_default').hide();
					$('#hfu_methods').show(function() {
						$('#hfu_method_list').slideDown();
					});
				}
				else if ($('#hfu_methods').is(':visible')) {
					//submit methods
					//let's count the checkmarks
					var the_count = $('.hfu_checkbox:checked').length;
					if (the_count > 0) { 
						label = the_count;
					}
					else {
						if ($('#hfu_none').is(':checked')) {
							label = 'none';
						}
						else if ($('#hfu_forgot').is(':checked')) {
							label = 'forgot';
						}
						else {
							label = 'blank';
						}
					}
					
					WH.whEvent(window.WH.HelpfulnessFollowup.cat, WH.HelpfulnessFollowup.getAction('submit'), label);

					$('#hfu_methods').hide();
					$('#hfu_nothanks_div').hide();
					$('#wh_modal_close').hide();
					$('#wh_modal_top').html('Thank You!');
					$('#hfu_ty').show();
				}
				else {
					WH.whEvent(window.WH.HelpfulnessFollowup.cat, WH.HelpfulnessFollowup.getAction('done'), label);
					WH.HelpfulnessFollowup.closeDialog();
				}
				
			});
			
			//no thanks checkbox
			$('#hfu_nothanks').click(function() {
				var daysMs = 90 * 24 * 60 * 60 * 1000;
				var expireDate = new Date();
				expireDate.setDate(expireDate.getDate() + daysMs);
				document.cookie = "hfno=1; expires="+expireDate.toGMTString()+"; path=/";
				WH.whEvent(window.WH.HelpfulnessFollowup.cat, WH.HelpfulnessFollowup.getAction('neveragain'), label);
			});
		},
		
		getAction: function(action) {
			if ($('#hfu_default').is(':visible')) {
				action_prefix = 'hf_prompt';
			}
			else if ($('#hfu_methods').is(':visible')) {
				action_prefix = 'hf_list';
			}
			else {
				action_prefix = 'hf_thanks';
			}
			return action_prefix+'_'+action;
		},
		
		setTrigger: function() {			
			var daysMs = 90 * 24 * 60 * 60 * 1000; //3 months
			var expireDate = new Date();
			var now = new Date();
			expireDate.setDate(expireDate.getDate() + daysMs);
			
			//set the cookie after 42 seconds
		   setTimeout(function() {
			   document.cookie = "hfpop="+now.toGMTString()+"|"+mw.config.get('wgArticleId')+"; expires="+expireDate.toGMTString()+";path=/";
			   WH.whEvent(window.WH.HelpfulnessFollowup.cat, "hf_timer_start", mw.config.get('wgTitle'));
		   }, 42000);
			
		},
		
		getMethods: function(id) {
			var id = $.cookie('hfpop').split("|")[1];
			$.getJSON('/Special:BuildWikihowModal?modal=helpfulness2&aid='+id, function(data) {
				$('#hfu_title').html(data.title);
				$('#hfu_method_list').prepend(data.html);
			});
		},
		
		closeDialog: function() {
			//eat cookie
			$.removeCookie('hfpop');
			$.modal.close();
		}
		
	};
	
	$(document).ready(function() {
		//setting trigger cookie?
		if ($('#hfpop').length && !$.cookie('hfpop')) {
			WH.HelpfulnessFollowup.setTrigger();
		}
		
		//launching modal?
		if ($.cookie('hfpop') && !$.cookie('hfno')) {
				var triggerdate = $.cookie('hfpop').split("|")[0];
				var then = new Date(triggerdate).getTime();
				var now = new Date().getTime();
				
				//has it been over an hour?
				if (now && then && (now-then) > 60*60*1000) {
					WH.HelpfulnessFollowup.popModal();
				}
		}
	});
		
}(jQuery, mw));

