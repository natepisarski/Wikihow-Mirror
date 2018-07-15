(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.SortQuestions = {
		
		target: $('#render-target'),
		tool: '/Special:SortQuestions',
		
		init: function() {
			WH.xss.addToken();
			this.getQuestions();
			
			$('#next-batch').click( $.proxy(function() {
				WH.maEvent('next', { category: 'sort_questions_tool' }, false);
				this.saveVotes();
				return false;
			}, this));
			
			$(document).on('click', '.choice', function() {
				WH.SortQuestions.markChoice($(this));
				return false;
			});
		},
		
		getQuestions: function() {
			var aid = $('#sqt_article_id').val() ? $('#sqt_article_id').val() : '';
			var url = this.tool+'?getQs=1&last_article='+ aid;
			
			//loading...
			this.target.fadeOut(function() {
				$('.spinner').fadeIn(function() {
							
					$.getJSON(url, function(data) {
						WH.SortQuestions.target.html(data.html);
						
						$('.spinner').fadeOut(function() {
							WH.SortQuestions.target.fadeIn();
						});
					});
					
				});
			});
		},

		markChoice: function (obj) {
			obj.siblings('.choice').removeClass('chosen');
			obj.toggleClass('chosen');
			return false;
		},
		
		saveVotes: function () {
			var payload = {questions: []};
			
			this.target.find('.question').each(function () {
				var $choice = $(this).find('.chosen'),
					question = $(this).data();

				if ($choice.length > 0) {
					question.dir = $choice.hasClass('yes') ? 'up' : 'down';
					payload.questions.push(question);					
					
					//log
					var label = 'vote_'+question.dir;
					WH.maEvent(label, { category: 'sort_questions_tool' }, false);
				}
			});
			
			if (payload.questions.length > 0) {
				$.post(this.tool, payload)
					.done($.proxy(this, 'getQuestions'));
			} else {
				this.getQuestions();
			}
		}

	};
	
	$(document).ready(function() {
		WH.SortQuestions.init();
	});
	
})();