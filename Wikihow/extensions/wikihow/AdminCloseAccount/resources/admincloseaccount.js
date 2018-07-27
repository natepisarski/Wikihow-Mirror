/*global jQuery*/
( function( window, document, $ ) {
	'use strict';

	var editToken;
	var loading = null;
	var next = null;
	var queried = false;
	var cache = { fuzzy: {}, strict: {} };

	var states = {
		'search_results': {
			toggle: [ '#aca_search_results' ],
			enter: function ( params ) {
				$( '#aca_search_form' ).show();
				var $template = $( '#aca_search_result_template' ),
					$results = $( '#aca_search_result_container' );

				if ( params.blank ) {
					return;
				}
				if ( params.loading ) {
					$( '#aca_search_results' ).addClass( 'aca_loading' );
					$( '#aca_search_results_loading' ).show();
				} else if ( params.users ) {
					$( '#aca_search_results' ).removeClass( 'aca_loading' );
					if ( !params.users.length ) {
						$( '#aca_search_results_none' ).show();
						return;
					}
					$( '#aca_search_results_some' ).show();
					$results.append( params.users.map( function ( user ) {
						var $result = $template.clone();
						var registered = user.id !== null;

						$result.attr( 'id', '' ).addClass( 'aca_search_result_rendered' ).show();
						$result.find( '.aca_search_result_avatar span' )
							.css( { backgroundImage: 'url(' + user.avatar + ')' } );
						$result.find( '.aca_search_result_username' ).append(
							registered ?
								$( '<a>' )
									.attr( { target: '_blank', href: user.url } )
									.text( user.name )
									.add(
										$( '<span>' )
											.addClass( 'aca_mute' )
											.text( ' (' + user.id + ')' )
									) :
								$( '<span>' )
									.addClass( 'aca_mute' )
									.text( '(anonymous)' )
						);
						$result.find( '.aca_search_result_useremail' ).append(
							user.email ?
								$( '<span>' )
									.toggleClass( 'aca_confirmed', user.confirmed )
									.text( user.email ) :
								$( '<span>' ).addClass( 'aca_mute' ).text( '(no email)' )
						);
						$result.find( '.aca_search_result_edits' ).append(
							user.edits ?
								$( '<a>' )
									.attr( {
										target: '_blank',
										href: '/Special:Contributions?target=' + user.name
									} )
									.text( user.edits + ( user.edits === 1 ? ' edit' : ' edits' ) ) :
								$( '<span>' )
									.addClass( 'aca_mute' )
									.text( '(no edits)' )
						);
						var date = new Date( user.since );
						date = [ date.getYear() + 1900, date.getMonth(), date.getDay() ].join( '/' );
						$result.find( '.aca_search_result_registration' )
							.addClass( 'aca_mute' )
							.append( $( '<p>' ).text( 'Since' ), $( '<p>' ).text( date ) );
						$result.find( '.aca_remove_submit' )
							.text( registered ? 'Close Account' : 'Remove Email' )
							.on( 'click', function () {
								if ( confirm( 'Are you sure? This action is not reversible.' ) ) {
									change( 'remove_loading', { user: user } );
								}
							} );

						return $result;
					} ) );
				}
			},
			exit() {
				$( '.aca_search_result_rendered' ).remove();
				$( '#aca_search_results_loading' ).hide();
				$( '#aca_search_results_none' ).hide();
				$( '#aca_search_results_some' ).hide();
			}
		},
		'remove_loading': {
			toggle: [ '#aca_remove_loading' ],
			enter: function ( params ) {
				$( '#aca_remove_loading_subject' ).text( params.user.name || params.user.email );
				$( '#aca_search_form' ).hide();

				// Purge cache since the about-to-be-removed user is in there
				cache = { fuzzy: {}, strict: {} };
				var action = params.user.id !== null ? 'close_account' : 'remove_email';
				var data = {
					editToken: editToken,
					action: action,
					username: params.user.name,
					email: params.user.email
				};
				queried = false;
				$.ajax( {
					type: 'POST',
					dataType: 'json',
					url: '/Special:AdminCloseAccount',
					data: data
				} )
					.done( function ( response ) {
						change( 'remove_results', response );
					} )
					.fail( function ( response ) {
						change( 'error', parseError( response ) );
					} );
			}
		},
		'remove_results': {
			toggle: [ '#aca_remove_results' ],
			enter: function ( params ) {
				$( '#aca_remove_results_target' ).text( params.target );
				var $changes = $( '#aca_remove_results_changes' ).empty();
				if ( params.results.changes.length ) {
					params.results.changes.forEach( function ( change ) {
						$changes.append( $( '<li>' ).text( change ) );
					} );
				} else {
					$changes.append( $( '<li>' ).text( 'None' ) );
				}
				var $warnings = $( '#aca_remove_results_warnings' ).empty();
				if ( params.results.warnings.length ) {
					params.results.warnings.forEach( function ( warning ) {
						$warnings.append( $( '<li>' ).text( warning ) );
					} );
				} else {
					$warnings.append( $( '<li>' ).text( 'None' ) );
				}
				$( '#aca_search_form' )[0].reset();
			}
		},
		'error': {
			toggle: [ '#aca_error' ],
			enter: function ( params ) {
				$( '#aca_error_msg' ).text( params.error );
			}
		}
	};

	var currentState = null;

	function change( state, data ) {
		if ( state in states ) {
			// Leave current state
			if ( currentState in states ) {
				if ( states[currentState].toggle ) {
					$( states[currentState].toggle.join() ).hide();
				}
				if ( states[currentState].exit ) {
					states[currentState].exit();
				}
			}

			// Enter new state
			if ( states[state].toggle ) {
				$( states[state].toggle.join() ).show();
			}
			if ( states[state].enter ) {
				states[state].enter( data || {} );
			}
			currentState = state;
		}
	}

	function parseError( response ) {
		var obj;
		try {
			return JSON.parse( response.responseText );
		} catch ( error ) {
			return { error: 'Invalid response from server.' };
		}
	}

	function queryUsers( query, fuzzy ) {
		var mode = fuzzy ? 'fuzzy' : 'strict';
		next = function () {
			if ( !query ) {
				change( 'search_results', { blank: true } );
				return;
			}
			if ( query in cache[mode] ) {
				change( 'search_results', cache[mode][query] );
				return;
			}
			if ( !queried ) {
				change( 'search_results', { loading: true } );
				queried = true;
			}
			$( '#aca_search_form,#aca_search_results' ).addClass( 'aca_loading' );
			loading = $.ajax( {
				type: 'POST',
				dataType: 'json',
				url: '/Special:AdminCloseAccount',
				data: {
					action: 'query_users',
					query: query,
					fuzzy: fuzzy,
					editToken: editToken
				}
			} )
				.done( function ( response ) {
					cache[mode][query] = response;
					if ( !next ) {
						change( 'search_results', response );
					}
				} )
				.fail( function ( response ) {
					change( 'error', parseError( response ) );
				} )
				.always( function () {
					$( '#aca_search_form,#aca_search_results' ).removeClass( 'aca_loading' );
				} );
		};
		if ( loading ) {
			loading.then( function () {
				if ( next ) {
					next();
					next = null;
				}
			} );
		} else {
			next();
			next = null;
		}
	}

	$( function () {
		editToken = $( '#aca_edit_token' ).val();
		function query() {
			queryUsers( $( '#aca_search_query' ).val(), $( '#aca_search_fuzzy:checked' ).val() );
		}
		$( '#aca_search_query' ).on( 'input', query );
		$( '#aca_search_fuzzy' ).on( 'input', query );
		$( '#aca_search_form' ).on( 'submit', function () {
			query();
			return false;
		} );
		$( '#aca_remove_done,#aca_error_done' ).on( 'click', function () {
			change( 'search_results', { blank: true } );
		} );
		change( 'search_results', { blank: true } );
	} );

}( window, document, jQuery ) );
