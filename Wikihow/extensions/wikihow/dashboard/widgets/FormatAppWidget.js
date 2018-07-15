// Add this widget to the WH.dashboard module
WH.dashboard.FormatAppWidget = (function($) {

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
	function FormatAppWidget() {

		// Called by WH.dashboard.init after it's done running
		this.init = function() {
			unpatrolledNode = $('.comdash-widget-FormatAppWidget .comdash-count span');
			lastImage = $('.comdash-widget-FormatAppWidget .comdash-lastcontributor .avatar');
			lastName = $('.comdash-widget-FormatAppWidget .comdash-lastcontributor .name');
			lastTime = $('.comdash-widget-FormatAppWidget .comdash-lastcontributor .time');
			completedNode = $('.comdash-widget-FormatAppWidget .comdash-today');
			topImage = $('.comdash-widget-FormatAppWidget .comdash-topcontributor .avatar');
			topName = $('.comdash-widget-FormatAppWidget .comdash-topcontributor .name');
			topTime = $('.comdash-widget-FormatAppWidget .comdash-topcontributor .time');

			/*$('.comdash-widget-FormatAppWidget .comdash-start').click(function(){
				gatTrack('comm_engagement', 'format_start', 'dashboard');
			});
			$('.comdash-widget-FormatAppWidget .comdash-login').click(function(){
				gatTrack('comm_engagement', 'format_login', 'dashboard');
			});*/
		};

		this.getWidgetName = function(){
			return "FormatAppWidget";
		}

		// Called by WH.dashboard after new data has been downloaded from
		// the server.
		//
		// @param type either 'global' or 'user'
		this.listenData = function(type, data) {
			if (type == 'global') {
				var unpatrolled = data['ct'];
				var img = this.getAvatarLink(data['lt']['im'], data['lt']['hp']);
				var userLink = this.getUserLink(data['lt']['na']);
				var topImg = this.getAvatarLink(data['tp']['im'], data['tp']['hp']);
				var topUserLink = this.getUserLink(data['tp']['na']);

				//get weather
				var weatherIcon = this.getWeatherIcon(unpatrolled);
				this.animateUpdateWeather(weatherIcon);

				animateUpdate(unpatrolledNode, unpatrolled, this.getWidgetName());
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
	FormatAppWidget.prototype = new WH.dashboard.DashboardWidget();

	// Instantiate this widget
	widget = new FormatAppWidget();

	// Listen for data updates from the server
	WH.dashboard.registerDataListener('FormatAppWidget', widget);

	return widget;
})(jQuery);

