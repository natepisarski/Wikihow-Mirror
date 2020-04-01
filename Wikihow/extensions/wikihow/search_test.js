(function() {
	window.WH = window.WH || {};
	window.WH.SearchTest = {
		
		new_search: false,
		
		init: function() {
			//check if we're showing the new search UI
			this.new_search = $('#bubble_search').hasClass('search_line');
			
			WH.SearchTest.track();
		},
		
		track: function() {
			var load_event = this.new_search ? 'search_new_load_2' : 'search_original_load_2';
			WH.maEvent(load_event, { category: 'search_bar_test' }, false);
			
			$('#search_site_bubble').click(function() {
				var click_event = WH.SearchTest.new_search ? 'search_new_click_2' : 'search_original_click_2';
				WH.maEvent(click_event, { category: 'search_bar_test' }, false);
			});
		}
	};
	
	$(document).ready(function() {
		WH.SearchTest.init();
	});
	
})(jQuery);