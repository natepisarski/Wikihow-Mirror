// Add this widget to the WH.dashboard module
WH.dashboard.TweetItForwardWidget = (function($) {

	// Make aliases for things we use a lot
	var animateUpdate = WH.dashboard.animateUpdate,
		animateUpdateImage = WH.dashboard.animateUpdateImage,
		unpatrolledNode = null,
		completedNode = null,
		lastImage = null,
		lastName = null,
		lastTime = null,
		topImage = null,
		topName = null,
		topTime = null;

	// Our new widget class
	function TweetItForwardWidget() {

		// Called by WH.dashboard.init after it's done running
		this.init = function() {
			unpatrolledNode = $('.comdash-widget-TweetItForwardWidget .comdash-count span');
			lastImage = $('.comdash-widget-TweetItForwardWidget .comdash-lastcontributor .avatar');
			lastName = $('.comdash-widget-TweetItForwardWidget .comdash-lastcontributor .name');
			lastTime = $('.comdash-widget-TweetItForwardWidget .comdash-lastcontributor .time');
			completedNode = $('.comdash-widget-TweetItForwardWidget .comdash-today');
			topImage = $('.comdash-widget-TweetItForwardWidget .comdash-topcontributor .avatar');
			topName = $('.comdash-widget-TweetItForwardWidget .comdash-topcontributor .name');
			topTime = $('.comdash-widget-TweetItForwardWidget .comdash-topcontributor .time');
			var weatherIcon = "sunny";
			this.animateUpdateWeather(weatherIcon);
		};

		this.getWidgetName = function() {
			return "TweetItForwardWidget";
		};

		// Called by WH.dashboard after new data has been downloaded from
		// the server.
		//
		// @param type either 'global' or 'user'
		this.listenData = function(type, data) {
			if (type == 'global') {
				var img = this.getAvatarLink(data['lt']['im'], data['lt']['hp']);
				var userLink = this.getUserLink(data['lt']['na']);
				var topImg = this.getAvatarLink(data['tp']['im'], data['tp']['hp']);
				var topUserLink = this.getUserLink(data['tp']['na']);

				var weatherIcon = "sunny";
				this.animateUpdateWeather(weatherIcon);

				animateUpdateImage(lastImage, img);
				animateUpdate(lastName, userLink, this.getWidgetName());
				animateUpdate(lastTime, data['lt']['da'], this.getWidgetName());
				animateUpdateImage(topImage, topImg);
				animateUpdate(topName, topUserLink, this.getWidgetName());
				animateUpdate(topTime, data['tp']['da'], this.getWidgetName());
			} else if (type == 'user') {
				var completion = data;
				$(completedNode).show();
			}
		};

	}

	// Make our widget inherit from the base widget
	TweetItForwardWidget.prototype = new WH.dashboard.DashboardWidget();

	// Instantiate this widget
	widget = new TweetItForwardWidget();

	// Listen for data updates from the server
	WH.dashboard.registerDataListener('TweetItForwardWidget', widget);

	return widget;
})(jQuery);

