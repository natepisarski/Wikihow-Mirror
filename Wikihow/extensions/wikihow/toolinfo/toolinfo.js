(function($) {
	
	window.WH = window.WH || {};
	window.WH.ToolInfo = {
		
		ti_cookie: 'wiki_shared_ti',
		
		init: function() {
			$('#ti_icon').click(function() {
				if ($('#ti_box').is(':visible')) {
					WH.ToolInfo.closeToolInfo();
				}
				else {
					WH.ToolInfo.openToolInfo(true);
				}
			});
			
			$('#ti_icon').html(mw.message('ti_help').text());
			
			//open if it's the user's first time seeing this
			if (this.isNoob()) this.openToolInfo(false);
		},
		
		openToolInfo: function(byUser) {	
			//show
			$('#ti_outer_box').slideDown(function() {
				//got it link
				$('#ti_link').click(function() {
					WH.ToolInfo.closeToolInfo();
					return false;
				});
			});
			
			//log it
			if (byUser) {
				WH.usageLogs.log({event_action: 'expand_hints'});
			}
		},

		closeToolInfo: function() {
			//set cookie so we don't open by default in the future
			this.setCookie();
			
			$('#ti_outer_box').slideUp();
			
			//log it
			WH.usageLogs.log({event_action: 'close_hints'});	
		},
		
		//is this their first time?
		isNoob: function() {
			if ($.cookie(this.ti_cookie)) {
				var tools = $.cookie(this.ti_cookie).split(',');
				//not n00b if they're in the cookie array
				if ($.inArray(mw.config.get('wgTitle'), tools) != -1) return false;
			}
			return true;
		},
		
		setCookie: function() {
			var tools = [];
			
			if ($.cookie(this.ti_cookie)) {
				tools = $.cookie(this.ti_cookie).split(',');
			
				// it's already in there...nvm
				if ($.inArray(mw.config.get('wgTitle'), tools) != -1) return;
			}
			
			//add this tool
			tools.push(mw.config.get('wgTitle'));
			
			//set it
			$.cookie(this.ti_cookie, tools.toString());
		}
		
	};
	
	//FIRE!!!
	WH.ToolInfo.init();

})(jQuery);
