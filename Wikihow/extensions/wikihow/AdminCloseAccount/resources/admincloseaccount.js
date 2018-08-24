/*global jQuery*/
( function( window, document, $ ) {
	'use strict';

	var editToken;
	var loading = null;
	var next = null;
	var queried = false;
	var cache = { fuzzy: {}, strict: {} };

	var states = {
		'query_results': {
			toggle: [ '#aca_query_results' ],
			enter: function ( params ) {
				$( '#aca_query_form' ).show();
				var $template = $( '#aca_query_result_template' ),
					$results = $( '#aca_query_result_container' );

				if ( params.blank ) {
					return;
				}
				if ( params.loading ) {
					$( '#aca_query_results' ).addClass( 'aca_loading' );
					$( '#aca_query_results_loading' ).show();
				} else if ( params.users ) {
					$( '#aca_query_results' ).removeClass( 'aca_loading' );
					if ( !params.users.length ) {
						$( '#aca_query_results_none' ).show();
						return;
					}
					$( '#aca_query_results_some' ).show();
					$results.append( params.users.map( function ( user ) {
						var $result = $template.clone();
						var registered = user.id !== null;

						$result.attr( 'id', '' ).addClass( 'aca_query_result_rendered' ).show();
						$result.find( '.aca_query_result_avatar span' )
							.css( { backgroundImage: 'url(' + user.avatar + ')' } );
						$result.find( '.aca_query_result_username' ).append(
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
						$result.find( '.aca_query_result_useremail' ).append(
							user.email ?
								$( '<span>' )
									.toggleClass( 'aca_confirmed', user.confirmed )
									.text( user.email ) :
								$( '<span>' ).addClass( 'aca_mute' ).text( '(no email)' )
						);
						$result.find( '.aca_query_result_edits' ).append(
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
						$result.find( '.aca_query_result_registration' )
							.addClass( 'aca_mute' )
							.append( $( '<p>' ).text( 'Since' ), $( '<p>' ).text( date ) );
						$result.find( '.aca_describe_submit' )
							.text( 'Review' )
							.on( 'click', function () {
								change( 'describe_loading', { user: user } );
							} );

						return $result;
					} ) );
				}
			},
			exit() {
				$( '.aca_query_result_rendered' ).remove();
				$( '#aca_query_results_loading' ).hide();
				$( '#aca_query_results_none' ).hide();
				$( '#aca_query_results_some' ).hide();
			}
		},
		'describe_loading': {
			toggle: [ '#aca_describe_loading' ],
			enter: function ( params ) {
				var registered = params.user.id !== null;
				$( '#aca_describe_loading_subject' ).text(
					registered ? params.user.name + ' (' + params.user.id + ')' : params.user.email
				);
				$( '#aca_query_form' ).hide();

				var data = {
					editToken: editToken,
					action: 'describe'
				};
				if ( params.user.name ) {
					data.name = params.user.name;
				} else {
					data.email = params.user.email;
				}
				queried = false;
				$.ajax( {
					type: 'POST',
					dataType: 'json',
					url: '/Special:AdminCloseAccount',
					data: data
				} )
					.done( function ( response ) {
						change( 'describe_results', { user: params.user, response: response } );
					} )
					.fail( function ( response ) {
						change( 'error', parseError( response ) );
					} );
			}
		},
		'describe_results': {
			toggle: [ '#aca_describe_results' ],
			enter: function ( params ) {
				var registered = params.user.id !== null;
				var response = params.response;
				$( '#aca_describe_results_target' ).text( response.target );

				var someChanges = response.results.changes.length;
				$( '#aca_describe_results_changes_some' ).toggle( !!someChanges );
				$( '#aca_describe_results_changes_none' ).toggle( !someChanges );
				var $changes = $( '#aca_describe_results_changes' ).empty();
				response.results.changes.forEach( function ( change ) {
					$changes.append( $( '<li>' ).html( change ) );
				} );

				var someWarnings = response.results.warnings.length;
				$( '#aca_describe_results_warnings_some' ).toggle( !!someWarnings );
				$( '#aca_describe_results_warnings_none' ).toggle( !someWarnings );
				var $warnings = $( '#aca_describe_results_warnings' ).empty();
				response.results.warnings.forEach( function ( warning ) {
					$warnings.append( $( '<li>' ).html( warning ) );
				} );

				$( '#aca_execute_submit' )
					.text( registered ? 'Close Account' : 'Remove Email' )
					.off( 'click' )
					.one( 'click', function () {
						if ( confirm( 'Are you sure? This action is not reversible.' ) ) {
							change( 'execute_loading', {
								user: params.user,
								executeToken: response.executeToken
							} );
						}
					} );
				$( '#aca_query_form' )[0].reset();
			}
		},
		'execute_loading': {
			toggle: [ '#aca_execute_loading' ],
			enter: function ( params ) {
				var registered = params.user.id !== null;
				$( '#aca_execute_loading_subject' ).text(
					registered ? params.user.name + ' (' + params.user.id + ')' : params.user.email
				);

				// Purge cache since the about-to-be-removed user is in there
				cache = { fuzzy: {}, strict: {} };
				var data = {
					editToken: editToken,
					executeToken: params.executeToken,
					action: 'execute',
				};
				if ( params.user.name ) {
					data.name = params.user.name;
				} else {
					data.email = params.user.email;
				}
				$.ajax( {
					type: 'POST',
					dataType: 'json',
					url: '/Special:AdminCloseAccount',
					data: data
				} )
					.done( function ( response ) {
						change( 'execute_results', { user: params.user, response: response } );
					} )
					.fail( function ( response ) {
						change( 'error', parseError( response ) );
					} );
			}
		},
		'execute_results': {
			toggle: [ '#aca_execute_results' ],
			enter: function ( params ) {
				var response = params.response;
				$( '#aca_execute_results_target' ).text( response.target );

				var someChanges = response.results.changes.length;
				$( '#aca_execute_results_changes_some' ).toggle( !!someChanges );
				$( '#aca_execute_results_changes_none' ).toggle( !someChanges );
				var $changes = $( '#aca_execute_results_changes' ).empty();
				response.results.changes.forEach( function ( change ) {
					$changes.append( $( '<li>' ).html( change ) );
				} );

				var someWarnings = response.results.warnings.length;
				$( '#aca_execute_results_warnings_some' ).toggle( !!someWarnings );
				$( '#aca_execute_results_warnings_none' ).toggle( !someWarnings );
				var $warnings = $( '#aca_execute_results_warnings' ).empty();
				response.results.warnings.forEach( function ( warning ) {
					$warnings.append( $( '<li>' ).html( warning ) );
				} );

				$( '#aca_query_form' )[0].reset();
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
				change( 'query_results', { blank: true } );
				return;
			}
			if ( query in cache[mode] ) {
				change( 'query_results', cache[mode][query] );
				return;
			}
			if ( !queried ) {
				change( 'query_results', { loading: true } );
				queried = true;
			}
			$( '#aca_query_form,#aca_query_results' ).addClass( 'aca_loading' );
			loading = $.ajax( {
				type: 'POST',
				dataType: 'json',
				url: '/Special:AdminCloseAccount',
				data: {
					action: 'query',
					query: query,
					fuzzy: fuzzy,
					editToken: editToken
				}
			} )
				.done( function ( response ) {
					cache[mode][query] = response;
					if ( !next ) {
						change( 'query_results', response );
					}
				} )
				.fail( function ( response ) {
					change( 'error', parseError( response ) );
				} )
				.always( function () {
					$( '#aca_query_form,#aca_query_results' ).removeClass( 'aca_loading' );
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
			queryUsers( $( '#aca_query_query' ).val(), $( '#aca_query_fuzzy:checked' ).val() );
		}
		$( '#aca_query_query' ).on( 'input', query );
		$( '#aca_query_fuzzy' ).on( 'input', query );
		$( '#aca_query_form' ).on( 'submit', function () {
			query();
			return false;
		} );
		$( '#aca_execute_done,#aca_error_done,#aca_describe_cancel' ).on( 'click', function () {
			change( 'query_results', { blank: true } );
			$(window).scrollTop(0);
		} );
		change( 'query_results', { blank: true } );
	} );

}( window, document, jQuery ) );
