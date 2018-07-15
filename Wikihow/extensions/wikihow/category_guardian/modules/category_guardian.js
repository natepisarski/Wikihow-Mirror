(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.CategoryGuardian = {

		URL: "/Special:CategoryGuardian",
		numChecked: 0,
		retries: 0,
		MAX_RETRIES: 5,
		MAX_NUM_ANON_CHECKED: 50,
		template: $('#quiz-template').html(),
		$target: $('#render-target'),
		$quiz: $('.quiz'),
		currentArticle: {},
		curData: {},
		voted: false,
		numPagesLoaded: 0,

		initialize: function () {
			this.pusher = new WH.Utils.PushLoad(this.$target);
			// this.throttler = new WH.AnonThrottle({
			// 	toolName: 'category_checker_anon_edits',
			// 	maxEdits: this.MAX_NUM_ANON_CHECKED
			// });

			WH.xss.addToken();
			this.getArticles(true);

			$('#next-batch').click(function (event) {
				event.preventDefault();
				// Commented out for Badges test
				//event.stopPropagation();
				WH.CategoryGuardian.saveVotes();
			});
		},

		getArticles: function () {
			// if (this.throttler.limitReached()) {
				// this.showLimitReached();
				// return;
			// }

			this.$quiz.addClass('loading');
			$.getJSON(this.URL, {nextBatch: true}, $.proxy(this, 'articlesLoaded')).fail(function () {
				WH.CategoryGuardian.articlesLoaded({});
			});
			this.numPagesLoaded++;
		},

		articlesLoaded: function (data) {
			if (!data || !data.articles || data.articles.length === 0) {
				this.retries += 1;

				if (this.retries < this.MAX_RETRIES) {
					this.getArticles();
					return;
				}

				this.showNoMore();
				return;
			}

			this.curData = data;

			$('body').data({
				event_type: 'category_guardian'
			});

			this.$quiz.removeClass('loading');
			this.retries = 0;
			this.pusher.addSlide(Mustache.render(this.template, data));

			this.pusher.$currentSlide.find('.counter').each(function () {
				var $elem = $(this),
					oldVal = parseInt($elem.text(), null),
					newVal = isNaN(oldVal) ? 1 : oldVal + 1;
				$(this).text(newVal);
			});

			this.listen(this.pusher.$currentSlide);

			if (WH.isMobileDomain && !$.cookie('cg_prompted') && !this.voted && this.numPagesLoaded > 2) {
				this.showPrompt();
			}
		},

		listen: function ($scope) {
			$scope.find('.choice').click($.proxy(this, 'markChoice'));
			$scope.find('.answer-text').click($.proxy(this, 'toggleBlurb'));
		},

		showPrompt: function () {
			//slideDown actually means slide it up because it's BIZARRO WIKIHOW!!!
			$('#skip_prompt').slideDown(function() {
				//add click function to hide it again
				$('#skip_prompt_x, #skip_prompt input').click(function(event) {
					event.preventDefault();
					event.stopPropagation();
					$('#skip_prompt').slideUp(); //opposite of what you might expect
				});
			});

			//let's not show it ever again
			$.cookie('cg_prompted','1',{expires: 365 * 100});

			//log
			WH.usageLogs.log({
				event_action: 'show_prompt',
				serialized_data: {
					prompt: 'skip',
					text: $('#firstP').html()+' '+$('#secondP').html()
				}
			});
		},

		toggleBlurb: function (event) {
			event.stopPropagation();
			event.preventDefault();
			var $text = $(event.currentTarget);

			this.$target.find('.answer-text').not($text).removeClass('open');
			$text.toggleClass('open');

			if ($text.hasClass('open')) {
				var data = $text.data();
				data.event_action = 'expand';
				WH.usageLogs.log(data);
			}

			this.pusher.updateHeightDelayed(300);
		},

		saveVotes: function () {
			var payload = {answers: []};
			var realAnswerCount = 0;
			this.$target.find('.answer').each(function () {
				var $choice = $(this).find('.chosen'),
					answer = $(this).data();

				if ($choice.length > 0) {
					answer.dir = $choice.hasClass('yes') ? 'up' :'down';
					realAnswerCount++;
					payload.answers.push(answer);
				} else if (answer.id == -1) {
					//id's of -1 indicate that they came from planted questions
					//for these, record the skips
					answer.dir = "skip";
					payload.answers.push(answer);
				}
			});

			if (payload.answers.length > 0) {
				this.$quiz.addClass('loading');
				// if (realAnswerCount > 0) {
				// 	//need to check if > 0, b/c recordEdit function forces to 1 if it's zero
				// 	this.throttler.recordEdit(realAnswerCount);
				// }
				WH.statsUpdater.update(realAnswerCount);

				$.post(this.URL, payload)
					.done($.proxy(this, 'articlesLoaded'))
					.fail($.proxy(this, 'getArticles'));

			} else {
				this.getArticles();
			}
		},

		showLimitReached: function () {
			this.$quiz.removeClass('loading').addClass('inactive');
			var params = "";
			if (WH && WH.isMobileDomain) {
				var params = "&useformat=mobile&returntoquery=useformat%3Dmobile";
			}
			this.pusher.addSlide(
				Mustache.render(unescape($('#limit-reached-template').html()), {params:params})
			);
		},

		showNoMore: function () {
			this.$quiz.removeClass('loading').addClass('inactive');
			$.get('/Special:EndOfQueue?this_tool=catch', function(data) {
				if (WH.isMobileDomain) {
					$('#content .quiz').hide().delay(200).html(data).fadeIn();
				}
				else {
					$('#render-target').hide().delay(200).html(data).fadeIn();
				}
			});
		},

		markChoice: function (event) {
			event.stopPropagation();
			event.preventDefault();

			this.voted = true;

			var $choice = $(event.currentTarget),
				$text = $choice.parent().parent().find('.answer-text');

			$choice.siblings('.choice').removeClass('chosen');
			$choice.toggleClass('chosen');
			$text.removeClass('open');

			return false;
		}
	};

	WH.CategoryGuardian.initialize();
}());



