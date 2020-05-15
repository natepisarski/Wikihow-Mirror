(function($) {
	window.WH = window.WH || {};
	window.WH.MyWikihow = {

		cat: 'mobile_feed_test',
		version: '1',
		first: 'personalize',
		second: 'feed',

		addHandlers: function(page) {

			if (page == WH.MyWikihow.first) {
				//choosing categories
				$(document).on('click', '.mwh_done', function() {
					WH.MyWikihow.fillBoxes();
				});

				$(document).on('click','.mwh_catcall', function() {
					$(this).toggleClass('mwh_selected');
					var topcat = $(this).text().replace(/ /g,'-');
					$.getJSON('/Special:MyWikihow?getsubcats='+topcat, function(data) {
						WH.MyWikihow.formatSubCats(data);
					});
				});
			}
			else {
				$('#mwh_question input').click(function() {
					$('#mwh_question').html(mw.message('mywikihow_response').text());
				});

			}
		},

		formatSubCats: function(subcats) {
			var catbox = '';

			$.each(subcats, function(key, cat) {
				catbox = '<div class="mwh_catbox"><div class="mwh_catcall">'+cat.replace(/-/g,' ')+'</div></div>';
				$('#mwh_cats').append(catbox);
			});

		},

		getAllCats: function() {
			var cats = Array();
			$('.mwh_selected').each(function() {
				cats.push($(this).text());
			});
			return cats.join(',');
		},

		fillBoxes: function() {
			$.cookie('mwh',1);
			$.post('/Special:MyWikihow',
				{
					cats: WH.MyWikihow.getAllCats(),
				}, function(data) {
					WH.MyWikihow.showResults(data);
					WH.MyWikihow.addHandlers(WH.MyWikihow.second);
				}
			,'json');
		},

		showResults: function(data) {
			$('#mwh_articles').prepend(data);
			$('.mwh_done').fadeOut();
			$('#mwh_cats').slideUp(function() {
				$('#my_wikihow').show();
				$('#mwh_hdr').html(mw.message('mywikihow_hdr2').text());
				$('#mwh_articles').slideDown();
			});
		}
	};

	$(document).ready(function() {
		if (!$.cookie('mwh')) {
			$('#my_wikihow').show();
			WH.MyWikihow.addHandlers(WH.MyWikihow.first);
		}
		else {
			WH.MyWikihow.fillBoxes();
		}
	});
})(jQuery);
