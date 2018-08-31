( function($, mw) {
'use strict';

var $jqDocument = $(document),
	$jqWindow = $(window);

var $steps = null,
	$firstStep,
	$lastStep;

var firstStepPingSent = false,
	lastStepPingSent = false;

// send a couple stats pings when certain reading checkpoints are hit, as
// as defined by Alissa
function maybeDoStatsPing() {
	// lazy compute these variables since they might not
	// be available if RL loads in the document <head>
	if ($steps === null) {
		$steps = $('.step');
		$firstStep = $steps.first();
		$lastStep = $steps.last();
	}

	// no steps section -- maybe not an article page
	if ($steps.length === 0) {
		return;
	}

	// already did both pings
	if (firstStepPingSent && lastStepPingSent) {
		return;
	}
	var sendFirst = false, sendLast = false;
	var firstStepLine, lastStepLine;

	// calculate the pixel position of the bottom of the viewport window
	var viewportHeight = $jqWindow.height();
	var scrollTop = $jqDocument.scrollTop();
	var bottomLineViewport = scrollTop + viewportHeight;

	// calculate the page's pixel position of the bottom of the first and last step, and
	// whether or not to send the respective pings
	if (!firstStepPingSent) {
		firstStepLine = Math.round( $firstStep.offset().top ) + $firstStep.height();
		sendFirst = bottomLineViewport > firstStepLine;
	}
	if (!lastStepPingSent) {
		lastStepLine = Math.round( $lastStep.offset().top ) + $lastStep.height();
		sendLast = bottomLineViewport > lastStepLine;
	}

	// nothing to do if there is nothing to send
	if (!sendFirst && !sendLast) {
		return;
	}

	var docHeight = Math.round( $jqDocument.height() );
	if (sendFirst) {
		firstStepPingSent = true;
		WH.Stu.ping({ 'ev': 'first_step_view', 'he': docHeight, 'bt': firstStepLine });
	}
	if (sendLast) {
		lastStepPingSent = true;
		WH.Stu.lastStepPingSent = true; // this variable is used for A5-7 in stu.js
		WH.Stu.ping({ 'ev': 'last_step_view', 'he': docHeight, 'bt': lastStepLine });
	}
}

// check wgNamespaceNumber to make sure it's NS_MAIN and wgIsArticle
// to make sure we're not on the edit/history/etc page
if (mw.config.get('wgNamespaceNumber') === 0 && mw.config.get('wgIsArticle')) {
	WH.addThrottledScrollHandler(maybeDoStatsPing);
}

}(jQuery, mediaWiki) );
