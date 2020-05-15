(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.SpecialTechFeedback = {
		lastVote : 0,
		lastPageId : null,
		articleVisible: false,
		tool: '/Special:TechFeedback',
		smallScreen: $(window).width() < WH.mediumScreenMinWidth,

		init: function() {
			WH.xss.addToken();

			var topElement = $('#stf-title').parent();
			$(topElement).append('<div id="tool-data"></div>');
			$(topElement).after('<div id="article-data"></div>');

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
				WH.SpecialTechFeedback.articleVisible = true;
				return false;
			});

			$(document).on('click', '.hide-full-link', function(event) {
				$('#article-data').hide();
				$('.full-link').show();
				$('.hide-full-link').hide();
				WH.SpecialTechFeedback.articleVisible = false;
				return false;
			});

			$(document).on('click', '#buttons-line', function(event) {
				$('#buttons-line').hide();
				var vote = 0;
				if ($(event.target).hasClass("yes")) {
					vote = 1;
				} else if ($(event.target).hasClass("no")) {
					vote = -1;
				}
				WH.SpecialTechFeedback.lastVote = vote;
				var payload = {rrid : $(this).data('rating-reason-id'), pageid: $(this).data('page-id'), vote: vote};
				WH.SpecialTechFeedback.save(payload);
				return false;
			});
		},

		voteDone: function(data) {
			WH.SpecialTechFeedback.updateStats();
			this.getNext();
		},

		getNext: function() {
			var url = this.tool+'?getQs=1';
			var smallScreen = this.smallScreen;

			$('#article-data').fadeOut();
			$('#tool-data').fadeOut(function() {
				$('.spinner').fadeIn(function() {
					$.getJSON(url, function(data) {
						$('#buttons-line').show();
						if (data.remaining == 0) {
							$('#header-count').html(data.remaining);
							$('#tool-data').html(data.html);
						} else {
							$('#tool-data').html(data.html);
							$('#header-title').html(data.title);
							$('#header-count').html(data.remaining);
							if (data.articlehtml && !smallScreen) {
								$('#article-data').html(data.articlehtml);
								$('#article-data').data('loaded', true);
							} else {
								console.log('comparing last page id', WH.SpecialTechFeedback.lastPageId, 'to data page id', data.pageId);
								if (WH.SpecialTechFeedback.lastPageId != data.pageId) {
									console.log('prev article is new .. resetting');
									$('#article-data').data('loaded', false);
									$('#article-data').html('');
									$('.full-link').show();
									$('.hide-full-link').hide();
								} else {
									console.log('prev article has not changed');

									if (WH.SpecialTechFeedback.articleVisible) {
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
						WH.SpecialTechFeedback.lastPageId = data.pageId;
					});
				});
			});
		},

		updateStats : function() {
			// do not update if last vote was a skip
			if (WH.SpecialTechFeedback.lastVote == 0) {
				return;
			}
			var statboxes = '#iia_stats_today_techfeedbackreviewed,#iia_stats_week_techfeedbackreviewed,#iia_stats_all_techfeedbackreviewed,#iia_stats_group';
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
		mw.loader.using( 'ext.wikihow.common_top', function() {
			WH.SpecialTechFeedback.init();
		} );
	});
})();
