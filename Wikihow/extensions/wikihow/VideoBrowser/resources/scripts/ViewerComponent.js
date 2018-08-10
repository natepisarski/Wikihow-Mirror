/*global WH, mw*/
WH.VideoBrowser.ViewerComponent = WH.Render.createComponent( {
	create: function () {
		this.state = {
			slug: null,
			bumper: false,
			next: null,
			current: null,
			countdown: 0
		};
		this.next = new WH.VideoBrowser.ItemComponent();
		this.current = new WH.VideoBrowser.ItemComponent();
		this.element = null;
		this.muting = false;
		this.item = null;
		this.queue = [];
		this.tick = this.tick.bind( this );
		this.isMobile = !!mw.mobileFrontend;
		this.touched = false;
		this.seeking = false;
		this.finished = false;
	},
	render: function () {
		var state = this.state;
		var item = this.item;
		if ( !item || item.slug !== state.slug ) {
			this.touched = false;
			this.finished = false;
			WH.VideoBrowser.sessionStreak++;
			this.cancelCountdown();
			item = this.item = WH.VideoBrowser.catalog.items()
				.filter( { slug: state.slug } ).first();
			var categories = item.categories.split( ',' );
			this.queue = WH.VideoBrowser.catalog.items( categories.map( function ( category ) {
				return { categories: { 'regex': new RegExp( '\\b' + category + '\\b' ) } };
			} ) )
				.filter( { id: { '!is': item.id } } )
				.order( 'watched' )
				.limit( 3 )
				.select( 'id' )
				.map( function ( id ) {
					return new WH.VideoBrowser.ItemComponent( { id: id, link: true } );
				} );
			// Track view
			WH.maEvent( 'videoBrowser_view', {
				videoId: this.item.id,
				videoTitle: this.item.title,
				userIsMobile: this.isMobile,
				userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
				userHasInteracted: WH.VideoBrowser.hasUserInteracted,
				userHasMuted: WH.VideoBrowser.hasUserMuted,
				userSessionStreak: WH.VideoBrowser.sessionStreak
			}, false );
		}
		var autoPlayNextUp = WH.VideoBrowser.preferences.autoPlayNextUp;
		if ( item ) {
			var videoAttributes = {
				key: item.id,
				width: 728,
				height: 410,
				controls: '',
				controlsList: 'nodownload',
				poster: item.poster || WH.VideoBrowser.missingPosterUrl,
				onended: 'onEnded',
				onplay: 'onPlay',
				oncanplay: 'onCanPlay',
				onvolumechange: 'onVolumeChange',
				onseeking: 'onSeeking'
			};
			var player = this;
			return [ 'div.videoBrowser-viewer' + ( !this.isMobile ? '.section_text' : '' ),
				[ 'div' + ( this.isMobile ? '.section_text' : '' ),
					[ 'p.videoBrowser-viewer-description', item.description ],
					[ 'p.videoBrowser-viewer-more',
						[ 'a.button.primary',
							{ href: '/' + item.title },
							mw.msg( 'videobrowser-read-more' )
						]
					],
					[ 'div.videoBrowser-viewer-player',
						[ 'video',
							videoAttributes,
							[ 'source', { src: item.video, type: 'video/mp4' } ],
							function ( element ) {
								player.element = element;
							}
						],
						state.bumper && !this.isMobile ? [ 'div.videoBrowser-viewer-bumper',
							state.current ? [ 'div.videoBrowser-viewer-bumperOption',
								[ 'p.videoBrowser-viewer-bumperTitle', mw.msg( 'videobrowser-replay' ) ],
								[ 'div.videoBrowser-viewer-bumperVideoItem',
									{ onclick: 'onReplayClick' },
									this.current.using( { id: state.current, icon: 'replay' } )
								],
							] : undefined,
							state.next ? [ 'div.videoBrowser-viewer-bumperOption',
								[ 'p.videoBrowser-viewer-bumperTitle', mw.msg( 'videobrowser-next' ) ],
								[ 'div.videoBrowser-viewer-bumperVideoItem',
									{ onclick: 'onNextClick' },
									this.next.using( { id: state.next, icon: 'next' } ),
								],
								[ 'p.videoBrowser-viewer-bumperClock',
									mw.msg( 'videobrowser-countdown', state.countdown )
								],
								[ 'div.videoBrowser-viewer-bumperCancel.button',
									{ onclick: 'onCancelClick' }, mw.msg( 'videobrowser-cancel' )
								]
							] : undefined,
						] : undefined
					],
					[ 'script', { type: 'application/ld+json' }, JSON.stringify( {
						'@context': 'http://schema.org',
						'@type': 'VideoObject',
						'name': item.title,
						'description': item.description,
						'thumbnailUrl': [
							item.poster.replace( /\/([^/]+$)/, '/-crop-360-360-$1' ),
							item.poster.replace( /\/([^/]+$)/, '/-crop-480-360-$1' ),
							item.poster
						],
						'uploadDate': item.updated,
						//'duration': 'PT1M33S', // TODO: Actual duration
						'contentUrl': String( window.location ),
						'embedUrl': item.video,
						'interactionCount': item.plays
					} ) ]
				],
				[ 'div.videoBrowser-viewer-queue' + ( this.isMobile ? '.section' : '' ),
					[ 'h2.videoBrowser-viewer-queue-title',
						[ 'span.mw-headline', mw.msg( 'videobrowser-next' ) ],
						this.isMobile ? undefined : [ 'label',
							mw.msg( 'videobrowser-auto-play' ),
							[ 'input', {
								type: 'checkbox',
								checked: autoPlayNextUp || undefined,
								onchange: 'onAutoPlayChanged'
							} ]
						]
					],
					[ 'div' + ( this.isMobile ? '.section_text' : '' ),
						[ 'div.videoBrowser-viewer-queue-items' ].concat( this.queue )
					]
				]
			];
		}
		return [ 'div.videoBrowser-viewer', mw.msg( 'videobrowser-not-found' ) ];
	},
	onDetach: function () {
		clearInterval( this.clock );
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
		WH.VideoBrowser.catalog.watchItem( this.item );
	},
	onCancelClick: function () {
		this.cancelCountdown();
		if ( this.element ) {
			var element = this.element;
		}
	},
	onCanPlay: function () {
		if ( this.element && !this.touched ) {
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
	onVolumeChange: function () {
		var track = false;
		if ( this.element ) {
			// Either after auto-mute or when initially acting
			if ( !this.muting || WH.VideoBrowser.hasUserMuted == this.element.muted ) {
				WH.VideoBrowser.hasUserMuted = this.element.muted;
				// Track mute change
				WH.maEvent( 'videoBrowser_mute', {
					videoId: this.item.id,
					videoTitle: this.item.title,
					userIsMobile: this.isMobile,
					userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
					userHasInteracted: WH.VideoBrowser.hasUserInteracted,
					userHasMuted: WH.VideoBrowser.hasUserMuted
				}, false );
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
		var item = WH.VideoBrowser.catalog.items().filter( { id: this.state.next } ).first();
		if ( item ) {
			WH.VideoBrowser.router.go( item.pathname );
			this.cancelCountdown();
		}
	},
	onEnded: function () {
		var next;
		var current = this.item.id;

		if ( !this.seeking && !this.finished ) {
			this.finished = true;
			// Track mute change
			WH.maEvent( 'videoBrowser_played', {
				videoId: this.item.id,
				videoTitle: this.item.title,
				userIsMobile: this.isMobile,
				userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
				userHasInteracted: WH.VideoBrowser.hasUserInteracted,
				userHasMuted: WH.VideoBrowser.hasUserMuted
			}, false );
		}

		if ( !WH.VideoBrowser.preferences.autoPlayNextUp || this.isMobile || this.seeking ) {
			return;
		}

		if ( this.queue ) {
			if ( this.queue.length ) {
				next = this.queue[0].item.id;
			}
		}
		this.change( { bumper: true, current: current, next: next, countdown: 10 } );
		if ( next ) {
			this.clock = setInterval( this.tick, 1000 );
		}
	},
	cancelCountdown: function () {
		var state = this.state;
		if ( state.bumper && state.next ) {
			clearInterval( this.clock );
			this.change( { bumper: false, current: null, next: null, countdown: 0 } );
		}
	},
	tick: function () {
		var state = this.state;
		if ( state.bumper && state.next ) {
			if ( state.countdown <= 1 ) {
				var item = WH.VideoBrowser.catalog.items().filter( { id: state.next } ).first();
				this.cancelCountdown();
				WH.VideoBrowser.router.go( item.pathname );
			} else {
				this.change( { countdown: state.countdown - 1 } );
			}
		}
	}
} );
