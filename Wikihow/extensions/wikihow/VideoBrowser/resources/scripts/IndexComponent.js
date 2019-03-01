/*global WH, mw*/
WH.VideoBrowser.IndexComponent = WH.Render.createComponent( {
	create: function () {
		this.lists = [];
		this.title = new WH.VideoBrowser.TitleComponent();
		this.actionBar = new WH.VideoBrowser.ActionBarComponent();
		this.onViewportChange = this.onViewportChange.bind( this );
	},
	rebuild: function () {
		var categoryText;
		var categories = WH.VideoBrowser.catalog.categories();
		var isCategoryPage = !!this.state.category;
		if ( isCategoryPage ) {
			categoryText = this.state.category.replace( /-/g, ' ' );
			categories = categories.filter( { title: categoryText } );
		} else {
			categories = categories.order( 'rank desc' );
		} 
		this.lists = categories
			.get()
			.map( function ( category ) {
				return new WH.VideoBrowser.VideoListComponent( {
					category: category,
					videosPerPage: isCategoryPage ? 24 : 6
				} );
			} );
		this.category = this.state.category;
		document.title = categoryText ?
			mw.msg( 'videobrowser-categoryindex-title', categoryText ) :
			mw.msg( 'videobrowser-index-title' );
	},
	render: function () {
		if ( this.category !== this.state.category ) {
			this.rebuild();
		}
		return [ 'div.videoBrowser-index',
			this.actionBar,
			this.title,
			[ 'div' ].concat( this.lists )
		];
	},
	onAttach: function () {
		window.addEventListener( 'resize', this.onViewportChange );
		window.addEventListener( 'scroll', this.onViewportChange );
	},
	onDetach: function () {
		window.removeEventListener( 'resize', this.onViewportChange );
		window.removeEventListener( 'scroll', this.onViewportChange );
	},
	onViewportChange: function () {
		var i, len, video, bounding,
			videos = document.querySelectorAll( '.videoBrowser-video video' );
		for ( i = 0, len = videos.length; i < len; i++ ) {
			video = videos[i];
			bounding = video.getBoundingClientRect();
			if (
				bounding.bottom >= 0 &&
				bounding.right >= 0 &&
				bounding.top <= ( window.innerHeight || document.documentElement.clientHeight ) &&
				bounding.left <= ( window.innerWidth || document.documentElement.clientWidth )
			) {
				// Be extra safe for IE support
				var promise = video.play();
				if ( promise && typeof promise['catch'] === 'function' ) {
					promise['catch']( function () {} );
				}
			} else {
				video.pause();
			}
		}
	}
} );
