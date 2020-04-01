(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.SpecialTechVerifyAdmin = {
        getNow : Date.now || function() { return new Date().getTime(); },
        startTime : null,
        lastVote : 0,
        lastPageId : null,
        articleVisible: false,
		tool: '/Special:TechTestingAdmin',

		showForm: function(data) {
			console.log("showForm");
			var batchName = '';
			var platform = '';
			var enabled = 0;
			var newJob = 1;
			var pageIds = "";

			if (typeof(data) != 'undefined') {
				newJob = 0;
				batchName = data.batchName;
				platform = data.platform;
				pageIds = data.pageIds;
				enabled = data.enabled ? 1 : 0;
			}
			console.log("data", data);

			var html = this.escapeHtml(Mustache.render(unescape($('#job_edit').html()), {
				enabled: enabled,
				platform: platform,
				batchName: batchName,
				pageIds: pageIds,
				existing: !newJob,
				stva_batch_label: mw.message('stva_batch_label').text(),
				stva_platform_label: mw.message('stva_platform_label').text(),
				articles_prompt: mw.message('stva_addnew_list').text(),
				articles_example: mw.message('stva_addnew_list_example').text(),
				job_submit_button: mw.message('stva_addnew_submit').text(),
				job_done_button: mw.message('stva_addnew_done').text()
			}));

			$.modal(html, {
				zIndex: 100000007,
				maxWidth: 500,
				minWidth: 500,
				overlayCss: { "background-color": "#000" }
			});

			this.addFormHandlers();
		},

		addFormHandlers: function() {
			$('.fa-times').click(function() {
				$.modal.close();
			});

			$('#stva_edit_submit').click(function() {
				WH.SpecialTechVerifyAdmin.submitForm(this);
			});
		},

		submitForm: function(obj) {
			var form = $(obj).closest('.stva_job_edit');
			this.processing(true);

			if (!this.validateForm(form)) {
				this.processing(false);
				return;
			}
			var formData = {
					action: 'save_job',
					update_batch: $(form).find('#stva_batch_name').prop('disabled'),
					batch_name: $(form).find('#stva_batch_name').val(),
					platform_name: $(form).find('#stva_platform_name').val(),
					article_list: $('#stva_article_list').length ? $('#stva_article_list').val() : ''
				};
			console.log("will submit form", formData);

			$.post(
				this.tool,
				formData,
				$.proxy(function(result) {
					if (result.success) {
						this.showMessage(result.message);
						this.showCloseButton();
					}
					else {
						this.showError(result.message);
						this.processing(false);
					}

				},this),
				'json'
			);
		},

		validateForm: function(form) {
			var err = '';

			if ($(form).find('#stva_batch_name').val().trim() == '') {
				err = mw.message('stva_err_no_batch_name').text();
			}
			else if ($(form).find('#stva_platform_name').val().trim() == '') {
				err = mw.message('stva_err_no_platform').text();
			}
			else if ($(form).data('job_id') == 0 && $(form).find('#stva_article_list').val().trim() == '') {
				err = mw.message('stva_err_no_articles').text();
			}

			if (err.length) {
				this.showError(err);
				return false;
			}

			return true;
		},

		escapeHtml: function (htmlString) {
			return $('<textarea/>').html(htmlString).text();
		},

		showMessage: function(msg) {
			$('#stva_message').removeClass('stva_error').html(msg);
		},

		showError: function(err) {
			$('#stva_message').addClass('stva_error').html(err);
		},

		showCloseButton: function() {
			$('#stva_edit_done').show().click(function() {
				$.modal.close();
				window.location.reload();
			});
		},

		processing: function(is_processing) {
			if (is_processing) {
				$('#stva_message').html('');
				$('#stva_edit_submit').fadeOut();
			}
			else {
				$('#stva_edit_submit').fadeIn();
			}
		}, 

		runReport: function(obj) {
			var batch_name = $(obj).closest('.stva_job_row').data('batch_name');
			var report_url = this.tool+'?action=run_report&batch_name='+batch_name;
			console.log("here", report_url);
			window.location.href = report_url;
		},

		toggleEnabled: function(obj) {
			var new_enabled_state;

			if ($(obj).hasClass('fa-toggle-on')) {
				$(obj).addClass('fa-toggle-off').removeClass('fa-toggle-on');
				new_enabled_state = 0;
			}
			else {
				$(obj).addClass('fa-toggle-on').removeClass('fa-toggle-off');
				new_enabled_state = 1;
			}

			var batchName = $(obj).closest('.stva_job_row').data('batch_name');
			var save_button = $(obj).siblings('.stva_change_enabled');
			console.log('new enabled', new_enabled_state);
			console.log('batch', batchName);
			console.log('button', save_button);

			$(save_button).show().click($.proxy(function() {
				$(save_button).prop('disabled',true);
				this.changeEnabled(batchName, new_enabled_state);
			},this));
		},

		changeEnabled: function(batchName, enabled) {
			console.log("change enabled");
			$.getJSON(
				this.tool+'?action=change_job_state&batch_name='+batchName+'&enabled='+enabled,
				function(data) {
					window.location.reload();
				}
			);
		},

		loadForm: function(obj) {
			var batchName = $(obj).closest('.stva_job_row').data('batch_name');

			$.getJSON(
				this.tool+'?action=get_job_details&batch_name='+batchName,
				$.proxy(function(data) {
					this.showForm(data);
				},this)
			);
		},

        initEventHandlers: function() {
			$('.stva_edit').click(function() {
				WH.SpecialTechVerifyAdmin.loadForm(this);
			});
			$('.stva_report').click(function() {
				console.log('report');
				WH.SpecialTechVerifyAdmin.runReport(this);
			});
			$('.stva_enabled').click(function() {
				WH.SpecialTechVerifyAdmin.toggleEnabled(this);
			});

            $('#platformselect .button').on("click",function() {
                var val = $('#platformselect select').val();
                var platformId = WH.SpecialTechVerify.getPlatformId();
                if (!val) {
                    return false;
                }
                $('#platformselect').hide();
                $('#header-title').show();
                $('#tool-data').show();
                WH.SpecialTechVerify.showPlatformMessageTop(val);
                if (val != platformId) {
                    WH.SpecialTechVerify.setPlatformId(val);
                    WH.SpecialTechVerify.getNext(val);
                }
                return false;
            });

			$(document).on('click', '#willtest-buttons', function(event) {
                var vote = null;
                if (!$(event.target).hasClass('button')) {
                    return;
                }
                if ($(event.target).hasClass("skip")) {
                    vote = 0;
                    WH.SpecialTechVerify.lastVote = vote;
                    var platformId = $(this).data('platformid');
                    var payload = {
                        pageid: $(this).data('page-id'),
                        revid: $(this).data('rev-id'),
                        vote: vote,
                        platformid: platformId
                    };
                    console.log('willtest buttons: skip', payload);

                    // TODO switch these when you want to save to db
                    WH.SpecialTechVerify.save(payload, 'feedbackDone');
                    //WH.SpecialTechVerify.feedbackDone(payload);
                } else {
                    console.log('willtest buttons clicked yes. will show testing buttons');
                    // show the testing-section
                    $('#testing-section').show();
                    $('#willtest-buttons').hide();
                    $('#instructions-section').hide();
                }

				return false;
			});

			$(document).on('click', '#testing-section, #time-verification-section', function(event) {
                if (!$(event.target).hasClass("button")) {
                    return;
                }

                var vote = null;
                if ($(event.target).hasClass("yes")) {
                    vote = 1;
                    var now = WH.SpecialTechVerify.getNow();
                    if ( this.id == 'testing-section' && (now - WH.SpecialTechVerify.startTime) < 30000) {
                        console.log("not enough time passsed will show verify section");
                        $(this).hide();
                        $('#time-verification-section').show();
                        return false;
                    }
                } else if ($(event.target).hasClass("no")) {
                    vote = -1;
                } else {
                    vote = 0;
                }

                WH.SpecialTechVerify.lastVote = vote;
                var platformId = $(this).data('platformid');
                var payload = {
                    pageid: $(this).data('page-id'),
                    revid: $(this).data('rev-id'),
                    vote: vote,
                    platformid:platformId
                };
                console.log(this.id + ' clicked with payload:', payload);

                if (vote == 0) {
                    WH.SpecialTechVerify.save(payload, 'feedbackDone');
                } else {
                    WH.SpecialTechVerify.save(payload, 'testedDone');
                }

				return false;
			});

			$(document).on('click', '#yesfeedback-section', function(event) {
                var payload = {};
                if (!$(event.target).hasClass('button')) {
                    return;
                }
                if ($(event.target).hasClass("skip")) {
                    console.log("feedback not given");
                    WH.SpecialTechVerify.feedbackDone(payload, '{}');
                    return false;
                }

				var platformId = $(this).data('platformid');
				var model = $('#yesfeedbackmodeltext').val();
				WH.SpecialTechVerify.setDeviceModel(platformId, model);
                var version = $('#yesfeedbackversiontext').val();
                var reason = $('#yesfeedbackselect').select2().get(0).value;
				WH.SpecialTechVerify.setDeviceVersion(platformId, version);
                payload = {
                    action: 'feedback',
                    pageid: $(this).data('page-id'),
                    revid: $(this).data('rev-id'),
                    platformid: platformId,
                    model: model,
                    version: version,
					reason: reason
                };
                if (!model && !version && !reason) {
                    return false;
                }
                console.log('yes feedback submitted', payload);
				WH.SpecialTechVerify.save(payload, 'feedbackDone');

				return false;
			});

			$(document).on('click', '#updatesheet', function(event) {

				console.log("will update sheet");
                var payload = {
                    action: 'updatesheet',
				};
				$.get(this.tool, payload).done(function() {
					console.log("update done");
				});
			});

			$('#addnew').click($.proxy(function() {
				console.log("addnew clicked");
				this.showForm();
			},this));

			$(document).on('click', '#nofeedback-section', function(event) {
                var payload = {};
                if (!$(event.target).hasClass('button')) {
                    return;
                }
                if ($(event.target).hasClass("skip")) {
                    console.log("feedback not given");
                    WH.SpecialTechVerify.feedbackDone(payload, '{}');
                    return false;
                }

                var textbox = $('#nofeedback-mainfeedback').val();
				var platformId = $(this).data('platformid');
                var model = $('#nofeedbackmodeltext').val();
				WH.SpecialTechVerify.setDeviceModel(platformId, model);
                var version = $('#nofeedbackversiontext').val();
                var reason = $('#nofeedbackselect').select2().get(0).value;
				WH.SpecialTechVerify.setDeviceVersion(platformId, version);
                if (!textbox && !model && !version && !reason) {
                    return false;
                }
                payload = {
                    action: 'feedback',
                    pageid: $(this).data('page-id'),
                    revid: $(this).data('rev-id'),
                    platformid: platformId,
                    textbox: textbox,
                    model: model,
                    version: version,
                    reason: reason
                };
                console.log('no feedback submitted', payload);

				WH.SpecialTechVerify.save(payload, 'feedbackDone');

				return false;
			});
        },

		init: function() {
			WH.xss.addToken();

            this.startTime = this.getNow();
            this.initEventHandlers();
		},

        save: function (payload, callback) {
            $.post(this.tool, payload).done($.proxy(this, callback, payload));
        },
	};
	$(document).ready(function() {
		WH.SpecialTechVerifyAdmin.init();
	});
})();
