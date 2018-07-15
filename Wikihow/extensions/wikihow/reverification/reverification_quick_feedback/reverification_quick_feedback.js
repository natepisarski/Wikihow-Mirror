(function($, mw)  {
	'use strict';
	window.WH = window.WH || {};
	window.WH.ReverficationQuickFeedback = {
		ACTION_NEXT: 'next',
		ACTION_REVERIFY: 'reverify',
		ACTION_EDIT: 'edit',
		ACTION_FLAG: 'flag',
		ACTION_SKIP: 'skip',
		endpoint: '/Special:ReverificationQuickFeedback',
		responseData: {a: '', aid: 0, rever_id: 0, rid_new: 0, rid_reset: 0},
		$root: $('#rvq_tools_container'),

		init: function() {
			this.initListeners();
			this.getNext();
		},

		initListeners: function() {
			var $container = $('#rvq_tools_container');
			$container.on('click', '#rvq_btn_verified, #wpSave', $.proxy(function(e) {
				e.preventDefault();
				this.onReverify();
			}, this));

			$container.on('click', '#rvq_btn_cancel, #mw-editform-cancel', $.proxy(function(e) {
				e.preventDefault();
				this.onCancel();
			}, this));

			$container.on('click', '#rvq_btn_edit', $.proxy(function(e) {
				e.preventDefault();
				this.onEdit();
			}, this));


			$container.on('click', '#rvq_btn_flag', $.proxy(function(e) {
				e.preventDefault();
				this.onFlag();
			}, this));

			$container.on('click', '#rvq_btn_skip', $.proxy(function(e) {
				e.preventDefault();
				this.onSkip();
			}, this));

			$container.on('click', '#wpPreview', $.proxy(function(e) {
				e.preventDefault();
				this.onPreview();
			}, this));

			$container.on('submit', '#editform', $.proxy(function(e) {
				// We're ignoring the edit form submit events since we have other click handlers for
				// each of the edit form actions
				e.preventDefault();
			}, this));
		},

		getNext: function() {
			this.setLoading(true);
			$.post(this.endpoint,
				this.getPostVars(this.ACTION_NEXT),
				function (result) {
					WH.ReverficationQuickFeedback.loadResult(result);
				},
				'json'
			);
		},

		loadResult: function(result) {
			// Clean out the editing data (wikitext, preview html) when loading a new article
			console.log(result);
			this.clearEditingData();
			this.responseData = result;
			if (result['rever_id']) {
				this.setTitle(result['title'], result['title_url']);
				this.setQuickFeedback(result['feedback_user'], result['quick_feedback']);
				$('#rvq_article').html(result['html']);
				this.setLoading(false);
				this.scrollToTop();
			} else if (result['error_msg']) {
				this.setEndOfQueue();
				this.setTitle(mw.msg('rvq_error', result['error_msg']));
			} else if (result['status_msg']) {
				this.setEndOfQueue();
				this.setTitle(mw.msg('rvq_status', result['status_msg']));
			}
			else {
				this.setEndOfQueue();
				this.setTitle(mw.msg('rvq_status_eoq'));
			}

		},

		setTitle: function(title, url) {
			if (url) {
				$('.firstHeading').html($('<a>').attr('href', url).attr('target', '_blank').html(title));
			} else {
				$('.firstHeading').html(title);
			}

		},

		setQuickFeedback: function(name, feedback) {
			$('#rvq_quick_feedback').html(mw.msg('rvq_quick_feedback', feedback));
			$('#rvq_quick_feedback_label').html(mw.msg('rvq_quick_feedback_label', name))
		},

		setEndOfQueue: function() {
			this.$root.addClass('rvq_eoq');
			this.setTitle();
			$('#rvq_quick_feedback').html('');
		},

		getPostVars: function(action) {
			return {
				a: action,
				rever_id: this.responseData['rever_id'],
				rid_new: this.responseData['rid_new'],
				aid: this.responseData['r_aid'],
				rid_reset: mw.util.getParamValue('rid_reset')
			}
		},

		onReverify: function() {
			if ($('#wpSummary').val() == "") {
				alert(mw.msg('rvq_edit_summary_error'));
				return;
			}

			this.setEditing(false);
			this.setLoading(true);

			var vars = this.getPostVars(this.ACTION_REVERIFY);
			vars['wikitext'] = $('#wpTextbox1').val();
			vars['edit_summary']= $('#wpSummary').val();

			$.post(this.endpoint,
				vars,
				function (result) {
					WH.ReverficationQuickFeedback.loadResult(result);
				},
				'json'
			);
		},

		onFlag: function() {
			this.setLoading(true);

			var vars = this.getPostVars(this.ACTION_FLAG);
			$.post(this.endpoint,
				vars,
				function (result) {
					WH.ReverficationQuickFeedback.loadResult(result);
				},
				'json'
			);
		},

		onSkip: function() {
			this.setLoading(true);

			var vars = this.getPostVars(this.ACTION_SKIP);
			$.post(this.endpoint,
				vars,
				function (result) {
					WH.ReverficationQuickFeedback.loadResult(result);
				},
				'json'
			);
		},

		onEdit: function() {
			var vars = this.getPostVars(this.ACTION_EDIT);

			$.get(this.endpoint,
				vars,
				function (result) {
					document.getElementById('rvq_editor_wikitext').innerHTML = result;
					$('#wpSave').attr('value', mw.msg('rvq_btn_verified'));
					WH.ReverficationQuickFeedback.setEditing(true);

				}
			);
		},

		onPreview: function() {
			// According to MW, this is only used if the wikitext contains magic wwords such as {{PAGENAME}}
			// See: http://www.mediawiki.org/wiki/Manual:Live_preview
			var thisTitle = mw.config.get('wgPageName').substring(1);

			$.ajax({
				url: '/index.php',
				type: 'POST',
				data: $('#editform').serialize() + '&wpPreview=true&live=true&action=edit&title=' + thisTitle,
				success: function(data) {
					var previewElement = $(data).find('preview').first();

					var previewContainer = $('#rvq_preview');
					if ( previewContainer && previewElement ) {
						previewContainer.html(previewElement.first().text()).show();
						WH.ReverficationQuickFeedback.scrollToTop();
					}
				}
			});
		},

		onCancel: function() {
			this.clearEditingData();
			this.setEditing(false);
			this.scrollToTop();
		},

		clearEditingData: function() {
			$('#rvq_preview, #rvq_editor_wikitext').html('');
		},

		scrollToTop: function() {
			$('body').animate({scrollTop:0});
		},


		setLoading: function(isLoading) {
			if (isLoading) {
				this.$root.addClass('rvq_loading');
				$('.firstHeading').html('');
				$('#rvq_quick_feedback_label').html('');
				$('#rvq_quick_feedback').html(mw.msg('rvq_status_loading'));
				$('#rvq_article').slideUp();
				$('#rvq_tools').slideUp();
			} else {
				this.$root.removeClass('rvq_loading');
				$('#rvq_article').slideDown();
				$('#rvq_tools').slideDown();
			}
		},

		setEditing: function(isEditing) {
			if (isEditing) {
				this.$root.addClass('rvq_editing');
				$('#wpSummary').val(mw.msg('rvq_edit_summary'));
			} else {
				this.$root.removeClass('rvq_editing');
			}
		}
	};

	$(document).ready(WH.ReverficationQuickFeedback.init());

}(jQuery, mw));