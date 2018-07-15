(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.FlagAsDetails = {
		option: '',
		qfa_id: '',
		question: '',
		answer: '',

		popModal: function() {
			$.get('/Special:BuildWikihowModal?modal=flagasdetails', $.proxy(function(data) {
				$.modal(data, {
					zIndex: 100000007,
					maxWidth: 450,
					minWidth: 450,
					overlayCss: { "background-color": "#000" }
				});
				this.addHandlers();
			},this));
		},

		addHandlers: function() {
			//submit
			$('#wh_modal_btn_prompt').click($.proxy(function() {
				this.submitForm();
			},this));

			//x or skip
			$('#wh_modal_close, #wh_modal_btn_skip').click(function() {
				$.modal.close();
			});
		},

		submitForm: function() {
			var comment = $("<div/>").html($('#fad_details').val()).text();

			$.post(
					WH.QAWidget.endpoint,
					{
						a: 'aq_flag_details',
						qfa_id: this.qfa_id,
						details: comment
					}
			);

			$.modal.close();
		}
	}

}($,mw));