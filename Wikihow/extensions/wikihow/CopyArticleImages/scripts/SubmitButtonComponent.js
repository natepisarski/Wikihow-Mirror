/*global WH, mw*/

WH.CopyArticleImages.SubmitButtonComponent = WH.Render.createComponent( {
	create: function () {
		this.state = { enabled: false };
	},
	render: function () {
		return [ 'div.cai-submitButton',
			[ 'button.button.primary',
				{
					onclick: 'onButtonClick',
					disabled: !this.state.enabled
				},
				mw.message( 'cai-submitbutton-label' ).text()
			],
			[ 'p', mw.message( 'cai-submitbutton-instructions' ).text() ]
		];
	},
	setEnabled: function ( value ) {
		this.change( { enabled: value } );
	},
	onButtonClick: function ( e ) {
		if ( this.props.onSubmit ) {
			this.props.onSubmit();
		}
	}
} );
