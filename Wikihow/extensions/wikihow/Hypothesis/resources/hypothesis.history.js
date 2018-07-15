$( function () {
	var api = new mw.Api();

	function render( hypxs, hypts ) {
		var experiments = hypxs[0].query.hypxs.experiments,
			test = hypts[0].query.hypts.test,
			options = [];

		if ( test ) {
			var experiment = experiments.reduce( function ( acc, cur ) {
				return cur.hypx_id == test.hypt_experiment ? cur : acc;
			} );
			options.push( {
				label: 'Experiment: ' + experiment.hypx_name,
				value: experiment.hypx_id,
				selected: true
			} ); 
			options.push( {
				label: '─────────────────',
				disabled: true
			} );
			options.push( {
				label: 'Remove from experiment',
				value: 'remove'
			} );
		} else {
			options.push( {
				label: 'Add to an experiment',
				selected: true,
				disabled: true
			} );
			experiments.forEach( function ( experiment ) {
				options.push( {
					label: experiment.hypx_name,
					value: experiment.hypx_id
				} ); 
			} )
		}

		// Remove if already rendered
		$( '.hyp-experiment select' ).remove();

		// Add to page
		$( '.historysubmit:first' )
			.after( WH.Hypothesis.template.render( 'history', { options: options } ) );

		// Annotate rows
		$( '#pagehistory li' ).removeClass( 'hyp-rev-a hyp-rev-b' );
		if ( test ) {
			$( 'input[name=oldid][value=' + test.hypt_rev_a + ']' )
				.closest( 'li' )
					.addClass( 'hyp-rev-a' );
			$( 'input[name=oldid][value=' + test.hypt_rev_b + ']' )
				.closest( 'li' )
					.addClass( 'hyp-rev-b' );
		}

		// Events
		$( '.hyp-experiment select' ).on( 'change', function ( event ) {
			var params,
				$this = $( this ),
				$option = $this.find( 'option:selected' ),
				$diff = $( 'input[name=diff]:checked' ),
				$oldid = $( 'input[name=oldid]:checked' );

			$this.prop( 'disabled', true );
			if ( $option.val() === 'remove' ) {
				$option.text( 'Removing...' );
				params = {
					action: 'hypt',
					hypt_id: test.hypt_id,
					remove: true
				};
			} else {
				$option.text( 'Adding...' );
				params = {
					action: 'hypt',
					hypt_experiment: $option.val(),
					hypt_page: wgArticleId,
					hypt_rev_a: $oldid.val(),
					hypt_rev_b: $diff.val()
				};
			}
			api.postWithToken( 'edit', params )
				.done( load );
		} );
	}

	function load() {
		$.when( api.get( { action: 'hypxs' } ), api.get( { action: 'hypts', page: wgArticleId } ) )
			.done( render );
	}
	load();

	$( window ).on( 'pageshow', load );
} );
