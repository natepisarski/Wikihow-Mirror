(function($) {
	var tipsDisplayed = null;
	var numTips = null;

	$(document).ready(function() {
		var tips = getTips();
		numTips = tips.size();
		if (numTips > 10) {
			tipsDisplayed = numTips < 10 ? numTips : 10;

			var ul = $(tips).parent();
			$("<div id='show_tips_container'><div id='tip_expander' class='drop-heading-expander tip_expander'/><a id='tip_more' href='#'>Show More Tips</a></div>").insertAfter(ul);
			reorderTips();
			displayTips();	

			$(document).on('click', '#show_tips_container', function(e) {
				e.preventDefault();
				tipsDisplayed = tipsDisplayed ==  10 ? numTips : numTips < 10 ? numTips : 10;
				displayTips();
			});
		}
	});

	// Reorder tips: 
	// - Make sure tips with references are in top 10
	// - Rotate in tips with no votes
	function reorderTips() {
		var tips = getTips();
		if ($(tips).size() > 10) {
			var refTips = [];
			var noVotes = [];
			var numNoVotes = 0;
			var noVotesMax = 2;
			tips.each(function(i, li) {
				if ($(li).find('sup.reference').size() > 0) {
					refTips.push(li);
					return;
				}

				var numVotes = 0;
				if (numNoVotes <= noVotesMax) {
					$(li).find('.tr_vote').each(function(j, span) {
						numVotes +=  parseInt($(span).html());
					});
					if (numVotes == 0) {
						numNoVotes++;	
						noVotes.push(li);
						return;
					}
				}
			});

			while (refTips.length > 0) {
				var li = refTips.shift();
				$(li).prependTo($(li).parent());
			}

			while (noVotes.length > 0) {
				var li = noVotes.shift();
				var ul = $(li).parent();
				// insert at last position of top 10
				$(li).insertAfter($(ul).children('li').get(8));
			}
		}
	}

	function getTips() {
		var tips = $('#tips ul');
		if (tips.size() > 1) {
			tips = $($(tips).get(0)).children('li');
		} else {
			tips = $(tips).children('li');
		}
		return tips;
	}

	function displayTips() {
		var tips = getTips();
		tips.each(function(i, li) {
			if (i < tipsDisplayed) {
				$(li).fadeIn();
			} else {
				$(li).hide();	
			}
		});
		updateTipExpander(numTips - tipsDisplayed);
	}
	
	function updateTipExpander(tipsRemaining) {
		s = tipsRemaining == 1 ? '' : 's';
		if (tipsRemaining > 0) {
			$('#tip_more').html('Show ' + tipsRemaining + ' more tip' + s);
			$('#tip_expander').removeClass('d-h-show');
		} else {
			$('#tip_more').html('Show fewer tips');
			$('#tip_expander').addClass('d-h-show');
		}
	}
})(jQuery);
