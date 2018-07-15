/**
 * Animate patrol links to use asynchronous API requests to
 * patrol pages, rather than navigating to a different URI.
 *
 * @since 1.21
 * @author Marius Hoch <hoo@online.de>
 */
( function ( mw, $ ) {
	if ( !mw.user.tokens.exists( 'patrolToken' ) ) {
		// Current user has no patrol right, or an old cached version of user.tokens
		// that didn't have patrolToken yet.
		return;
	}
	$( function () {
		var $patrolLinks = $( '.patrollink a' );
		$patrolLinks.on( 'click', function ( e ) {
			var $spinner, href, rcid, apiRequest;

			// Start preloading the notification module (normally loaded by mw.notify())
			mw.loader.load( ['mediawiki.notification'], null, true );

			// Hide the link and create a spinner to show it inside the brackets.
			$spinner = $.createSpinner( {
				size: 'small',
				type: 'inline'
			} );
			$( this ).hide().after( $spinner );

			// Reuben, upgrade 1.21: Do JS patrolling from Special:RecentChanges, ported
			// from wikiHow 1.12
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

			href = $( this ).attr( 'href' );
			rcid = mw.util.getParamValue( 'rcid', href );
			apiRequest = new mw.Api();

			apiRequest.post( {
				action: 'patrol',
				token: mw.user.tokens.get( 'patrolToken' ),
				rcid: rcid
			} )
			.done( function ( data ) {
				// Reuben, upgrade 1.21: we modify these links and spinners instead of
				// removing
				// Remove all patrollinks from the page (including any spinners inside).
				//$patrolLinks.closest( '.patrollink' ).remove();
				if ( data.patrol !== undefined ) {
					// Success
					var title = new mw.Title( data.patrol.title );
					mw.notify( mw.msg( 'markedaspatrollednotify', title.toText() ) );
				} else {
					// This should never happen as errors should trigger fail
					mw.notify( mw.msg( 'markedaspatrollederrornotify' ) );
				}

				// Reuben, upgrade 1.21: Port JS Patrolling
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
			} )
			.fail( function ( error ) {
				$spinner.remove();
				// Restore the patrol link. This allows the user to try again
				// (or open it in a new window, bypassing this ajax module).
				$patrolLinks.show();
				if ( error === 'noautopatrol' ) {
					// Can't patrol own
					mw.notify( mw.msg( 'markedaspatrollederror-noautopatrol' ) );
				} else {
					mw.notify( mw.msg( 'markedaspatrollederrornotify' ) );
				}
			} );

			e.preventDefault();
		} );

		// Reuben, upgrade 1.21: Animate rollback links with RecentChanges too
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
}( mediaWiki, jQuery ) );
