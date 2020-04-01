/*global WH, mw*/

WH.CopyArticleImages.FormComponent = WH.Render.createComponent( {
	create: function () {
		this.state = { submitting: null, disabled: false };
		this.pageListInput = new WH.CopyArticleImages.PageListInputComponent( {
			onChange: this.onChange.bind( this )
		} );
		this.languageSelector = new WH.CopyArticleImages.LanguageSelectorComponent( {
			onChange: this.onChange.bind( this )
		} );
		this.submitButton = new WH.CopyArticleImages.SubmitButtonComponent( {
			onSubmit: this.onSubmit.bind( this )
		} );
	},
	render: function () {
		var state = this.state;
		return [ 'fieldset.cai-form', { disabled: state.disabled },
			[ 'section',
				this.pageListInput,
			],
			[ 'section',
				this.languageSelector,
				this.submitButton
			]
		];
	},
	submit: function ( pages, langs ) {
		if ( this.state.submitting ) {
			return;
		}
		var api = new mw.Api(),
			done = this.onAddImageTransfersDone.bind( this ),
			fail = this.onAddImageTransfersFail.bind( this ),
			params = {
				action: 'addimagetransfers',
				pages: pages.join( '\n' ),
				langs: langs.join( ',' )
			};
		var submitting = api.postWithToken( 'csrf', params ).then( done, fail );
		this.change( { submitting: submitting } );
		if ( this.props.onSubmit ) {
			this.props.onSubmit( submitting );
		}
	},
	onChange: function () {
		this.submitButton.setEnabled(
			this.pageListInput.getValues().length > 0 &&
			this.languageSelector.getSelection().length > 0
		);
	},
	onSubmit: function () {
		this.change( { disabled: true } );
		this.submit( this.pageListInput.getValues(), this.languageSelector.getSelection() );
	},
	onAddImageTransfersDone: function ( data ) {
		// TODO: Handle success
		console.log( 'onAddImageTransfersDone', arguments );
		this.change( { submitting: null, disabled: false } );
		this.pageListInput.change( { value: '' } );
		this.onChange();
		return data.addimagetransfers;
	},
	onAddImageTransfersFail: function ( error ) {
		// TODO: Handle error
		console.log( 'onAddImageTransfersFail', arguments );
		this.change( { submitting: null } );
		this.change( { disabled: false } );
	}
} );
