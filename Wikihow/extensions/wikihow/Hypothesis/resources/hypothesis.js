$( function () {
	$( '#bodycontents' ).removeClass( 'minor_section' );

	var app = new WH.App( '#hyp' );
	app
		.mount( '/(is-:filter/?)(/?page-:page)', function ( params ) {
			app.get( 'hypxs', {
				filter: params.filter || '',
				page: params.page || ''
			} )
				.done( function ( hypxs ) {
					hypxs.experiments.forEach( addExperimentInfo );
					addIndexInfo( hypxs, params );
					render( 'index', hypxs );
					link( '.hyp-item', function () {
						return '/view/' + $( this ).data( 'id' );
					} );
					link( '.hyp-create', '/create' );
					action( '.hyp-purge', function () {
						if ( confirm( 'Are you sure you want to purge archived experiments' ) ) {
							return app.post( 'hypx', { purge: true } )
								.done( function () {
									app.go( '/' );
								} )
								.fail( error( 'Purge failed' ) );
						}
						return $.when();
					} );
				} )
				.fail( error( 'Load failed' ) );
		} )
		.mount( '/view/:id', function ( params ) {
			$.when( app.get( 'hypxs', params ), app.get( 'hypts', { experiment: params.id } ) )
				.done( function ( hypxs, hypts ) {
					addExperimentInfo( hypxs.experiment );
					hypts.tests.forEach( addTestInfo );
					render( 'view', {
						experiment: hypxs.experiment,
						tests: hypts.tests,
					} );
					[ 'start', 'pause', 'archive', 'unarchive' ].forEach( function ( val ) {
						action( '.hyp-' + val, function () {
							return app.post( 'hypx', { hypx_id: params.id, opti_action: val } )
								.done( function () {
									app.go( '/view/' + params.id );
								} )
								.fail( error( labelify( val ) + ' failed' ) );
						} );
					} );
				} )
				.fail( error( 'Load failed' ) );
		} )
		.mount( '/edit/:id', function ( params ) {
			$.when( app.get( 'hypxs', params ), app.get( 'hypts', { experiment: params.id } ) )
				.done( function ( hypxs, hypts ) {
					addExperimentInfo( hypxs.experiment );
					hypts.tests.forEach( addTestInfo );
					render( 'edit', {
						experiment: hypxs.experiment,
						tests: hypts.tests,
					} );
					action( '.hyp-save', function () {
						return app.post( 'hypx', addEditInputs( { hypx_id: params.id } ) )
							.done( function () {
								app.go( '/view/' + params.id );
							} )
							.fail( error( 'Save failed' ) );
					} );
				} )
				.fail( error( 'Load failed' ) );
		} )
		.mount( '/create', function ( params ) {
			var experiment = {
				hypx_holdback: 9900,
				hypx_target: 'all',
				hypx_status: 'not_started',
				page_titles: ''
			};
			addExperimentInfo( experiment );
			render( 'edit', { experiment: experiment } );
			action( '.hyp-create', function () {
				return app.post( 'hypx', addEditInputs( {} ) )
					.done( function ( hypx ) {
						app.go( '/view/' + hypx.hypx_id );
					} )
					.fail( error( 'Create failed' ) );
			} );
		} );

	app.start();

	/* Helper Functions */

	function addExperimentInfo( hypx ) {
		var target = hypx.hypx_target,
			targetLabels = {
				'desktop': 'desktop',
				'mobile': 'mobile',
				'all': 'desktop & mobile'
			};
			statusLabels = {
				'not_started': 'Not Started',
				'archived': 'Archived',
				'running': 'Running',
				'paused': 'Paused'
			};

		hypx.hypx_traffic = 100 - ( hypx.hypx_holdback / 100 );
		hypx.status = {
			label: statusLabels[hypx.hypx_status],
			class: hypx.hypx_status === 'running' ? 'hyp-running' : ''
		};
		hypx.page_titles = hypx.page_titles.replace( /-/g, ' ' ).replace( /,/g, ', ' );
		hypx[hypx.hypx_status === 'archived' ? 'canUnarchive' : 'canArchive'] = true;
		if ( hypx.hypx_status === 'running' ) {
			hypx.canPause = true;
		}
		if ( hypx.hypx_status === 'paused' || hypx.hypx_status === 'not_started' ) {
			hypx.canStart = true;
		}

		hypx.target = {
			label: targetLabels[target],
			options: [
				{
					label: targetLabels.desktop,
					value: 'desktop',
					selected: target === 'desktop' ? 'selected' : ''
				},
				{
					label: targetLabels.mobile,
					value: 'mobile',
					selected: target === 'mobile' ? 'selected' : ''
				},
				{
					label: targetLabels.all,
					value: 'all',
					selected: target === 'all' ? 'selected' : ''
				}
			]
		};

		return hypx;
	}

	function addTestInfo( hypt ) {
		hypt.timing = {
			dateA: moment( hypt.rev_timestamp_a, 'YYYYMMDDHHmmss' ).format( 'M/D/YY h:mmA' ),
			dateB: moment( hypt.rev_timestamp_b, 'YYYYMMDDHHmmss' ).format( 'M/D/YY h:mmA' )
		};
		hypt.title = hypt.page_title.replace( /-/g, ' ' );

		return hypt;
	}

	function addIndexInfo( data, params ) {
		function link( filter, page ) {
			return (
				'#/' +
				( filter ? 'is-' + filter : '' ) +
				( page ? ( filter ? '/' : '' ) + 'page-' + page : '' )
			);
		}

		data.filters = [
			{ label: 'Active', value: '' },
			{ label: 'Running', value: 'running' },
			{ label: 'Archived', value: 'archived' }
		].map( function ( filter ) {
			filter.selected = filter.value === ( params.filter || '' );
			if ( filter.selected ) {
				data.filterName = filter.label.toLowerCase();
			}
			filter.link = link( filter.value, filter.selected ? params.page : 0 );
			return filter;
		} );

		data.pagers = [
			{
				label: '◀',
				link: link( params.filter, Math.max( data.page - 1, 0 ) ),
				disabled: data.page <= 0
			},
			{
				label: '▶',
				link: link( params.filter, Math.min( data.page + 1, data.pages - 1 ) ),
				disabled: data.page >= data.pages - 1
			}
		];

		data.canPurge = params.filter === 'archived';
		data.isEmpty = !data.experiments.length;

		return data;
	}

	function addEditInputs( params ) {
		params.hypx_name = $( '#hyp-input-name' ).val();
		params.hypx_holdback = ( 100 - $( '#hyp-input-traffic' ).val() ) * 100;
		params.hypx_target = $( '#hyp-input-target option:selected' ).val();

		return params;
	}

	function labelify( str ) {
		str = str.replace( /_/g, ' ' );
		return str.charAt( 0 ).toUpperCase() + str.slice( 1 );
	}

	function render( template, params ) {
		app.$.empty().append( WH.Hypothesis.template.render( template, params ) );
	}

	function error( message ) {
		return function( error ) {
			app.$.append( '<h2><span class="hyp-error">Error: ' + message + '</span></h2>' );
			app.$.append( '<pre>' + error + '</pre>' );
		}
	}

	function link( selector, location ) {
		app.$.find( selector ).on( 'click', function ( event ) {
			app.go( typeof location === 'function' ? location.call( this ) : location );
			event.preventDefault();
		} );
	}

	function action( selector, callback ) {
		app.$.find( selector ).on( 'click', function ( event ) {
			var $button = $( this );
			if ( !app.locked && !$button.hasClass( 'hyp-disabled' ) ) {
				app.locked = true;
				$button.addClass( 'hyp-disabled' );
				app.$.prepend( '<div class="hyp-lock"><div class="hyp-wait"></div></div>' );
				$.when( callback.call( this ) ).done( function () {
					app.$.find( '.hyp-lock' ).remove();
					$button.removeClass( 'hyp-disabled' );
					app.locked = false;
				} );
			}
			event.preventDefault();
		} );
	}

} );
