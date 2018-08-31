/*global WH, mw*/
WH.VideoBrowser.ItemComponent = WH.Render.createComponent( {
	create: function () {
		this.item = null;
	},
	render: function () {
		var item = this.item = WH.VideoBrowser.catalog.items().filter( { id: this.props.id } ).first();
		return [ this.props.link ? 'a.videoBrowser-item' : 'div.videoBrowser-item',
			{ key: item.id, href: item.pathname },
			[ 'div.videoBrowser-item-content',
				[ 'div.videoBrowser-item-image',
					item.clip && !this.props.static ?
						[ 'video',
							{
								src: item.clip,
								poster: item.poster || WH.VideoBrowser.missingPosterUrl,
								muted: '',
								loop: '',
								'webkit-playsinline': '',
								playsinline: '',
								oncanplay: 'this.muted=true;this.play()'
							}
						]:
						[ 'img', { src: item.poster || WH.VideoBrowser.missingPosterUrl } ]
				],
				item.watched ? [ 'div.videoBrowser-item-watched', '✔' ] : undefined,
				[ 'p.videoBrowser-item-title',
					[ 'p', mw.msg( 'videobrowser-how-to', '' ) ],
					item.title
				],
				this.props.icon ?
					[ 'div.videoBrowser-item-icon.videoBrowser-item-icon-' + this.props.icon] : undefined
			]
		];
	}
} );
