/*global WH, mw*/
WH.VideoBrowser.ActionBarComponent = WH.Render.createComponent( {
	render: function () {
		var list = [], top, breadcrumbs, item, i, len;

		var video = WH.VideoBrowser.catalog.videos().filter( { slug: this.state.slug } ).first();
		if ( video ) {
			breadcrumbs = video.breadcrumbs.split( ',' );
			list = breadcrumbs.slice( 0, 1 ).reverse();
			top = breadcrumbs.slice( -1 ).pop();
			for ( i = 0, len = list.length; i < len; i++ ) {
				list[i] = {
					label: list[i],
					link: '/Video/Category:' + top.replace( / /g, '-' )
				};
			}
		} else if ( this.state.category ) {
			list = [ {
				label: this.state.category.replace( /-/g, ' ' ),
				link: '/Video/Category:' +  this.state.category
			} ];
		}

		for ( i = 0, len = list.length; i < len; i++ ) {
			item = list[i];
			list[i] = [ 'li',
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
				].concat( list )
			]
		];
	}
} );
