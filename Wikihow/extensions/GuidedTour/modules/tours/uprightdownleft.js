// HTML and CSS pokey demonstration.
//
// Note well that you should run this tour on an editable page with at least
// one section.
( function ( gt ) {

	gt.defineTour( {
		name: 'uprightdownleft',
		steps: [ {
			title: 'Up',
			description: '',
			attachTo: '#ca-edit',
			position: 'bottom',
			buttons: [ {
				action: 'next'
			} ]
		}, {
			title: 'Right',
			description: '',
			attachTo: '#ca-edit',
			position: 'left',
			buttons: [ {
				action: 'next'
			} ]
		}, {
			title: 'Down',
			description: '',
			attachTo: '.mw-editsection',
			position: 'top',
			buttons: [ {
				action: 'next'
			} ]
		}, {
			title: 'Left',
			description: '',
			attachTo: '#n-mainpage-description',
			position: 'right'
		} ]
	} );

}( mw.guidedTour ) );
