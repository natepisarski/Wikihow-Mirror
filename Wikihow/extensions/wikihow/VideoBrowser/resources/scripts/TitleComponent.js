/*global WH, mw*/
WH.VideoBrowser.BrowserTitleComponent = WH.Render.createComponent( {
	create: function () {
		this.state = {
			slug: null
		};
	},
	render: function () {
		var item,
			text = mw.msg( 'videobrowser' ),
			state = this.state;
		if ( state.slug ) {
			item = WH.VideoBrowser.catalog.items().filter( { slug: state.slug } ).first();
			if ( item ) {
				text = 'How to ' + item.title;
			}
		}
		return [ 'span.videoBrowser-title' + ( item ? '.videoBrowser-title-subpage' : '' ),
			[ 'span.videoBrowser-title-text',
				text
			],
			item ? [ 'a.videoBrowser-title-indexButton',
				{ href: WH.VideoBrowser.router.link( '/' ), title: mw.msg( 'videobrowser-back' ) }
			] : undefined,
		];
	}
} );
