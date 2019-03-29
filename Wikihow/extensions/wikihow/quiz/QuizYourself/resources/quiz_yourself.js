(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.QuizYourself = {
		tool_url: '/Special:QuizYourself',
		cookie_score: mw.config.get('wgCookiePrefix') + '_qy_score',
		cookie_skips: mw.config.get('wgCookiePrefix') + '_qy_skips',
		cookie_expiration_days: 30,
		point_value: 10,
		category: '',
		questions_count: 0,

		init: function() {
			this.setCategory();

			if (this.category)
				this.loadQuiz();
			else
				this.loadCategories();
		},

		getScore: function() {
			return typeof $.cookie(this.cookie_score) !== 'undefined' ? parseInt($.cookie(this.cookie_score)) : 0;
		},

		setScore: function() {
			$('#quiz_yourself_score div').html(this.getScore());
		},

		upTheScore: function() {
			var new_score = this.getScore() + this.point_value;
			$.cookie(this.cookie_score, new_score, { expires : this.cookie_expiration_days });
			this.setScore();
		},

		addTheSkip: function(article_id) {
			var article_id = article_id.toString();
			var skips = typeof $.cookie(this.cookie_skips) !== 'undefined' ? $.cookie(this.cookie_skips).split(',') : [];

			if (skips.indexOf(article_id) == -1) skips.push(article_id);

			$.cookie(this.cookie_skips, skips.join(), { expires : this.cookie_expiration_days });
		},

		setCategory: function() {
			var category = mw.util.getParamValue('category');
			if (category) {
				this.category = this.escapeHtml(category);
				history.pushState(null, null, window.location.href.split("?")[0]);
			}
		},

		escapeHtml: function (htmlString) {
			var div = document.createElement("div");
			div.innerHTML = htmlString;
			return div.textContent || div.innerText || "";
		},

		addCategoryHandlers: function() {
			$('.topcat').click(function() {
				WH.QuizYourself.category = $(this).data('hyphenated');
				WH.maEvent('quiz_yourself_categories_page_taps', { 'category': WH.QuizYourself.category });
				WH.QuizYourself.loadQuiz();
			});

			//end of queue for whole app
			$('#quiz_yourself_exit').click(function() {
				window.location.href = '/';
			});
		},

		addQuizHandlers: function() {
			$('#quiz_categories').click($.proxy(function() {
				WH.maEvent('quiz_yourself_backto_category_taps');
				this.goToCategorySelect();
			},this));

			$('#quiz_article').click(function() {
				WH.maEvent('quiz_yourself_article_tap', { 'article_id': $(this).data('id') });
			});

			$('.quiz_next').click(function() {
				WH.QuizYourself.next(this);
			});

			$('.quiz_option').click(function() {
				WH.QuizYourself.answerTap($(this));
			});

			//end of queue
			$('#quiz_yourself_more').click($.proxy(function() {
				this.goToCategorySelect();
			},this));

			$('#quiz_yourself_exit').click(function() {
				window.location.href = '/';
			});
		},

		loadQuiz: function() {
			if (this.category == '') return;

			$.post(
				this.tool_url,
				{
					'action': 'get_quiz',
					'category': this.category
				},
				$.proxy(function(data) {
					if (!$.isEmptyObject(data)) {
						if (data.question_count) this.questions_count = data.question_count;
						this.displayData('quizzes', data);
					}
					else {
						this.goToCategorySelect();
					}
				},this),
				'json'
			);
		},

		goToCategorySelect: function() {
			this.category = '';
			this.loadCategories();
		},

		next: function(obj) {
			var article_id = $('#quiz_article').data('id');
			var event_suffix = $(obj).parent().attr('id') == 'quiz_yourself_quiz_after' ? 'bottom' : 'top';
			WH.maEvent('quiz_yourself_next_'+event_suffix, { 'article_id': article_id });

			this.addTheSkip(article_id);
			this.loadQuiz();
		},

		loadCategories: function() {
			$.post(
				this.tool_url,
				{ 'action': 'get_cat_page' },
				$.proxy(function(data) {
					this.displayData('categories', data);
				},this),
				'json'
			);
		},

		displayData: function(view_type, data) {
			//always reset to the top
			$(window).scrollTop(0);

			$('#quiz_yourself_app').removeClass().addClass(view_type).html(data.html);

			//add the appropriate handlers
			if (view_type == 'categories') {
				this.addCategoryHandlers();
			}
			else if (view_type == 'quizzes') {
				this.setScore();
				this.addQuizHandlers();
			}
		},

		loadAd: function(adId) {
			var client = "ca-pub-9543332082073187";
			var i = window.document.createElement('ins');
			i.setAttribute('data-ad-client', client);
			i.setAttribute('data-ad-slot', '9589201673');
			i.setAttribute('class', 'adsbygoogle');
			//var css = "display:block;width:320px;height:50px;margin:-5px auto 10px auto;";;
			if(window.isBig) {
				i.style.cssText = "display:block;width:500px;height:50px;margin:-5px auto 10px auto;";
			} else {
				i.style.cssText = "display:block;width:320px;height:50px;margin:-5px auto 10px auto;";
			}
			document.getElementById(adId).appendChild(i);
			(adsbygoogle = window.adsbygoogle || []).push({});
		},

		answerTap: function(obj) {
			var result_class = $(obj).hasClass('correct') ? 'quiz_success' : 'quiz_error';
			var question = $(obj).parent();

			//reset any other answers to this question
			$(question).find('.quiz_option')
				.removeClass('quiz_success')
				.removeClass('quiz_error');

			$(question).find('.quiz_explanation').hide();

			//return result for this answer
			$(obj)
				.addClass(result_class)
				.next('.quiz_explanation').addClass(result_class).slideDown(100);

			//score it (if first answer and correct)
			if ($(obj).hasClass('correct') && !$(question).hasClass('answered')) this.upTheScore();

			//mark as answered
			$(question).addClass('answered');

			WH.maEvent('quiz_yourself_answer_tap', {
				'question': $(obj).data('quiz'),
				'answer': $(obj).data('option') + 1,
				'correct': $(obj).hasClass('correct') ? 'yes' : 'no'
			});
		}

	}

	$(document).ready(function() {
		WH.QuizYourself.init();
	});
})(jQuery, mw);