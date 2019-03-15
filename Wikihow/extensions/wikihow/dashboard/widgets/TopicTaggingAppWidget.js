// Add this widget to the WH.dashboard module
WH.dashboard.TopicTaggingAppWidget = (function($) {

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
	function TopicTaggingAppWidget() {

		// Called by WH.dashboard.init after it's done running
		this.init = function() {
			unpatrolledNode = $('.comdash-widget-TopicTaggingAppWidget .comdash-count span');
			completedNode = $('.comdash-widget-TopicTaggingAppWidget .comdash-today');
		};

		this.getWidgetName = function(){
			return "TopicTaggingAppWidget";
		}

		// Called by WH.dashboard after new data has been downloaded from
		// the server.
		//
		// @param type either 'global' or 'user'
		this.listenData = function(type, data) {
			if (type == 'global') {
				var unpatrolled = data['ct'];

				//get weather
				var weatherIcon = this.getWeatherIcon(unpatrolled);
				this.animateUpdateWeather(weatherIcon);

				animateUpdate(unpatrolledNode, unpatrolled, this.getWidgetName());
			} else if (type == 'user') {
				var completion = data;
				$(completedNode).show();
			}
		};
	}

	// Make our widget inherit from the base widget
	TopicTaggingAppWidget.prototype = new WH.dashboard.DashboardWidget();

	// Instantiate this widget
	widget = new TopicTaggingAppWidget();

	// Listen for data updates from the server
	WH.dashboard.registerDataListener('TopicTaggingAppWidget', widget);

	return widget;
})(jQuery);

