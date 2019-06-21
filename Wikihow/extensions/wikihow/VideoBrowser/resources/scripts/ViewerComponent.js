/*global WH, mw*/
WH.VideoBrowser.ViewerComponent = WH.Render.createComponent( {
	create: function () {
		this.state = {
			slug: null,
			summary: null,
			summaryError: false,
			schema: null,
			bumper: false,
			playing: false,
			nextVideoId: null,
			currentVideoId: null,
			countdown: 0,
			relatedVideos: null,
			relatedArticles: null
		};
		this.title = new WH.VideoBrowser.TitleComponent();
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

		// Temporary hack
		this.youtubeIds = {
			5775245: 'pPYmcaPwwVU',
			8570867: 'ogKBevxvdr8',
			8326974: 'JQ0K--cv5Y4',
			13268: 'b-F7OtrLaoc',
			66809: '0c9PExGc9WE',
			663332: 'I4WHOEsq1Ko',
			41306: '469JJk1Wf1c',
			1800408: 'CODnVX7VAZ8',
			155200: 'emvdufe6t-8',
			316096: 'jVFV_1pOqDY',
			3399: 'Mcx1Q4uIjkY',
			3630441: 'paAKkQUYqjs',
			149992: 'iiyMR0LhipA',
			3743929: 'P7fWu3yEw-Y',
			154200: '6jHI-95fSTY',
			14904: 'sSV6ZwxVR1U',
			2344358: 'VPNjnNbzZxA',
			563462: '2StTVY6y9xg',
			138597: 'Rg1XZfF-ybc',
			3823: 'zSv-RzesjYo',
			4420660: 'Tirwu-YE_3I',
			19549: 'n9zwdJh7LMA',
			8002860: 'UvGe6A04bJc',
			482185: 'YjHVnlOEFc8',
			375502: 'R0qkRne1_jQ',
			134856: 'R-QBlNYpl6c',
			2448869: '23yM30uH-Wo',
			9426953: 'hhfkNrFxkcM',
			1412189: 'maCNg8DJ0s4',
			9431347: 'kYZJKvZaCG4',
			842696: 'U7Poo8AAIas'
		};
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

			// Use the existing summary text
			var summary = document.getElementsByClassName( 'videoBrowser-viewer-summary' )[0];
			var summaryHtml = summary ? summary.innerHTML : null;

			this.change( { summary: summaryHtml, relatedVideos: null, relatedArticles: null } );
			this.title.change( { slug: state.slug } );

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

			// Schema
			this.getSchema( function ( schema ) {
				viewer.change( {
					schema: JSON.stringify( schema )
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
					} )
					.slice( 0, isMobile ? 4 : 9 );
			} );

			// Related Articles
			this.getRelatedArticles( function ( relatedArticles ) {
				viewer.change( { relatedArticles: relatedArticles } );
				viewer.relatedArticles = relatedArticles
					.map( function ( relatedArticle ) {
						return new WH.VideoBrowser.ArticleComponent( relatedArticle );
					} )
					.slice( 0, isMobile ? 4 : 9 );
			} );

			// Page title
			document.title = mw.msg( 'videobrowser-viewer-title', video.title );

			// Track view
			this.trackView();
		}
		var autoPlayNextUp = WH.VideoBrowser.preferences.autoPlayNextUp;
		if ( video ) {
			return [ 'div.videoBrowser-viewer' + ( state.playing ? '.videoBrowser-viewer-playing' : '' ),
				video.id in this.youtubeIds ?
					[ 'div.videoBrowser-viewer-player',
						[ 'iframe',
							{
								width: '100%',
								height: '100%',
								src: 'https://www.youtube.com/embed/' + this.youtubeIds[video.id] + '?rel=0&modestbranding=1',
								frameborder: 0,
								allow: 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture',
								allowfullscreen: true
							}
						]
					] :
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
					[ 'p.videoBrowser-viewer-context', mw.msg( 'videobrowser-context' ) ],
					this.title,
					[ 'div.videoBrowser-viewer-description',
						// [ 'p.videoBrowser-viewer-plays' ].concat(
						// 	mw.msg( 'videobrowser-plays', video.plays )
						// ),
						[ 'div.videoBrowser-viewer-summary' ].concat(
							!state.summaryError ?
								WH.Render.parseHTML(
									state.summary ||
									mw.msg( 'videobrowser-loading' )
								) :
								''
						),
						[ 'p.videoBrowser-viewer-more',
							[ 'a',
								{ href: video.article, onclick: 'onFullArticleClick' },
								mw.msg( 'videobrowser-read-more' )
							]
						]
					],
					[ 'script', { type: 'application/ld+json' }, state.schema || '' ],
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
		this.api.get( { action: 'summary_section', ss_page: this.video.id } )
			.then( function ( data ) {
				callback( data.query.summary_section.content );
			}, function () {
				callback( null );
			} );
	},
	getSchema: function ( callback ) {
		var params = { action: 'schema_markup' };
		if ( this.video.id in this.youtubeIds ) {
			params.sm_type = 'video/youtube';
			params.sm_video_youtube_id = this.youtubeIds[this.video.id];
		} else {
			params.sm_type = 'video/wikihow';
			params.sm_video_wikihow_id = this.video.id;
		}
		this.api.get( params )
			.then( function ( data ) {
				callback( data.query.schema_markup.schema );
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
				.get()
		);
	},
	logAction: function ( action ) {
		var xmlHttp = new XMLHttpRequest();
		var url = '/x/event' +
			'?action=' + encodeURIComponent( action ) +
			'&page=' + encodeURIComponent( this.video.id );
		xmlHttp.open( 'GET', url, true );
		xmlHttp.send( null );
	},
	trackProgress: function ( duration, progress ) {
		// Trevor - 5/30/19 - Disabling tracking for now since Machinfy is being slow
		// var prev = Math.floor( this.progress * 4 );
		// var next = Math.floor( progress * 4 );
		// if ( prev < next ) {
		// 	// Track played %
		// 	WH.maEvent( 'videoBrowser_progress', {
		// 		origin: location.hostname,
		// 		videoId: this.video.id,
		// 		videoTitle: this.video.title,
		// 		userVideoTime: ( duration / 4 ) * next,
		// 		userVideoDuration: duration,
		// 		userVideoProgress: next * 0.25,
		// 		userIsSeeking: this.seeking,
		// 		userIsMobile: !mw.mobileFrontend,
		// 		userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
		// 		userHasInteracted: WH.VideoBrowser.hasUserInteracted,
		// 		userHasMuted: WH.VideoBrowser.hasUserMuted
		// 	} );
		// }
	},
	trackView: function () {
		this.logAction( 'svideoview' );
		// Trevor - 5/30/19 - Disabling tracking for now since Machinfy is being slow
		// WH.VideoBrowser.sessionStreak++;
		// WH.maEvent( 'videoBrowser_view', {
		// 	origin: location.hostname,
		// 	videoId: this.video.id,
		// 	videoTitle: this.video.title,
		// 	userIsMobile: !mw.mobileFrontend,
		// 	userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
		// 	userHasInteracted: WH.VideoBrowser.hasUserInteracted,
		// 	userHasMuted: WH.VideoBrowser.hasUserMuted,
		// 	userSessionStreak: WH.VideoBrowser.sessionStreak
		// } );
	},
	trackPlay: function () {
		this.logAction( 'svideoplay' );
	},
	trackMute: function () {
		// Trevor - 5/30/19 - Disabling tracking for now since Machinfy is being slow
		// WH.maEvent( 'videoBrowser_mute', {
		// 	origin: location.hostname,
		// 	videoId: this.video.id,
		// 	videoTitle: this.video.title,
		// 	userIsMobile: !mw.mobileFrontend,
		// 	userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
		// 	userHasInteracted: WH.VideoBrowser.hasUserInteracted,
		// 	userHasMuted: WH.VideoBrowser.hasUserMuted
		// } );
	},
	onFullArticleClick: function ( event ) {
		// Trevor - 5/30/19 - Disabling tracking for now since Machinfy is being slow
		// event.preventDefault();
		// var video = this.video;
		// WH.maEvent( 'videoBrowser_full_article_click', {
		// 	origin: location.hostname,
		// 	videoId: video.id,
		// 	videoTitle: video.title,
		// 	userIsMobile: !mw.mobileFrontend,
		// 	userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
		// 	userHasInteracted: WH.VideoBrowser.hasUserInteracted,
		// 	userHasMuted: WH.VideoBrowser.hasUserMuted,
		// 	userSessionStreak: WH.VideoBrowser.sessionStreak
		// }, function () {
		// 	window.location = video.article;
		// } );
	}
} );
