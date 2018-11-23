/*global WH, mw*/
WH.VideoBrowser.BrowserComponent = WH.Render.createComponent( {
	create: function () {
		this.views = {
			index: new WH.VideoBrowser.IndexComponent(),
			viewer: new WH.VideoBrowser.ViewerComponent()
		};
		this.state = {
			view: null
		};
	},
	render: function () {
		return [ 'div.videoBrowser-browser',
			this.state.view ? this.views[this.state.view] : undefined
		];
	},
	setView: function ( view, changes ) {
		if ( view in this.views ) {
			this.views[view].change( changes );
			if ( this.views[view].actionBar ) {
				this.views[view].actionBar.change( changes );
			}
			this.change( { view: view } );
		}
	}
} );
