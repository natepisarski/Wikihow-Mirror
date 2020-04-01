/*global WH, mw*/

WH.CopyArticleImages.PageListInputComponent = WH.Render.createComponent( {
	create: function () {
		this.state = { value: '' };
		this.link = document.createElement( 'a' );
	},
	render: function () {
		var state = this.state;
		return [ 'div.cai-pageListInput',
			[ 'p', mw.message( 'cai-pagelistinput-instructions' ).text() ],
			[ 'textarea', {
				onchange: 'onTextareaChange',
				oninput: 'onTextareaChange',
				value: new String( state.value ),
				placeholder: mw.message( 'cai-pagelistinput-placeholder' ).text()
			} ]
		];
	},
	onTextareaChange: function ( e ) {
		// Force HTTPS
		var value = e.target.value.split( '\n' ).map( function ( line ) {
			if ( line.indexOf( 'http' ) === 0 ) {
				return decodeURI( line.replace( /^http:\/\//, 'https://' ) );
			}
			return line;
		} ).join( '\n' );
		// var value = e.target.value.replace( /^http:\/\//gm, 'https://' );
		this.change( { value: value } );
		if ( this.props.onChange ) {
			this.props.onChange();
		}
	},
	getValues: function() {
		return this.state.value !== '' ? this.state.value.split( '\n' ) : [];
	}
} );
