( function () {
	/**
	 * @enum
	 * @alternateClassName gt.TransitionAction
	 *
	 * Special actions to take when there's a transition (other than simply returning
	 * another step)
	 */
	mw.guidedTour.TransitionAction = {
		/**
		 * Hide/do not show the tour, but maintain the user's saved position
		 */
		HIDE: 1,

		/**
		 * End the tour, removing the user's saved state (including their saved
		 * position).
		 */
		END: 2
	};
}() );
