(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.SpecialTechVerify = {
        getNow : Date.now || function() { return new Date().getTime(); },
        startTime : null,
        platformCookie: 'stv_platform',
        deviceVersionCookie: 'stv_version',
        deviceModelCookie: 'stv_model',
        lastVote : 0,
        lastPageId : null,
        articleVisible: false,
		tool: '/Special:TechTesting',

        testedDone: function(payload, data) {
            var data = JSON.parse(data);
            WH.SpecialTechVerify.log("testedDone with payload:", payload);
            WH.SpecialTechVerify.log("testedDone with data:", data);
            if (payload.vote == 1) {
                WH.SpecialTechVerify.log("testedDone will show yes feedback section");
                $('#testing-section').hide();
                $('#instructions-section').hide();
                $('#time-verification-section').hide();
                $('#yesfeedback-section').show();
                document.body.scrollTop = $('#yesfeedback-section').offset().top;
                $('#yesfeedbackselect').select2();
            } else if (payload.vote == -1) {
                WH.SpecialTechVerify.log("testedDone will show no feedback section");
                $('#testing-section').hide();
                $('#instructions-section').hide();
                $('#time-verification-section').hide();
                $('#nofeedback-section').show();
                document.body.scrollTop = $('#nofeedback-section').offset().top;
                $('#nofeedbackselect').select2();
            }

            if (data.logactions) {
                WH.SpecialTechVerify.log("log actions", data.logactions);
                for (var i = 0; i < data.logactions.length; i++ ) {
                    this.maLog(data.logactions[i]);
                }
            }
            WH.SpecialTechVerify.updateStats();
        },

        feedbackDone: function(payload, data) {
            var data = JSON.parse(data);
            WH.SpecialTechVerify.log("feedbackDone", data);
            $('#yesfeedback-section').hide();
            $('#nofeedback-section').hide();
            $('#testing-section').hide();
            $('#willtest-buttons').show();
            $('#instructions-section').show();

            document.body.scrollTop = document.documentElement.scrollTop = 0;
            if (data.logactions) {
                WH.SpecialTechVerify.log("log actions", data.logactions);
                for (var i = 0; i < data.logactions.length; i++ ) {
                    this.maLog(data.logactions[i]);
                }
            }
            WH.SpecialTechVerify.updateStats();
            var platform = WH.SpecialTechVerify.getPlatform();

            this.getNext(platform);
        },

        hidePlatformMessageTop: function(platform) {
            $('#platformmessagetop').hide();
            $('#testing-section').hide();
            $('#willtest-buttons').hide();
            $('#instructions-section').hide();
        },
        showPlatformMessageTop: function(platform) {
            $('#platformmessagetop span').text(platform);
            $('#platformmessagetop').show();

        },
        // fix the html so that the mobile and desktop pages
        // are similar enough so the rest of the javascript doesn't have to distinguish
        // between them
        fixBodyHtml: function() {
            if (!$('#bodycontents').length) {
                $('#content').prepend('<div id="bodycontents"></div>');
            }
            $('#bodycontents').append('<div id="tool-data"></div>');
            $('#bodycontents').after('<div id="article-data"></div>');
            $('#article').append($('#bottom-tool'));
            $('#article').append($('#chooseplatformbottom'));
        },

        changePlatform: function() {
            WH.SpecialTechVerify.hidePlatformMessageTop();
            WH.SpecialTechVerify.showPlatformSelect();
            document.body.scrollTop = document.documentElement.scrollTop = 0;
            return false;
        },

        initEventHandlers: function() {
            $('a.changeplatform, .changeplatform a').on("click", this.changePlatform);
            $('#platformselect .button').on("click",function() {
                var val = $('#platformselect select').val();
                var platform = WH.SpecialTechVerify.getPlatform();
                if (!val) {
                    return false;
                }
                $('#platformselect').hide();
                $('#header-title').show();
                $('#tool-data').show();
                WH.SpecialTechVerify.showPlatformMessageTop(val);
                if (val != platform) {
                    WH.SpecialTechVerify.setPlatform(val);
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
                    var platform = $(this).data('platform');
                    var batch = $(this).data('batch');
                    var payload = {
                        pageid: $(this).data('page-id'),
                        revid: $(this).data('rev-id'),
                        vote: vote,
                        batch: batch,
                        platform: platform
                    };
                    WH.SpecialTechVerify.log('willtest buttons: skip', payload);

                    // TODO switch these when you want to save to db
                    WH.SpecialTechVerify.save(payload, 'feedbackDone');
                    //WH.SpecialTechVerify.feedbackDone(payload);
                } else {
                    WH.SpecialTechVerify.log('willtest buttons clicked yes. will show testing buttons');
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
                        WH.SpecialTechVerify.log("not enough time passsed will show verify section");
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
                var platform = $(this).data('platform');
                var batch = $(this).data('batch');
                var payload = {
                    pageid: $(this).data('page-id'),
                    revid: $(this).data('rev-id'),
                    vote: vote,
                    batch: batch,
                    platform:platform
                };
                WH.SpecialTechVerify.log(this.id + ' clicked with payload:', payload);

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
                    WH.SpecialTechVerify.log("feedback not given");
                    WH.SpecialTechVerify.feedbackDone(payload, '{}');
                    return false;
                }

				var platform = $(this).data('platform');
				var batch = $(this).data('batch');
				var model = $('#yesfeedbackmodeltext').val();
				WH.SpecialTechVerify.setDeviceModel(platform, model);
                var version = $('#yesfeedbackversiontext').val();
				WH.SpecialTechVerify.setDeviceVersion(platform, version);
                payload = {
                    action: 'feedback',
                    pageid: $(this).data('page-id'),
                    revid: $(this).data('rev-id'),
                    batch: batch,
                    platform: platform,
                    model: model,
                    version: version
                };
                if (!model && !version && !reason) {
                    return false;
                }
                WH.SpecialTechVerify.log('yes feedback submitted', payload);
				WH.SpecialTechVerify.save(payload, 'feedbackDone');

				return false;
			});

			$(document).on('click', '#nofeedback-section', function(event) {
                var payload = {};
                if (!$(event.target).hasClass('button')) {
                    return;
                }
                if ($(event.target).hasClass("skip")) {
                    WH.SpecialTechVerify.log("feedback not given");
                    WH.SpecialTechVerify.feedbackDone(payload, '{}');
                    return false;
                }

                var textbox = $('#nofeedback-mainfeedback').val();
				var platform = $(this).data('platform');
				var batch = $(this).data('batch');
                var model = $('#nofeedbackmodeltext').val();
				WH.SpecialTechVerify.setDeviceModel(platform, model);
                var version = $('#nofeedbackversiontext').val();
                var reason = $('#nofeedbackselect').select2().get(0).value;
				WH.SpecialTechVerify.setDeviceVersion(platform, version);
                if (!textbox && !model && !version && !reason) {
                    return false;
                }
                payload = {
                    action: 'feedback',
                    pageid: $(this).data('page-id'),
                    revid: $(this).data('rev-id'),
                    platform: platform,
                    batch: batch,
                    textbox: textbox,
                    model: model,
                    version: version,
                    reason: reason
                };
                WH.SpecialTechVerify.log('no feedback submitted', payload);

				WH.SpecialTechVerify.save(payload, 'feedbackDone');

				return false;
			});
        },

        getPlatform: function() {
            return $.cookie(this.platformCookie);
        },
        setPlatform: function(val) {
            return $.cookie(this.platformCookie, val);
        },

        getDeviceVersion: function(platform) {
            return $.cookie(this.deviceVersionCookie+'_'+platform);
        },
        setDeviceVersion: function(platform, val) {
            return $.cookie(this.deviceVersionCookie+'_'+platform, val);
        },

        getDeviceModel: function(platform) {
            return $.cookie(this.deviceModelCookie+'_'+platform);
        },
        setDeviceModel: function(platform, val) {
            return $.cookie(this.deviceModelCookie+'_'+platform, val);
        },

        showPlatformSelect: function() {
            $('#header-title').hide();
            $('#tool-data').hide();
            $('#desktop-title').show();
            var platformId = this.getPlatform();
            if (platformId) {
                $("#platformselect input[name='platform'][value='" + platformId + "']").prop('checked', true);
            }

            $('#platformselect').slideDown();
        },

		init: function() {
			WH.xss.addToken();

            this.startTime = this.getNow();
            this.fixBodyHtml();
            this.initEventHandlers();

            var platformId = this.getPlatform();
			console.log("here", $('#platformselect'));
			// check if platform is in list of platforms
			var found = false;
			$('#platformselect option').each(function() {
				if (platformId == $(this).val()) {
					found = true;
				}
			});
			if (found == false) {
				var platformId = '';
				this.setPlatform('');
			}
            if (!platformId) {
                this.showPlatformSelect();
            } else {
                this.showPlatformMessageTop(platformId);
                // get the next item if we know the device
                this.getNext(platformId);
            }
		},

        maLog: function(action) {
			var event = 'test_tech_articles';
            var data = { 'action' : action };
            data['article'] = $('#current-title').data('title');
            WH.SpecialTechVerify.log('will ma log data', data);
            WH.maEvent(event, data, true);
        },

		log: function() {
			if (wgUserId == 2029395 ) {
				console.log.apply(null, arguments);
			}
		},

		getNext: function(platformId) {
			var url = this.tool+'?action=next&platform='+platformId;
            // check for uselang qqx
            if (window.location.search.includes("uselang=qqx")) {
                url += "&uselang=qqx";
            }

            $('#testing-section').remove();
            $('#time-verification-section').remove();
            $('#yesfeedback-section').remove();
            $('#nofeedback-section').remove();
            $('#article-data').fadeOut();
			$('#tool-data').fadeOut(function() {
				$('.spinner').fadeIn(function() {
					$.getJSON(url, function(data) {
                        WH.SpecialTechVerify.loadNextArticle(data);
					});
				});
			});
		},

        loadNextArticle: function(data) {
            if (data.remaining == 0) {
                $('#header-count').html(data.remaining);
                $('#tool-data').html(data.html);
                $('#desktop-title').hide();
                $('#header-title').html('');
            } else {
                $('#desktop-title').show();
                $('#tool-data').html(data.html);
                $('#header-title').html(data.title);
                $('#header-count').html(data.remaining);
                if (data.articlehtml) {
                    $('#article-data').html(data.articlehtml);
                    $('#article-data').data('loaded', true);
                } else {
                    if (WH.SpecialTechVerify.lastPageId != data.pageId) {
                        $('#article-data').data('loaded', false);
                        $('#article-data').html('');
                        $('.full-link').show();
                        $('.hide-full-link').hide();
                    } else {
                        if (WH.SpecialTechVerify.articleVisible) {
                            $('.full-link').hide();
                            $('.hide-full-link').show();
                        }
                    }
                }
            }
            $('.spinner').fadeOut(function() {
                $('#tool-data').fadeIn();
                if (data.remaining > 0) {
                    $('#article-data').fadeIn();
                }

                $('#chooseplatformbottom').show();
                $('#testing-section').show();
            });
            WH.ToolInfo.init();
            this.lastPageId = data.pageId;
            $('#testing-section').prependTo('#bottom-tool');
            $('#time-verification-section').prependTo('#bottom-tool');
            $('#yesfeedback-section').prependTo('#bottom-tool');
            $('#nofeedback-section').prependTo('#bottom-tool');

            $('a.changeplatform, .changeplatform a').off("click", this.changePlatform);
            $('a.changeplatform, .changeplatform a').on("click", this.changePlatform);

			// TODO only do this if the platform matches??

			var platform = $('#testing-section').data('platform');
			var model = this.getDeviceModel(platform);
			if (model) {
				$('#yesfeedbackmodeltext').val(model);
				$('#nofeedbackmodeltext').val(model);
			}
			var version = this.getDeviceVersion(platform);
			if (version) {
				$('#yesfeedbackversiontext').val(version);
				$('#nofeedbackversiontext').val(version);
			}
        },

        updateStats : function() {
            // do not update if last vote was a skip
            if (WH.SpecialTechVerify.lastVote == 0) {
                return;
            }
            var statboxes = '#iia_stats_today_techarticletested,#iia_stats_week_techarticletested,#iia_stats_all_techarticletested,#iia_stats_group';
            $(statboxes).each(function(index, elem) {
                $(this).fadeOut(function () {
                    var cur = parseInt($(this).html());
                    $(this).html(cur + 1);
                    $(this).fadeIn();
                });
            });
        },

        save: function (payload, callback) {
            $.post(this.tool, payload).done($.proxy(this, callback, payload));
        },
	};
	$(document).ready(function() {
		WH.SpecialTechVerify.init();
	});
})();
