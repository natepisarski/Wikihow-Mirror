/*global WH, mw*/
WH.VideoBrowser.ViewerComponent = WH.Render.createComponent( {
	create: function () {
		this.state = {
			slug: null,
			summary: null,
			summaryError: false,
			bumper: false,
			playing: false,
			nextVideoId: null,
			currentVideoId: null,
			countdown: 0,
			relatedVideos: null,
			relatedArticles: null
		};
		this.actionBar = new WH.VideoBrowser.ActionBarComponent();
		this.nextVideo = new WH.VideoBrowser.VideoComponent();
		this.currentVideo = new WH.VideoBrowser.VideoComponent();
		this.relatedVideosSlider = new WH.VideoBrowser.SliderComponent();
		this.relatedArticlesSlider = new WH.VideoBrowser.SliderComponent();
		this.video = null;
		this.relatedVideos = null;
		this.relatedArticles = null;
		this.tick = this.tick.bind( this );
		this.api = new mw.Api();

		// State
		this.attached = false;
		this.element = null;
		this.seeking = false;
		this.muting = false;
		this.touched = false;
		this.progress = 0;
		this.played = false;
	},
	render: function () {
		var viewer = this;
		var isMobile = !!mw.mobileFrontend;
		var state = this.state;
		var video = this.video;
		if ( !video || video.slug !== state.slug ) {
			// Reset rendering state
			this.element = null;
			this.seeking = false;
			this.muting = false;
			this.touched = false;
			this.progress = 0;
			this.played = false;
			this.change( { summary: null, relatedVideos: null, relatedArticles: null } );

			this.cancelCountdown();
			video = this.video = WH.VideoBrowser.catalog.videos()
				.filter( { slug: state.slug } ).first();

			// Summary
			this.getSummary( function ( html ) {
				var element = document.createElement( 'div' );
				element.innerHTML = html ? html : '';
				viewer.change( {
					summary: html,
					summaryError: html === null,
					summaryText: element.innerText.replace( /^\s\s*/, '' ).replace( /\s\s*$/, '' )
				} );
			} );

			// Related Videos
			this.getRelatedVideos( function ( relatedVideos ) {
				viewer.change( { relatedVideos: relatedVideos } );
				viewer.relatedVideos = relatedVideos
					.map( function ( relatedVideo ) {
						return new WH.VideoBrowser.VideoComponent( {
							id: relatedVideo.id,
							link: true,
							static: true,
							icon: 'play',
							related: true
						} );
					} );
			} );

			// Related Articles
			this.getRelatedArticles( function ( relatedArticles ) {
				viewer.change( { relatedArticles: relatedArticles } );
				viewer.relatedArticles = relatedArticles
					.map( function ( relatedArticle ) {
						return new WH.VideoBrowser.ArticleComponent( relatedArticle );
					} );
			} );

			// Page title
			document.title = mw.msg(
				'videobrowser-viewer-title', mw.msg( 'videobrowser-how-to', video.title )
			);

			// Track view
			this.trackView();
		}
		var autoPlayNextUp = WH.VideoBrowser.preferences.autoPlayNextUp;
		if ( video ) {
			return [ 'div.videoBrowser-viewer' + ( state.playing ? '.videoBrowser-viewer-playing' : '' ),
				[ 'div.videoBrowser-viewer-player',
					[ 'video',
						{
							key: video.id,
							width: 728,
							height: 410,
							controls: '',
							controlsList: 'nodownload',
							poster: video.poster || WH.VideoBrowser.missingPosterUrl,
							playsinline: '',
							autoplay: '',
							onended: 'onEnded',
							onplay: 'onPlay',
							onpause: 'onPause',
							oncanplay: 'onCanPlay',
							onvolumechange: 'onVolumeChange',
							onseeking: 'onSeeking',
							ontimeupdate: 'onTimeUpdate'
						},
						[ 'source', { src: video.video, type: 'video/mp4' } ],
						function ( element ) {
							viewer.element = element;
						}
					],
					state.bumper && !isMobile ? [ 'div.videoBrowser-viewer-bumper',
						state.currentVideoId ? [ 'div.videoBrowser-viewer-bumperOption',
							[ 'p.videoBrowser-viewer-bumperTitle', mw.msg( 'videobrowser-replay' ) ],
							[ 'div.videoBrowser-viewer-bumperVideo',
								{ onclick: 'onReplayClick' },
								this.currentVideo.using( { id: state.currentVideoId, icon: 'replay' } )
							],
						] : undefined,
						state.nextVideoId ? [ 'div.videoBrowser-viewer-bumperOption',
							[ 'p.videoBrowser-viewer-bumperTitle', mw.msg( 'videobrowser-next' ) ],
							[ 'div.videoBrowser-viewer-bumperVideo',
								{ onclick: 'onNextClick' },
								this.nextVideo.using( { id: state.nextVideoId, icon: 'next' } ),
							],
							[ 'p.videoBrowser-viewer-bumperClock',
								mw.msg( 'videobrowser-countdown', state.countdown )
							],
							[ 'div.videoBrowser-viewer-bumperCancel.button',
								{ onclick: 'onCancelClick' }, mw.msg( 'videobrowser-cancel' )
							]
						] : undefined,
					] : undefined,
					[ 'div.videoBrowser-viewer-playButton', { onclick: 'onPlayButtonClick' } ]
				],
				[ 'div.section_text',
					this.actionBar,
					[ 'h2.videoBrowser-viewer-title',
						mw.msg( 'videobrowser-how-to', video.title )
					],
					[ 'div.videoBrowser-viewer-description',
						[ 'p.videoBrowser-viewer-plays' ].concat(
							mw.msg( 'videobrowser-plays', video.plays )
						),
						[ 'p.videoBrowser-viewer-summary' ].concat(
							!state.summaryError ?
								WH.Render.parseHTML( state.summary || mw.msg( 'videobrowser-loading' ) ) :
								''
						),
						[ 'p.videoBrowser-viewer-more',
							[ 'a',
								{ href: video.article, onclick: 'onFullArticleClick' },
								mw.msg( 'videobrowser-read-more' )
							]
						]
					],
					[ 'script', { type: 'application/ld+json' }, JSON.stringify( {
						'@context': 'http://schema.org',
						'@type': 'VideoObject',
						'name': mw.msg( 'videobrowser-how-to', video.title ),
						'description': state.summaryText || undefined,
						'thumbnailUrl': [ video['poster@1:1'], video['poster@4:3'], video.poster ],
						'uploadDate': video.updated,
						//'duration': 'PT1M33S', // TODO: Actual duration
						'contentUrl': String( window.location ),
						'embedUrl': video.video,
						'interactionCount': video.plays
					} ) ],
					[ 'div.videoBrowser-viewer-related',
						[ 'h2.videoBrowser-viewer-related-title',
							[ 'span.mw-headline', 'Related Videos' ],
							!isMobile ? [ 'label',
								mw.msg( 'videobrowser-auto-play' ),
								[ 'input', {
									type: 'checkbox',
									checked: autoPlayNextUp || undefined,
									onchange: 'onAutoPlayChanged'
								} ]
							] : undefined
						],
						[ 'div.videoBrowser-viewer-related-content',
							state.relatedVideos ?
								this.relatedVideosSlider.using( { items: this.relatedVideos } ) :
								[ 'span', mw.msg( 'videobrowser-loading' ) ]
						]
					],
					[ 'div.videoBrowser-viewer-related',
						[ 'h2.videoBrowser-viewer-related-title',
							[ 'span.mw-headline', 'Related Articles' ]
						],
						[ 'div.videoBrowser-viewer-related-content',
							state.relatedArticles ?
								this.relatedArticlesSlider.using( { items: this.relatedArticles } ) :
								[ 'span', mw.msg( 'videobrowser-loading' ) ]
						]
					]
				]
			];
		}
		return [ 'div.videoBrowser-viewer', mw.msg( 'videobrowser-not-found' ) ];
	},
	onDetach: function () {
		this.attached = false;
		clearInterval( this.clock );
	},
	onAttach: function () {
		this.attached = true;
	},
	onSeeking: function () {
		this.touched = true;
		this.seeking = true;
	},
	onAutoPlayChanged: function ( event ) {
		WH.VideoBrowser.preferences.autoPlayNextUp = !!event.target.checked;
		WH.VideoBrowser.savePreferences();
	},
	onPlay: function () {
		this.seeking = false;
		this.cancelCountdown();
		WH.VideoBrowser.catalog.watchVideo( this.video );
		if ( !this.played ) {
			this.trackPlay();
		}
		this.played = true;
		this.change( { playing: true } );
	},
	onPause: function () {
		this.change( { playing: false } );
	},
	onCancelClick: function () {
		this.cancelCountdown();
		if ( this.element ) {
			var element = this.element;
		}
	},
	onCanPlay: function () {
		if ( this.element && !this.touched && this.attached ) {
			if ( !WH.VideoBrowser.hasUserInteracted || WH.VideoBrowser.hasUserMuted ) {
				this.muting = true;
				this.element.muted = true;
			}
			if ( !this.seeking ) {
				this.play();
			}
		}
	},
	play: function () {
		try {
			var promise = this.element.play();
			if ( typeof promise === 'object' && promise['catch'] ) {
				promise['catch']( function () {} );
			}
		} catch ( error ) {
			//
		}
	},
	onTimeUpdate: function () {
		if ( this.element ) {
			var currentTime = this.element.currentTime;
			var duration = this.element.duration;
			var progress = currentTime / duration;
			if ( progress > this.progress ) {
				this.trackProgress( duration, progress );
				this.progress = progress;
			}
		}
	},
	onVolumeChange: function () {
		var track = false;
		if ( this.element ) {
			// Either after auto-mute or when initially acting
			if ( !this.muting || WH.VideoBrowser.hasUserMuted == this.element.muted ) {
				WH.VideoBrowser.hasUserMuted = this.element.muted;
				// Track mute change
				this.trackMute();
			}
		}
		this.muting = false;
	},
	onReplayClick: function () {
		if ( this.element ) {
			this.cancelCountdown();
			this.element.currentTime = 0;
			this.play();
		}
	},
	onNextClick: function () {
		var video = WH.VideoBrowser.catalog.videos().filter( { id: this.state.nextVideoId } ).first();
		if ( video ) {
			WH.VideoBrowser.router.go( video.pathname );
			this.cancelCountdown();
		}
	},
	onPlayButtonClick: function () {
		if ( this.element ) {
			if ( this.element.paused ) {
				this.play();
			} else {
				this.element.pause();
			}
		}
	},
	onEnded: function () {
		var nextVideoId;
		var currentVideoId = this.video.id;

		if ( !WH.VideoBrowser.preferences.autoPlayNextUp || !!mw.mobileFrontend || this.seeking ) {
			return;
		}

		if ( this.relatedVideos ) {
			if ( this.relatedVideos.length ) {
				nextVideoId = this.relatedVideos[0].video.id;
			}
		}
		this.change( { bumper: true, currentVideoId: currentVideoId, nextVideoId: nextVideoId, countdown: 10 } );
		if ( nextVideoId ) {
			this.clock = setInterval( this.tick, 1000 );
		}
	},
	cancelCountdown: function () {
		var state = this.state;
		if ( state.bumper && state.nextVideoId ) {
			clearInterval( this.clock );
			this.change( { bumper: false, currentVideoId: null, nextVideoId: null, countdown: 0 } );
		}
	},
	tick: function () {
		var state = this.state;
		if ( state.bumper && state.nextVideoId ) {
			if ( state.countdown <= 1 ) {
				var video = WH.VideoBrowser.catalog.videos().filter( { id: state.nextVideoId } ).first();
				this.cancelCountdown();
				WH.VideoBrowser.router.go( video.pathname );
			} else {
				this.change( { countdown: state.countdown - 1 } );
			}
		}
	},
	getSummary: function ( callback ) {
		this.api.get( { action: 'parse', page: 'Summary:' + this.video.title } )
			.then( function ( data ) {
				callback( data.parse.text['*'] );
			}, function () {
				callback( null );
			} );
	},
	getRelatedArticles: function ( callback ) {
		this.api.get( { action: 'related_articles', ra_page: this.video.id } )
			.then( function ( data ) {
				callback(
					data.query &&
					data.query.related_articles &&
					data.query.related_articles.articles ?
						data.query.related_articles.articles : []
				);
			}, function () {
				callback( [] );
			} );
	},
	getRelatedVideos: function ( callback ) {
		var categories = this.video.categories.split( ',' );
		var count = 0;
		callback(
			WH.VideoBrowser.catalog
				.videos( categories.map( function ( category ) {
					return { categories: { 'regex': new RegExp( '\\b' + category + '\\b' ) } };
				} ) )
				.filter( { id: { '!is': this.video.id } } )
				.order( 'watched' )
				.limit( 9 )
		);
	},
	logAction: function ( action ) {
		var xmlHttp = new XMLHttpRequest();
		var time = Math.round( new Date().getTime() / 60000 ) * 60;
		var url = '/x/event?action=' + action + '&t=' + time;
		xmlHttp.open( 'GET', url, true );
		xmlHttp.send( null );
	},
	trackProgress: function ( duration, progress ) {
		var prev = Math.floor( this.progress * 4 );
		var next = Math.floor( progress * 4 );
		if ( prev < next ) {
			// Track played %
			WH.maEvent( 'videoBrowser_progress', {
				origin: location.hostname,
				videoId: this.video.id,
				videoTitle: this.video.title,
				userVideoTime: ( duration / 4 ) * next,
				userVideoDuration: duration,
				userVideoProgress: next * 0.25,
				userIsSeeking: this.seeking,
				userIsMobile: !mw.mobileFrontend,
				userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
				userHasInteracted: WH.VideoBrowser.hasUserInteracted,
				userHasMuted: WH.VideoBrowser.hasUserMuted
			} );
		}
	},
	trackView: function () {
		WH.VideoBrowser.sessionStreak++;
		// this.logAction( 'svideoview' );
		WH.maEvent( 'videoBrowser_view', {
			origin: location.hostname,
			videoId: this.video.id,
			videoTitle: this.video.title,
			userIsMobile: !mw.mobileFrontend,
			userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
			userHasInteracted: WH.VideoBrowser.hasUserInteracted,
			userHasMuted: WH.VideoBrowser.hasUserMuted,
			userSessionStreak: WH.VideoBrowser.sessionStreak
		} );
	},
	trackPlay: function () {
		// this.logAction( 'svideoplay' );
	},
	trackMute: function () {
		WH.maEvent( 'videoBrowser_mute', {
			origin: location.hostname,
			videoId: this.video.id,
			videoTitle: this.video.title,
			userIsMobile: !mw.mobileFrontend,
			userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
			userHasInteracted: WH.VideoBrowser.hasUserInteracted,
			userHasMuted: WH.VideoBrowser.hasUserMuted
		} );
	},
	onFullArticleClick: function ( event ) {
		event.preventDefault();
		var video = this.video;
		WH.maEvent( 'videoBrowser_full_article_click', {
			origin: location.hostname,
			videoId: video.id,
			videoTitle: video.title,
			userIsMobile: !mw.mobileFrontend,
			userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
			userHasInteracted: WH.VideoBrowser.hasUserInteracted,
			userHasMuted: WH.VideoBrowser.hasUserMuted,
			userSessionStreak: WH.VideoBrowser.sessionStreak
		}, function () {
			window.location = video.article;
		} );
	}
} );
