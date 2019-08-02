// Add this widget to the WH.dashboard module
WH.dashboard.ArticleFeedbackAppWidget = (function() {

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
	function ArticleFeedbackAppWidget() {

		this.getWidgetName = function(){
			return "ArticleFeedbackAppWidget";
		}

		// Called by WH.dashboard.init after it's done running
		this.init = function() {
			unpatrolledNode = $('.comdash-widget-ArticleFeedbackAppWidget .comdash-count span');
			lastImage = $('.comdash-widget-ArticleFeedbackAppWidget .comdash-lastcontributor .avatar');
			lastName = $('.comdash-widget-ArticleFeedbackAppWidget .comdash-lastcontributor .name');
			lastTime = $('.comdash-widget-ArticleFeedbackAppWidget .comdash-lastcontributor .time');
			completedNode = $('.comdash-widget-ArticleFeedbackAppWidget .comdash-today');
			topImage = $('.comdash-widget-ArticleFeedbackAppWidget .comdash-topcontributor .avatar');
			topName = $('.comdash-widget-ArticleFeedbackAppWidget .comdash-topcontributor .name');
			topTime = $('.comdash-widget-ArticleFeedbackAppWidget .comdash-topcontributor .time');

			/*$('.comdash-widget-ArticleFeedbackAppWidget .comdash-start').click(function(){
				gatTrack('comm_engagement', 'categorize_start', 'dashboard');
			});
			$('.comdash-widget-ArticleFeedbackAppWidget .comdash-login').click(function(){
				gatTrack('comm_engagement', 'categorize_login', 'dashboard');
			});*/
		};

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
	ArticleFeedbackAppWidget.prototype = new WH.dashboard.DashboardWidget();

	// Instantiate this widget
	widget = new ArticleFeedbackAppWidget();

	// Listen for data updates from the server
	WH.dashboard.registerDataListener('ArticleFeedbackAppWidget', widget);

	return widget;
})();
