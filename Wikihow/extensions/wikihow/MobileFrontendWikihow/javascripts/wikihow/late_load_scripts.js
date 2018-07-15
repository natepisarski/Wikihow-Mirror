(function($) {
	window.WH = window.WH || {};
	window.WH.lateLoadScript = {
		
		initShares: function() {
			var click_cat = 'mobile_social_click';
			
			//PINTEREST
		   $('#pinterest_share_button').on('click', function(e) {
					e.preventDefault();
					var pin = document.createElement('script');
					pin.setAttribute('type', 'text/javascript');
					pin.setAttribute('charset', 'UTF-8');
					var link = 'http://assets.pinterest.com/js/pinmarklet.js?r=' + Math.random()*99999999;
					pin.setAttribute('src', link);
					document.body.appendChild(pin);
					
					WH.whEvent(click_cat, 'pin', '', wgTitle);
			});

			//FACEBOOK
			//This only loads the fb share script when the user clicks on the share icon
			$('#facebook_share_button').on('click', function(e) {
				e.preventDefault();

				//else do this:
				// FB JS SDK doesn't work on iOS Chrome, so this is the alternative method.
				if( navigator.userAgent.match('CriOS') ){
					var sharelink = "https://m.facebook.com/sharer.php?u=" + encodeURI(window.location.href) + "&app_id=" + wgFBAppId + "&referrer=social_plugin&_rdr"; 
					window.open(sharelink);
				} else {
					$.getScript('//connect.facebook.net/en_US/sdk.js', function(){
						FB.init({
						  appId: wgFBAppId,
						  version: 'v2.2'
						});
					//makes a share request only after the FB share script has been fetched and exectued with .getScript()
						FB.ui({
							method: 'share',
							href: window.location.href,
						}, function(response){});

					});
				}
					
				WH.whEvent(click_cat, 'fb', '', wgTitle);
			});
			
			//GPLUS
			$('#gplus_share_button').on('click', function(e) {
				WH.whEvent(click_cat, 'gplus', '', wgTitle);
			});
			
			//TWITTER
			$('#twitter_share_button').on('click', function(e) {
				WH.whEvent(click_cat, 'twitter', '', wgTitle);
			});
			
			//EMAIL
			$('#email_button').on('click', function() {
				WH.whEvent(click_cat, 'email', '', wgTitle);
			});
		},
		
		svgFix: function() {
			//can this client display SVGs?
			if (!document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#Shape", "1.0")) {
				//NO? L-a-a-a-a-a-a-me...
				$('#facebook_share_button').css('backgroundImage','url(/skins/owl/images/facebook2.png)');
				$('#gplus_share_button').css('backgroundImage','url(/skins/owl/images/gplus2.png)');
				$('#twitter_share_button').css('backgroundImage','url(/skins/owl/images/twitter3.png)');
				$('#email_button').css('backgroundImage','url(/skins/owl/images/email_icon.png)');
				$('#pinterest_share_button').css('backgroundImage','url(/skins/owl/images/pinterest.png)');
			}
		},
		
		shareTest: function() {
			$('#sharing_box').show();
			this.initShares();
			this.svgFix();
			$('.articleinfo').before($('#sharing_box'));
		},
		
		//make search placeholders disappear when the user clicks in
		//(instead of the standard behavior where it stays when the input is empty)
		placeholderToggle: function() {
			var placeholderVal = $('#search_footer .search_box').attr('placeholder');

			$('#search_footer .search_box').focus(function () {
			  $(this).attr('placeholder', '');
			});

			$('#search_footer .search_box').blur(function () {
			  $(this).attr('placeholder', placeholderVal);
			});
		},
		
		initPage: function() {
			if($(".customcontent").length == 0) {
				$('#article_rating_mobile').show();
				$('#footer_random_button').show();
			}
			this.placeholderToggle();
		}
	};

	WH.lateLoadScript.initPage();
})(jQuery);