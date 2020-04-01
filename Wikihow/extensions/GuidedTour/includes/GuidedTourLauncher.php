<?php

/**
 * Allows server-side launching of tours (without the URL parameter).
 */
class GuidedTourLauncher {
	/**
	 * State used to tell the client to directly launch tours using a client-side $wg
	 *
	 * @var array|null $directLaunchState
	 */
	protected static $directLaunchState = null;

	// This matches the format used on the client-side (e.g.
	// mw.guidedTour.internal.getInitialUserStateObject,
	// mw.guidedTour.launchTourFromUserState, etc.
	/**
	 * Get new state from old state.  The state describes the user's progress
	 * in the tour, and which step they are expected to see next.
	 *
	 * @param array|null $oldState Previous state
	 * @param string $tourName Tour name
	 * @param string $step Step to start at
	 * @return array New state
	 */
	protected static function getNewState( $oldState, $tourName, $step ) {
		$newState = $oldState;

		if ( $newState === null ) {
			$newState = [];
		}

		$newState = array_replace_recursive( $newState, [
			'version' => 1,
			'tours' => [
				$tourName => [
					'step' => $step,
				],
			]
		] );

		return $newState;
	}

	/**
	 * Adds a tour to the cookie
	 *
	 * @param string|null $oldCookieValue Previous value of cookie
	 * @param string $tourName Tour name
	 * @param string $step Step to start at
	 * @return string Value of new cookie
	 */
	public static function getNewCookie( $oldCookieValue, $tourName, $step ) {
		if ( $oldCookieValue == null ) {
			$oldCookieValue = '{}';
		}

		$oldState = FormatJson::decode( $oldCookieValue, true );
		if ( $oldState === null ) {
			$oldState = [];
		}

		$newState = self::getNewState( $oldState, $tourName, $step );

		return FormatJson::encode( $newState );
	}

	/**
	 * Sets a tour to auto-launch on this view
	 *
	 * @param string $tourName Name of tour to launch
	 * @param string $step Step to navigate to
	 */
	public static function launchTour( $tourName, $step ) {
		global $wgOut;

		self::$directLaunchState = self::getNewState(
			self::$directLaunchState,
			$tourName,
			$step
		);

		GuidedTourHooks::addTour( $wgOut, $tourName );
	}

	/**
	 * Adds a client-side $wg variable to control tour launch
	 *
	 * @param array &$vars Array of request-specific JavaScript config variables
	 * @param OutputPage $out
	 */
	public static function addLaunchVariable( array &$vars, OutputPage $out ) {
		if ( self::$directLaunchState !== null ) {
			$vars['wgGuidedTourLaunchState'] = self::$directLaunchState;
		}
	}

	/**
	 * Sets a tour to auto-launch on this view using a cookie.
	 *
	 * @param string $tourName Name of tour to launch
	 * @param string $step Step to navigate to
	 */
	public static function launchTourByCookie( $tourName, $step ) {
		global $wgOut, $wgRequest;

		$oldCookie = $wgRequest->getCookie( GuidedTourHooks::COOKIE_NAME );
		$newCookie = self::getNewCookie( $oldCookie, $tourName, $step );
		$wgRequest->response()->setCookie( GuidedTourHooks::COOKIE_NAME, $newCookie, 0, [
			'httpOnly' => false,
		] );

		GuidedTourHooks::addTour( $wgOut, $tourName );
	}
}
