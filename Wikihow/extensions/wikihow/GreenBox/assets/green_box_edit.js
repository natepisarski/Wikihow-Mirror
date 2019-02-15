(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.GreenBoxEdit = {
		tool_url: '/Special:GreenBoxEditTool',
		step: null,
		deleting: false,
		short_marker: 'SHORT',

		init: function(step) {
			this.step = step;
			this.addHandlers();
			this.openEditUI();
		},

		addHandlers: function() {
			$(document).on('change', '#green_box_type', $.proxy(function() {
				this.changeGreenBoxType();
			},this));

			$(document).on('click', '#green_box_edit_cancel', $.proxy(function() {
				this.hideEditUI();
			},this));

			$(document).on('click', '#green_box_edit_submit', $.proxy(function() {
				this.save();
			},this));

			$(document).on('click', '#green_box_edit_delete', $.proxy(function() {
				this.deleteContent();
			},this));
		},

		openEditUI: function() {
			if (typeof(this.step) === 'undefined' || this.step === null) return;

			this.deleting = false; //reset

			if ($('#green_box_edit_tool').length) {
				this.showEditUI($('#green_box_edit_tool'));
			}
			else {
				var url = this.tool_url+'?action=get_edit_box';

				$.getJSON(url, $.proxy(function(data) {
					if (data.html.length) {
						this.showEditUI(data.html);
						this.addEditToolbar();
					}
				},this));
			}
		},

		showEditUI: function(edit_tool) {
			this.processing(false);

			$(this.step).parent().append(edit_tool);

			this.addCurrentGreenBoxContent();

			$('#green_box_edit_tool').slideDown($.proxy(function() {
				this.readyDeleteButton();
			},this));
		},

		hideEditUI: function() {
			$('#green_box_edit_tool').slideUp(function() {
				$(this).find('textarea').html('');
			});
		},

		addCurrentGreenBoxContent: function() {
			var green_box = $(this.step).parent().find('.green_box');

			if (typeof(green_box) == 'undefined') {
				this.insertGreenBoxContent('');
				return;
			}
			else {
				$.post(
					this.tool_url,
					{
						'action': 'get_green_box_content',
						'page_id': mw.config.get('wgArticleId'),
						'step_info': $(this.step).closest('li').find('.stepanchor').prop('name'),
					},
					$.proxy(function(data) {

						if (data.error) {
							this.showError(data.error);
						}
						else {
							this.insertGreenBoxContent(data);
							this.setGreenBoxEditType(data);
						}

					},this),
					'json'
				);
			}
		},

		insertGreenBoxContent: function(green_box_data) {
			var content = green_box_data.green_box_content;
			var content_2 = green_box_data.green_box_content_2;

			//translate HTML line feeds
			if (content.length) content = content.replace(/<br\s?\/?>/gim,'\n');
			if (content_2.length) content_2 = content_2.replace(/<br\s?\/?>/gim,'\n');

			$('#green_box_edit_content').val(content.trim());
			$('#green_box_edit_content_2').val(content_2.trim());
			$('#green_box_edit_expert').val(green_box_data.green_box_expert);

			//hide old errors
			$('#green_box_edit_err').html('');
		},

		setGreenBoxEditType: function(green_box_data) {
			var edit_type = 'green_box'; //default;

			if (green_box_data.green_box_expert.length) {
				if (green_box_data.green_box_content_2 == this.short_marker)
					edit_type = 'green_box_expert_short';
				else if (green_box_data.green_box_content_2.length)
					edit_type = 'green_box_expert_qa';
				else
					edit_type = 'green_box_expert';
			}

			$('#green_box_type').val(edit_type);
			this.changeGreenBoxType();
		},

		readyDeleteButton: function() {
			//set current state
			this.updateDeleteButtonDisableState();

			//add the handler
			$('#green_box_edit_content').on('keyup', $.proxy(function() {
				this.updateDeleteButtonDisableState();
			},this));
		},

		updateDeleteButtonDisableState: function() {
			var disable_state = $('#green_box_edit_content').val().trim().length ? 0 : 1;
			$('#green_box_edit_delete').prop('disabled', disable_state);
		},

		//only called for the first load
		addEditToolbar: function() {
			var transparent_gif = '/skins/owl/images/1x1_transparent.gif';

			mw.loader.using(['mediawiki.action.edit', 'ext.wikihow.nonarticle_styles'], function() {
				mw.toolbar.addButtons(
					{
						imageFile: transparent_gif,
						speedTip: "Bold text",
						tagOpen: "\'\'\'",
						tagClose: "\'\'\'",
						sampleText: "Place bold text here",
						imageId: "mw-editbutton-bold"
					},
					{
						imageFile: transparent_gif,
						speedTip: "Italic text",
						tagOpen: "\'\'",
						tagClose: "\'\'",
						sampleText: "Italic text",
						imageId: "mw-editbutton-italic"
					},
					{
						imageFile: transparent_gif,
						speedTip: "Green box headline",
						tagOpen: "== ",
						tagClose: " ==",
						sampleText: "Headline text",
						imageId: "mw-editbutton-headline"
					},
					{
						imageFile: transparent_gif,
						speedTip: "Internal link",
						tagOpen: "[[",
						tagClose: "]]",
						sampleText: "Link title",
						imageId: "mw-editbutton-link"
					},
					{
						imageFile: transparent_gif,
						speedTip: "External link (remember http:// prefix)",
						tagOpen: "[",
						tagClose: "]",
						sampleText: "http://www.example.com link title",
						imageId: "mw-editbutton-extlink"
					},
					{
						imageFile: transparent_gif,
						speedTip: 'Mathematical formula (LaTeX)',
						tagOpen: '<math>',
						tagClose: '</math>',
						sampleText: 'Insert formula here',
						imageId: 'mw-editbutton-math'
					}
				);
			});
		},

		save: function() {
			this.processing(true);

			var err = this.validateAndCleanup();
			if (err.length) {
				this.showError(err);
				this.processing(false);
				return;
			}

			$.post(
				this.tool_url,
				{
					'action': 'save',
					'page_id': mw.config.get('wgArticleId'),
					'step_info': $('#green_box_edit_tool').closest('li').find('.stepanchor').prop('name'),
					'content': $('#green_box_edit_content').val(),
					'content_2': $('#green_box_edit_content_2').val(),
					'expert': $('#green_box_edit_expert').val()
				},
				$.proxy(function(data) {
					if (data.error) {
						this.showError(data.error);
					}
					else if (data.success) {
						this.refreshBox(data.html);
					}
				},this),
				'json'
			);
		},

		validateAndCleanup: function() {
			var err = '';

			if (this.deleting) {
				$('#green_box_edit_tool textarea, #green_box_edit_expert').val('');
			}
			else if ($('#green_box_type').val() == 'green_box') {
				//but we don't need these...
				$('#green_box_edit_content_2, #green_box_edit_expert').val('');
			}
			else if ($('#green_box_type').val() == 'green_box_expert') {
				//need an expert
				if ($('#green_box_edit_expert').val() == '0') err = mw.message('green_box_error_no_expert').text();

				//no Answer needed...
				$('#green_box_edit_content_2').val('');
			}
			else if ($('#green_box_type').val() == 'green_box_expert_short') {
				//need an expert
				if ($('#green_box_edit_expert').val() == '0') err = mw.message('green_box_error_no_expert').text();

				//has to be less than 140 characters
				if ($('#green_box_edit_content').val().length > 140) err = mw.message('green_box_error_too_long').text();

				//add a marker to show this is a short quote
				$('#green_box_edit_content_2').val(this.short_marker);
			}
			else if ($('#green_box_type').val() == 'green_box_expert_qa') {

				if ($('#green_box_edit_expert').val() == '0') //need an expert
					err = mw.message('green_box_error_no_expert').text();
				else if ($('#green_box_edit_content_2').val().trim() == '') //need an answer
					err = mw.message('green_box_error_no_answer').text();
			}

			return err;
		},

		deleteContent: function() {
			this.refreshBox(''); //we'll refresh after the save, but make it look immediate
			this.deleting = true;
			this.save();
		},

		showError: function(error) {
			$('#green_box_edit_err').html(error);
			this.processing(false);
		},

		refreshBox: function(html) {
			this.hideEditUI();
			var green_box = $(this.step).parent().find('.green_box');

			if ($(green_box).length) {
				$(green_box).replaceWith(html);
			}
			else {
				mw.loader.using('ext.wikihow.green_box', $.proxy(function() {
					$(this.step).after(html);
				},this));
			}
		},

		processing: function(in_process) {
			if (in_process)
				$('#green_box_edit_tool .button').hide();
			else
				$('#green_box_edit_tool .button').show();
		},

		changeGreenBoxType: function() {
			var type = $('#green_box_type').val();
			$('#green_box_edit_tool').removeClass().addClass(type+'_edit_type');

			//HACK: there's a chance we're moving from Short Quote to Q&A
			//so we'll need to remove the "SHORT" marker
			if (type == 'green_box_expert_qa' && $('#green_box_edit_content_2').val() == this.short_marker)
				$('#green_box_edit_content_2').val('');
		}
	}

})(jQuery, mw);