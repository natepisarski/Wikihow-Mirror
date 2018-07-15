if (WH.dashboard && !WH.dashboard.DashboardWidget) {
	WH.dashboard.DashboardWidget = function () {
		this.listenData = function(type, data) {
			// this method needs to be overridden
		};

		this.getWidgetName = function(){
			//this method needs to be overridden
		}

		this.getAvatarURL = function(img, path) {
			var type, param;
			var m = img.match(/([^:]*)(:(.*))/);
			if (m) {
				type = m[1];
				param = m[3];
			} else {
				type = 'df';
			}

			if (type == 'df') {
				return wfGetPad('/skins/WikiHow/images/80x80_user.png');
			} else if (type == 'fb' || type == 'gp') {
				return param;
			} else {
			    hash = unescape(path);
				return wfGetPad('/images/avatarOut/' + hash + param);
			}
		};

		this.getUserLink = function(username) {
			if(username == "Anonymous")
				return '<a title="wikiHow:Anonymous" href="/wikiHow:Anonymous">' + username + '</a>';
			if(username.length > WH.dashboard.usernameMaxLength)
				shortenedName = username.substring(0, WH.dashboard.usernameMaxLength - 3) + "...";
			else
				shortenedName = username;
			return '<a title="User:' + username + '" href="/User:' + username + '">' + shortenedName + '</a>';
		};

		this.getAvatarLink = function(img, path) {
			return '<img src="' + this.getAvatarURL(img, path) + '" />';
		};

		/**
		 * Takes a current count for this widget and
		 * compares to the threshold to determine what the
		 * weather should be.
		 */
		this.getWeatherIcon = function(count){
			count = count.replace(",", ""); //get rid of commas
			
			var widName = this.getWidgetName();
			var thresholds = WH.dashboard.thresholds(widName);

			var low = parseInt(thresholds["low"]);
			var med = parseInt(thresholds["med"]);
			var high = parseInt(thresholds["high"]);

			if(count <= low)
				return "sunny";
			else if(count <= med)
				return "cloudy";
			else if(count <= high)
				return "rainy";
			else
				return "stormy";
		}

		/**
		 * Updates the weather icon and its text (but only if necessary
		 */
		this.animateUpdateWeather = function(weatherStatus){
			var widName = this.getWidgetName();
			$.each($(".comdash-widget-" + widName + " .comdash-weather"), function(key, value){
				if($(value).hasClass(weatherStatus)){
					if($(value).hasClass('active')){
						//already showing this weather, so do nothing
						return;
					}
					else{
						//fade out the old one
						$(".comdash-widget-" + widName + " .comdash-weather.active").fadeOut('slow', function(){
							$(this).removeClass('active');
						});
						//fade in the new one
						$(value).fadeIn('slow', function(){
							$(this).addClass('active');
						})
					}
				}
			});
		}
	};
}

