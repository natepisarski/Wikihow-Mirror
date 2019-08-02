/**
 * Handles logging for GuidedTour, using EventLogging
 *
 * @class mw.guidedTour.EventLogger
 * @singleton
 *
 * @private
 */
( function () {
	var userId = mw.config.get( 'wgUserId' ) || 0,
		EventLogger, sessionIdPreCheck, sessionId, isSessionIdPersistent;

	sessionIdPreCheck = mw.user.sessionId();
	sessionId = mw.user.sessionId();

	// We want to know whether their browser will actually persist the session ID
	// Since mw.user.sessionId only stores it in the cookie (not in an instance
	// variable, etc.), this check should indicate whether it actually gets stored to
	// the cookie.
	isSessionIdPersistent = ( sessionIdPreCheck === sessionId );

	EventLogger = {
		/**
		 * Logs an EventLogging event if logging is enabled, and is a noop otherwise.
		 *
		 * Adds common fields (userId, tour, step, possibly sessionToken)
		 *
		 * @private
		 *
		 * @param {string} schemaName Name of schema to log to
		 * @param {mw.guidedTour.Step} step Step event is about
		 * @param {Object} [event={}] Event object; will be mutated to add
		 *  common information
		 * @return {jQuery.Promise}
		 */
		log: function ( schemaName, step, event ) {
			var tour = step.tour;

			if ( !tour.shouldLog ) {
				// Resolved promise
				return $.when();
			}

			event = event || {};
			$.extend( event, {
				userId: userId,
				tour: step.tour.name,
				step: step.name
			} );

			if ( isSessionIdPersistent ) {
				event.sessionToken = sessionId;
			}

			mw.eventLog.logEvent( schemaName, event );
		}
	};

	mw.guidedTour.EventLogger = EventLogger;
}() );
