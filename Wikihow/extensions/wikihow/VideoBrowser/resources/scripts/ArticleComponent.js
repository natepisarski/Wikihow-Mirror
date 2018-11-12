/*global WH, mw*/
WH.VideoBrowser.ArticleComponent = WH.Render.createComponent( {
	render: function () {
		var props = this.props;
		return [ 'a.videoBrowser-article',
			{ key: props.id, href: props.url, onclick: 'onClick' },
			[ 'div.videoBrowser-article-content',
				[ 'div.videoBrowser-article-image',
					[ 'img', { src: props.image || WH.VideoBrowser.missingPosterUrl } ]
				],
				[ 'p.videoBrowser-article-title',
					[ 'p', mw.msg( 'videobrowser-how-to', '' ) ],
					props.title
				],
			]
		];
	},
	onClick: function ( event ) {
		if ( this.props.url ) {
			event.preventDefault();
			var article = this;
			WH.maEvent( 'videoBrowser_related_article_click', {
				origin: location.hostname,
				articleId: article.props.id,
				articleTitle: article.props.title,
				userIsMobile: !mw.mobileFrontend,
				userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
				userHasInteracted: WH.VideoBrowser.hasUserInteracted,
				userHasMuted: WH.VideoBrowser.hasUserMuted,
				userSessionStreak: WH.VideoBrowser.sessionStreak
			}, function () {
				window.location = article.props.url;
			} );
		}
	}
} );
