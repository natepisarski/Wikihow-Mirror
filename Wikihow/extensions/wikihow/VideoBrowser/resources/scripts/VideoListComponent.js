/*global WH, mw*/
WH.VideoBrowser.VideoListComponent = WH.Render.createComponent( {
	create: function () {
		this.state = {
			videosPerPage: this.props.videosPerPage || 6,
			page: 1
		};
		this.videos = [];
	},
	onAttach: function () {
		this.videos = WH.VideoBrowser.catalog.videos()
			.filter( {
				categories: { 'regex': new RegExp( '\\b' + this.props.category.title + '\\b' ) }
			} )
			.order( 'watched' )
			.select( 'id' )
			.map( function ( id ) {
				return new WH.VideoBrowser.VideoComponent( { id: id, link: true } );
			} );
	},
	render: function () {
		var i, len, video,
			state = this.state,
			props = this.props,
			more = state.page < this.getPageCount() - 1,
			videos = this.videos.slice( 0, state.page * state.videosPerPage );
		return [ 'div.videoBrowser-videoList.section',
			[ 'h2.videoBrowser-videoList-title',
				[ 'span.mw-headline', props.category.title ],
				[ 'span.videoBrowser-videoList-subtitle.editsection',
					'watched ' + props.category.watched + ' of ' + props.category.size
				],
			],
			[ 'div.section_text',
				[ 'div.videoBrowser-videoList-videos' ].concat( videos ),
				this.renderMoreLink()
			]
		];
	},
	renderMoreLink: function () {
		return this.state.page + 1 <= this.getPageCount() ?
			[ 'p.videoBrowser-videoList-more',
				[ 'span',
					{ onclick: 'onMoreClick' },
					mw.msg( 'videobrowser-show-more' )
				]
			] :
			undefined;
	},
	getPageCount: function () {
		var state = this.state,
			count = this.videos.length;
		return Math.ceil( count / state.videosPerPage );
	},
	setPage: function ( page ) {
		var count = this.getPageCount();
		page = Math.max( Math.min( page, count ), 0 );
		this.change( { page: page } );
	},
	onMoreClick: function ( e ) {
		this.setPage( this.state.page + 1 );

		// Track more click
		// Trevor - 5/30/19 - Disabling tracking for now since Machinfy is being slow
		// WH.maEvent( 'videoBrowser_index_more', {
		// 	origin: location.hostname,
		// 	categoryTitle: this.props.category.title,
		// 	categoryPage: this.state.page,
		// 	userIsMobile: this.isMobile
		// } );

		return false;
	}
} );
