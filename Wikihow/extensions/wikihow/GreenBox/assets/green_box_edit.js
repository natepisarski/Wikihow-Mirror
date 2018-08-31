(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.GreenBoxEdit = {
		tool_url: '/Special:GreenBoxEditTool',
		step: null,

		init: function(step) {
			this.step = step;
			this.addHandlers();
			this.openEditUI();
		},

		addHandlers: function() {
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
						this.insertGreenBoxContent(data.green_box_content);
					},this),
					'json'
				);
			}
		},

		insertGreenBoxContent: function(green_box_content) {
			//translate HTML line feeds
			green_box_content = green_box_content.replace(/<br\s?\/?>/gim,'\n');

			$('#green_box_edit_tool textarea').val(green_box_content.trim());
		},

		readyDeleteButton: function() {
			//set current state
			this.updateDeleteButtonDisableState();

			//add the handler
			$('#green_box_edit_tool textarea').on('keyup', $.proxy(function() {
				this.updateDeleteButtonDisableState();
			},this));
		},

		updateDeleteButtonDisableState: function() {
			var disable_state = $('#green_box_edit_tool textarea').val().trim().length ? 0 : 1;
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

			$.post(
				this.tool_url,
				{
					'action': 'save',
					'page_id': mw.config.get('wgArticleId'),
					'step_info': $('#green_box_edit_tool').closest('li').find('.stepanchor').prop('name'),
					'content': $('#green_box_edit_tool textarea').val()
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

		deleteContent: function() {
			this.refreshBox(''); //we'll refresh after the save, but make it look immediate
			$('#green_box_edit_tool textarea').val('');
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
		}
	}

})(jQuery, mw);