/*global WH, mw*/
WH.VideoBrowser.BrowserTitleComponent = WH.Render.createComponent( {
	create: function () {
		this.state = {
			slug: null
		};
	},
	render: function () {
		var video,
			text = mw.msg( 'videobrowser' ),
			state = this.state;
		if ( state.slug ) {
			video = WH.VideoBrowser.catalog.videos().filter( { slug: state.slug } ).first();
			if ( video ) {
				text = mw.msg( 'videobrowser-how-to', video.title );
			}
		}
		return [ 'span.videoBrowser-title', [ 'span.videoBrowser-title-text', text ] ];
	}
} );
