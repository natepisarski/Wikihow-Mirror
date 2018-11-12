/*global WH, mw*/
WH.VideoBrowser.ActionBarComponent = WH.Render.createComponent( {
	render: function () {
		var video = WH.VideoBrowser.catalog.videos().filter( { slug: this.state.slug } ).first();
		return [ 'div.videoBrowser-actionBar', { role: 'navigation' },
			[ 'div#gatBreadCrumb',
				[ 'ul#breadcrumb.Breadcrumbs',
					{ 'aria-label': 'Video breadcrumbs' },
					['li.home',
						[ 'a', { href: '/Main-Page', title: 'Main Page' }, 'Home' ]
					],
					['li', ' » ',
						[ 'a', { href: '/Video', title: 'Video' }, 'Video' ]
					],
					video ? ['li', ' » ',
						[ 'a', 
							{
								href: String( window.location ),
								title: mw.msg( 'videobrowser-how-to', video.title )
							},
							mw.msg( 'videobrowser-how-to', video.title )
						]
					] : undefined
				]
			]
		];
	}
} );
