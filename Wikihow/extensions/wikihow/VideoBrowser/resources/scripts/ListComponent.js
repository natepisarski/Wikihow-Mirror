/*global WH, mw*/
WH.VideoBrowser.ListComponent = WH.Render.createComponent( {
	create: function () {
		this.state = {
			itemsPerPage: 6,
			page: 1
		};
		this.items = [];
	},
	onAttach: function () {
		this.items = WH.VideoBrowser.catalog.items()
			.filter( {
				categories: { 'regex': new RegExp( '\\b' + this.props.category.title + '\\b' ) }
			} )
			.order( 'watched' )
			.select( 'id' )
			.map( function ( id ) {
				return new WH.VideoBrowser.ItemComponent( { id: id, link: true } );
			} );
	},
	render: function () {
		var i, len, item,
			state = this.state,
			props = this.props,
			more = state.page < this.getPageCount() - 1,
			items = this.items.slice( 0, state.page * state.itemsPerPage );
		return [ 'div.videoBrowser-list.section',
			[ 'h2.videoBrowser-list-title',
				[ 'span.mw-headline', props.category.title ],
				[ 'span.videoBrowser-list-subtitle.editsection',
					'watched ' + props.category.watched + ' of ' + props.category.size
				],
			],
			[ 'div.section_text',
				[ 'div.videoBrowser-list-items' ].concat( items ),
				this.renderMoreLink()
			]
		];
	},
	renderMoreLink: function () {
		return this.state.page + 1 <= this.getPageCount() ?
			[ 'p.videoBrowser-list-more',
				[ 'a',
					{ href: WH.VideoBrowser.router.link( '/' ), onclick: 'onMoreClick' },
					mw.msg( 'videobrowser-show-more' )
				]
			] :
			undefined;
	},
	getPageCount: function () {
		var state = this.state,
			count = this.items.length;
		return Math.ceil( count / state.itemsPerPage );
	},
	setPage: function ( page ) {
		var count = this.getPageCount();
		page = Math.max( Math.min( page, count ), 0 );
		this.change( { page: page } );
	},
	onMoreClick: function ( e ) {
		this.setPage( this.state.page + 1 );

		// Track more click
		WH.maEvent( 'videoBrowser_index_more', {
			categoryTitle: this.props.category.title,
			categoryPage: this.state.page,
			userIsMobile: this.isMobile
		}, false );

		return false;
	}
} );
