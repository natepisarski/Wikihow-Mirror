//JS to fire the User's First Edit modal window
//should only be included when we KNOW it's the user's first edit
//we define this by the cookie firstEditPop
(function () {
	
	$(document).ready(function() {
		popFirstEditModal();
	});

	function popFirstEditModal() {
		var url = "/extensions/wikihow/common/jquery.simplemodal.1.4.4.min.js";
		$.getScript(url, function() {
			$.get("/Special:BuildWikihowModal?modal=firstedit", function(data) {
				$.modal(data, { 
					zIndex: 100000007,
					maxWidth: 450,
					minWidth: 450,
					overlayCss: { "background-color": "#000" }
				});
				addHandlers();
			});
		});
	}

	function addHandlers() {
		$('#wh_modal_close').click(function() {
			gatTrack("First_Edit_Popup", "click_close", "Editing_popup");
			WH.maEvent("first_edit_dialog_close", { category: 'first_edit_dialog' }, false);
			$.modal.close();
		});
		
		$('#wh_modal_btn_edit').click(function() {
			gatTrack("First_Edit_Popup", "click_edit", "Editing_popup");
			WH.maEvent("first_edit_dialog_edit", { category: 'first_edit_dialog' }, false);
			$.modal.close();
			return false;
		});
		
		$('#wh_modal_btn_prompt').click(function() {
			var href = $(this).attr('href');
			
			var action = '';
			if (href == '/Special:Spellchecker') {
				action = 'click_spelling';
				action_ma = 'first_edit_dialog_click_spell';
			}
			else if (href == '/Special:CategoryGuardian') {
				action = 'click_category';
				action_ma = 'first_edit_dialog_click_category';
			}
			else if (href == '/Special:EditFinder/Topic') {
				action = 'click_topic';
				action_ma = 'first_edit_dialog_click_topic';
			}
			gatTrack("First_Edit_Popup", action, "Editing_popup");
			WH.maEvent(action_ma, { category: 'first_edit_dialog' }, false);
			
			window.location.href = href;
			return false;
		});
		
		WH.maEvent("first_edit_dialog_show", { category: 'first_edit_dialog' }, false);
	}
	
}());
	