/*global WH, mw*/
WH.VideoBrowser.ActionBarComponent = WH.Render.createComponent( {
	render: function () {
		var list, top, item, i, len, breadcrumbs = [];

		var video = WH.VideoBrowser.catalog.videos().filter( { slug: this.state.slug } ).first();
		if ( video ) {
			list = video.breadcrumbs.slice( 0, 1 ).reverse();
			top = video.breadcrumbs.slice( -1 ).pop();
			for ( i = 0, len = list.length; i < len; i++ ) {
				breadcrumbs.push( {
					label: list[i],
					link: '/Video/Category:' + top.replace( / /g, '-' )
				} );
			}
		} else if ( this.state.category ) {
			breadcrumbs = [ {
				label: this.state.category.replace( /-/g, ' ' ),
				link: '/Video/Category:' +  this.state.category
			} ];
		}

		for ( i = 0, len = breadcrumbs.length; i < len; i++ ) {
			item = breadcrumbs[i];
			breadcrumbs[i] = [ 'li',
				' » ', [ 'a', { href: item.link, title: item.label }, item.label ]
			];
		}

		return [ 'div.videoBrowser-actionBar', { role: 'navigation' },
			[ 'div#gatBreadCrumb',
				[ 'ul#breadcrumb.Breadcrumbs',
					{ 'aria-label': 'Video breadcrumbs' },
					['li.home',
						[ 'a', { href: '/Main-Page', title: 'Main Page' }, 'Home' ]
					],
					['li', ' » ',
						[ 'a', { href: '/Video', title: 'Video' }, 'Video' ]
					]
				].concat( breadcrumbs )
			]
		];
	}
} );
