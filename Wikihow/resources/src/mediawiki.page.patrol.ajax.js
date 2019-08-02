/*!
 * Animate patrol links to use asynchronous API requests to
 * patrol pages, rather than navigating to a different URI.
 *
 * @since 1.21
 * @author Marius Hoch <hoo@online.de>
 */
( function () {
	if ( !mw.user.tokens.exists( 'patrolToken' ) ) {
		// Current user has no patrol right, or an old cached version of user.tokens
		// that didn't have patrolToken yet.
		return;
	}
	$( function () {
		// WikiHow: modified selector from '.patrollink[data-mw="interface"] a'
		var $patrolLinks = $( '.patrollink a' );
		$patrolLinks.on( 'click', function ( e ) {
			var $spinner, rcid, apiRequest;

			// Preload the notification module for mw.notify
			mw.loader.load( 'mediawiki.notification' );

			// Hide the link and create a spinner to show it inside the brackets.
			$spinner = $.createSpinner( {
				size: 'small',
				type: 'inline'
			} );
			$( this ).hide().after( $spinner );

			// Wikihow: This allows us to do JS patrolling from Special:RecentChanges
			var sharedParent = $( this ).parent().parent();
			var patrolNextNode = $( '.patrolnextlink', sharedParent ).first();
			var patrolNext = patrolNextNode.text();
			var isSkip = $( this ).hasClass('patrolskip');
			var patrolLink = $( this );

			if (isSkip) {
				if (patrolNext) {
					location.href = patrolNext;
				} else {
					mw.notify( 'Done patrolling!' );
					$spinner.remove();
					$patrolLinks.show();
					patrolLink.html('Done patrolling');
				}
				return false;
			}

			rcid = mw.util.getParamValue( 'rcid', this.href );
			apiRequest = new mw.Api();

			apiRequest.postWithToken( 'patrol', {
				formatversion: 2,
				action: 'patrol',
				rcid: rcid
			} ).done( function ( data ) {
				var title;
				// Remove all patrollinks from the page (including any spinners inside).
				// Wikihow: We modify these links and spinners instead of removing
				//$patrolLinks.closest( '.patrollink' ).remove();
				if ( data.patrol !== undefined ) {
					// Success
					title = new mw.Title( data.patrol.title );
					mw.notify( mw.msg( 'markedaspatrollednotify', title.toText() ) );
				} else {
					// This should never happen as errors should trigger fail
					mw.notify( mw.msg( 'markedaspatrollederrornotify' ), { type: 'error' } );
				}

				// Wikihow: Port JS Patrolling
				if (patrolNext) {
					window.setTimeout( function () {
						location.href = patrolNext;
					}, 300);
				} else {
					mw.notify( 'Done patrolling!' );
					$spinner.remove();
					$patrolLinks.show();
					patrolLink.html('Done patrolling');
				}
			} ).fail( function ( error ) {
				$spinner.remove();
				// Restore the patrol link. This allows the user to try again
				// (or open it in a new window, bypassing this ajax module).
				$patrolLinks.show();
				if ( error === 'noautopatrol' ) {
					// Can't patrol own
					mw.notify( mw.msg( 'markedaspatrollederror-noautopatrol' ), { type: 'warn' } );
				} else {
					mw.notify( mw.msg( 'markedaspatrollederrornotify' ), { type: 'error' } );
				}
			} );

			e.preventDefault();
		} );

		// Wikihow: Animate rollback links in RecentChanges too
		var $rollbackLinks = $( '.mw-rollback-link a' );
		$rollbackLinks.on( 'click', function ( e ) {
			// Hide the link and create a spinner to show it inside the brackets.
			var $spinner = $.createSpinner( {
				size: 'small',
				type: 'inline'
			} );
			$( this ).hide().after( $spinner );

			var href = $( this ).attr( 'href' );
			var fromRC = mw.util.getParamValue( 'fromrc', location.href );
			if (fromRC) {
				$.get( href )
				.done( function ( data ) {
					$spinner.replaceWith( 'Rollback complete' );
				} );
				return false;
			} else {
				return true;
			}
		} );

	} );
}() );
