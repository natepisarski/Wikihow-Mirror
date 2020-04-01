/*global WH, $, mw*/

WH.CopyArticleImages.MainComponent = WH.Render.createComponent( {
	create: function () {
		this.form = new WH.CopyArticleImages.FormComponent( {
			onSubmit: this.onFormSubmit.bind( this )
		} );
		this.batches = new WH.CopyArticleImages.BatchListComponent();
		this.list = new WH.CopyArticleImages.ListComponent( {
			onLoad: this.onListLoad.bind( this )
		} );
		this.list.load();
	},
	render: function () {
		return [ 'div.cai-main',
			this.form,
			this.batches,
			this.list
		];
	},
	onFormSubmit: function ( promise ) {
		promise.then( function ( items ) {
			this.list.addItems( items );
			this.batches.addBatch( items );
		}.bind( this ) );
	},
	onListLoad: function ( promise ) {
		//
	}
} );
