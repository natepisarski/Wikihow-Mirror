(function() {
	window.WH = window.WH || {};
	window.WH.SearchAd = {
		
		init: function() {
			this.addHandlers();
		},
		
		addHandlers: function() {
			$('#searchad').click(function() {
				//log it
				var version = $('#searchad_version').val();
				WH.maEvent('click_wikihow_to_'+version, {category: 'wikihow_to' }, false);
				
				var title = mw.config.get('wgTitle').replace(/\s/g,'+');
				window.location.href = 'wikiHowTo?search=wikiHow+to+'+title;
			});
		}
		
	};
	
	$(document).ready(function() {
		//IE7 & IE8 can't do the svgs, so let's just not show them the ad
		if ($.browser.msie && $.browser.version <= 8) {
			$('#searchad').hide();
			return;
		}
		
		WH.SearchAd.init();
	});
})(jQuery);