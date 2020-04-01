(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.SpecialArticleFeedback = {
        lastVote : 0,
        lastPageId : null,
        articleVisible: false,
		tool: '/Special:ArticleFeedback',
		init: function() {
			WH.xss.addToken();
            if (!$('#bodycontents').length) {
                // on mobile
                $('#content').prepend('<div id="bodycontents"></div>');
            }
            $('#bodycontents').append('<div id="tool-data"></div>');
            $('#bodycontents').after('<div id="article-data"></div>');

			this.getNext();

			$(document).on('click', '.full-link', function(event) {
                if (!$('#article-data').data('loaded')) {
                    // load the data
                    var pageId = $(this).data('page-id');
                    jQuery('<iframe/>', {
                        class: 'amparticle',
                        src : "/index.php?curid="+pageId+"&amp=1&wh_an=1",
                                width : '100%',
                                style : "overflow:hidden; display:block; width:100%; height:2000px;",
                                frameborder : '0',
                                onload : "this.height=this.contentWindow.document.body.scrollHeight;$('.show-article-line').show()",
                    }).appendTo('#article-data');
                    $('#article-data').data('loaded', true);
                    //$('.show-article-line').hide();
                }

                $('#article-data').show();
                $('.full-link').hide();
                $('.hide-full-link').show();
                WH.SpecialArticleFeedback.articleVisible = true;
                return false;
            });

			$(document).on('click', '.hide-full-link', function(event) {
                $('#article-data').hide();
                $('.full-link').show();
                $('.hide-full-link').hide();
                WH.SpecialArticleFeedback.articleVisible = false;
                return false;
            });

			$(document).on('click', '#buttons-line', function(event) {
                var vote = 0;
                if ($(event.target).hasClass("yes")) {
                    vote = 1;
                } else if ($(event.target).hasClass("no")) {
                    vote = -1;
                }
                WH.SpecialArticleFeedback.lastVote = vote;
                var payload = {rrid : $(this).data('rating-reason-id'), pageid: $(this).data('page-id'), vote: vote};
				WH.SpecialArticleFeedback.save(payload);
				return false;
			});
		},

        maLog: function(action) {
			var event = 'article_feedback_tool';
            var data = { 'action' : action };
            data['article'] = $('#current-title').data('title');
            WH.maEvent(event, data, true);
        },

        voteDone: function(data) {
            var data = JSON.parse(data);
            if (data.logactions) {
                for (var i = 0; i < data.logactions.length; i++ ) {
                    this.maLog(data.logactions[i]);
                }
            }
            WH.SpecialArticleFeedback.updateStats();
            this.getNext();
        },

		getNext: function() {
			var url = this.tool+'?action=next';

            $('#article-data').fadeOut();
			$('#tool-data').fadeOut(function() {
				$('.spinner').fadeIn(function() {
					$.getJSON(url, function(data) {
                        if (data.remaining == 0) {
                            $('#header-count').html(data.remaining);
                            $('#tool-data').html(data.html);
                        } else {
                            $('#tool-data').html(data.html);
                            $('#header-title').html(data.title);
                            $('#header-count').html(data.remaining);
                            if (data.articlehtml) {
                                $('#article-data').html(data.articlehtml);
                                $('#article-data').data('loaded', true);
                            } else {
                                console.log('comparing last page id', WH.SpecialArticleFeedback.lastPageId, 'to data page id', data.pageId);
                                if (WH.SpecialArticleFeedback.lastPageId != data.pageId) {
                                    console.log('prev article is new .. resetting');
                                    $('#article-data').data('loaded', false);
                                    $('#article-data').html('');
                                    $('.full-link').show();
                                    $('.hide-full-link').hide();
                                } else {
                                    console.log('prev article has not changed');

                                    if (WH.SpecialArticleFeedback.articleVisible) {
                                        $('.full-link').hide();
                                        $('.hide-full-link').show();
                                    }
                                }
                            }
                        }

						$('.spinner').fadeOut(function() {
							$('#tool-data').fadeIn();
							$('#article-data').fadeIn();
						});

                        WH.ToolInfo.init();
                        WH.SpecialArticleFeedback.lastPageId = data.pageId;
					});
				});
			});
		},

        updateStats : function() {
            // do not update if last vote was a skip
            if (WH.SpecialArticleFeedback.lastVote == 0) {
                return;
            }
            var statboxes = '#iia_stats_today_articlefeedbackreviewed,#iia_stats_week_articlefeedbackreviewed,#iia_stats_all_articlefeedbackreviewed,#iia_stats_group';
            $(statboxes).each(function(index, elem) {
                    $(this).fadeOut(function () {
                        var cur = parseInt($(this).html());
                        $(this).html(cur + 1);
                        $(this).fadeIn();
                    });
                }
            );
        },

        save : function (payload) {
            $.post(this.tool, payload).done($.proxy(this, 'voteDone'));
        },
	};
	$(document).ready(function() {
		WH.SpecialArticleFeedback.init();
	});
})();
