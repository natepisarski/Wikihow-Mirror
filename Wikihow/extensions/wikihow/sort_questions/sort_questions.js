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
				WH.SortQuestions.target.fadeOut();
				$('.spinner').fadeIn();
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
			$.getJSON(url, function(data) {
				WH.SortQuestions.target.html(data.html);

				$('.spinner').fadeOut(function() {
					WH.SortQuestions.target.fadeIn();
				});
			});
		},

		markChoice: function (obj) {
			obj.siblings('.choice').removeClass('chosen');
			obj.toggleClass('chosen');
			return false;
		},

		update: function (numEdits) {
			numEdits = numEdits || 1;
			var $counters = $("td[id^='iia_stats_']").not("td[id^='iia_stats_standing']");

			$counters.each(function (index, elem) {
				var duration = index * 400,
				$stat = $(elem),
				statValuesString = $stat.text().replace(/,/g,""), // Getting all stat values as String and removing all commas (since parseInt doesn't handle commas)
                newVal = parseInt(statValuesString, 10) + numEdits; // Parsing stat values to int and adding number of new votes
                newVal = newVal.toLocaleString(); // toLocaleString adds thousand-separator commas to improve readability

				$stat.fadeOut(duration, function () {
					$stat.html(newVal).fadeIn();
				});
			});
		},

		saveVotes: function () {
			var payload = {questions: []};
			var voteCount = 0;
			this.target.find('.question').each(function () {
				var $choice = $(this).find('.chosen'),
					question = $(this).data();

				if ($choice.length > 0) {
					question.dir = $choice.hasClass('yes') ? 'up' : 'down';
					voteCount++;
					payload.questions.push(question);
				}
			});

			if (payload.questions.length > 0) {

				WH.SortQuestions.update(voteCount);

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
