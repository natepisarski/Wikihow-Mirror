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
					WH.whEvent(WH.MyWikihow.cat, 'click_next', '', WH.MyWikihow.first, WH.MyWikihow.version);
					WH.MyWikihow.fillBoxes();
				});
				
				$(document).on('click','.mwh_catcall', function() {
					$(this).toggleClass('mwh_selected');
					WH.whEvent(WH.MyWikihow.cat, 'click_tag', '', WH.MyWikihow.first, WH.MyWikihow.version);
					var topcat = $(this).text().replace(/ /g,'-');
					$.getJSON('/Special:MyWikihow?getsubcats='+topcat, function(data) {
						WH.MyWikihow.formatSubCats(data);
					});
				});
			}
			else {
				//showing articles
				$('.related_box').click(function() {
					WH.whEvent(WH.MyWikihow.cat, 'click_action', '', WH.MyWikihow.second, WH.MyWikihow.version);
				});
				
				$('#mwh_question input').click(function() {
					action = $(this).hasClass('mwh_yes') ? 'click_yes' : 'click_no';
					WH.whEvent(WH.MyWikihow.cat, action, '', WH.MyWikihow.second, WH.MyWikihow.version);
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
					WH.whEvent(WH.MyWikihow.cat, 'load', '', WH.MyWikihow.second, WH.MyWikihow.version);
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
			WH.whEvent(WH.MyWikihow.cat, 'load', '', WH.MyWikihow.first, WH.MyWikihow.version);
			$('#my_wikihow').show();
			WH.MyWikihow.addHandlers(WH.MyWikihow.first);
		}
		else {
			WH.MyWikihow.fillBoxes();
		}
	});
})(jQuery);