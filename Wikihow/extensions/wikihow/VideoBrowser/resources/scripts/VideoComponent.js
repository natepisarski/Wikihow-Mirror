/*global WH, mw*/
WH.VideoBrowser.VideoComponent = WH.Render.createComponent( {
	create: function () {
		this.video = null;
	},
	render: function () {
		var video = this.video = WH.VideoBrowser.catalog.videos().filter( { id: this.props.id } ).first();
		return [ this.props.link ? 'a.videoBrowser-video' : 'div.videoBrowser-video',
			{ key: video.id, href: video.pathname, onclick: 'onClick' },
			[ 'div.videoBrowser-video-content',
				[ 'div.videoBrowser-video-image',
					video.clip && !this.props.static ?
						[ 'video',
							{
								src: video.clip,
								poster: video.poster || WH.VideoBrowser.missingPosterUrl,
								muted: '',
								loop: '',
								'webkit-playsinline': '',
								playsinline: '',
								oncanplay: 'this.muted=true;this.play()'
							}
						]:
						[ 'img', { src: video.poster || WH.VideoBrowser.missingPosterUrl } ]
				],
				video.watched ? [ 'div.videoBrowser-video-watched', '✔' ] : undefined,
				[ 'p.videoBrowser-video-title',
					[ 'p', mw.msg( 'videobrowser-how-to', '' ) ],
					video.title
				],
				this.props.icon ?
					[ 'div.videoBrowser-video-icon.videoBrowser-video-icon-' + this.props.icon] : undefined
			]
		];
	},
	onClick: function ( event ) {
		// Trevor - 5/30/19 - Disabling tracking for now since Machinfy is being slow
		// if ( this.props.link && this.props.related ) {
		// 	event.preventDefault();
		// 	var video = this.video;
		// 	WH.maEvent( 'videoBrowser_related_video_click', {
		// 		origin: location.hostname,
		// 		videoId: video.id,
		// 		videoTitle: video.title,
		// 		userIsMobile: !mw.mobileFrontend,
		// 		userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
		// 		userHasInteracted: WH.VideoBrowser.hasUserInteracted,
		// 		userHasMuted: WH.VideoBrowser.hasUserMuted,
		// 		userSessionStreak: WH.VideoBrowser.sessionStreak
		// 	}, function () {
		// 		if ( window.location.pathname !== video.pathname ) {
		// 			window.location = video.pathname;
		// 		}
		// 	} );
		// }
	}
} );
