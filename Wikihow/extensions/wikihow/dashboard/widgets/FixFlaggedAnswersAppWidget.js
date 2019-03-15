// Add this widget to the WH.dashboard module
WH.dashboard.FixFlaggedAnswersAppWidget = (function($) {

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
	function FixFlaggedAnswersAppWidget() {

		// Called by WH.dashboard.init after it's done running
		this.init = function() {
			unpatrolledNode = $('.comdash-widget-FixFlaggedAnswersAppWidget .comdash-count span');
			completedNode = $('.comdash-widget-FixFlaggedAnswersAppWidget .comdash-today');
		};

		this.getWidgetName = function(){
			return "FixFlaggedAnswersAppWidget";
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
	FixFlaggedAnswersAppWidget.prototype = new WH.dashboard.DashboardWidget();

	// Instantiate this widget
	widget = new FixFlaggedAnswersAppWidget();

	// Listen for data updates from the server
	WH.dashboard.registerDataListener('FixFlaggedAnswersAppWidget', widget);

	return widget;
})(jQuery);

