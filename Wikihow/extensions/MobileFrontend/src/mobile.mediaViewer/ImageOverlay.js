var Overlay = require( './../mobile.startup/Overlay' ),
	util = require( './../mobile.startup/util' ),
	mfExtend = require( './../mobile.startup/mfExtend' ),
	Icon = require( './../mobile.startup/Icon' ),
	icons = require( './../mobile.startup/icons' ),
	Button = require( './../mobile.startup/Button' ),
	cancelButton = icons.cancel( 'gray' ),
	detailsButton = new Button( {
		label: mw.msg( 'mobile-frontend-media-details' ),
		additionalClassNames: 'button',
		progressive: true
	} ),
	slideLeftButton = new Icon( {
		rotation: 90,
		name: 'arrow-invert'
	} ),
	slideRightButton = new Icon( {
		rotation: -90,
		name: 'arrow-invert'
	} ),
	LoadErrorMessage = require( './LoadErrorMessage' ),
	ImageGateway = require( './ImageGateway' ),
	// FIXME: mw.loader.require is a private function but there's no other way to get hold of
	// this right now using require will cause webpack to resolve it
	// Can be rewritten to mw.router when https://gerrit.wikimedia.org/r/#/c/mediawiki/core/+/482732 has been merged
	router = mw.loader.require( 'mediawiki.router' );

/**
 * Displays images in full screen overlay
 * @class ImageOverlay
 * @extends Overlay
 * @uses Icon
 * @uses ImageGateway
 * @uses LoadErrorMessage
 * @uses Router
 * @fires ImageOverlay#ImageOverlay-exit
 * @fires ImageOverlay#ImageOverlay-slide
 * @param {Object} options Configuration options
 * @param {OO.EventEmitter} options.eventBus Object used to listen for resize:throttled events
 */
function ImageOverlay( options ) {
	this.gateway = options.gateway || new ImageGateway( {
		api: options.api
	} );
	this.router = options.router || router;
	this.eventBus = options.eventBus;

	Overlay.call(
		this,
		util.extend(
			{
				className: 'overlay media-viewer',
				events: {
					'click .image-wrapper': 'onToggleDetails',
					// Click tracking for table of contents so we can see if people interact with it
					'click .slider-button': 'onSlide'
				}
			},
			options
		)
	);
}

mfExtend( ImageOverlay, Overlay, {
	/**
	 * allow pinch zooming
	 * @memberof ImageOverlay
	 * @instance
	 */
	hasFixedHeader: false,
	/**
	 * @memberof ImageOverlay
	 * @instance
	 */
	hideOnExitClick: false,
	/**
	 * @memberof ImageOverlay
	 * @instance
	 */
	template: mw.template.get( 'mobile.mediaViewer', 'Overlay.hogan' ),

	/**
	 * @memberof ImageOverlay
	 * @instance
	 * @mixes Overlay#defaults
	 * @property {Object} defaults Default options hash.
	 * @property {mw.Api} defaults.api instance of API to use
	 * @property {string} defaults.licenseLinkMsg Link to license information in media viewer.
	 * @property {Thumbnail[]} defaults.thumbnails a list of thumbnails to browse
	 */
	defaults: util.extend( {}, Overlay.prototype.defaults, {
		licenseLinkMsg: mw.msg( 'mobile-frontend-media-license-link' ),
		thumbnails: []
	} ),
	/**
	 * Event handler for slide event
	 * @memberof ImageOverlay
	 * @instance
	 * @param {jQuery.Event} ev
	 */
	onSlide: function ( ev ) {
		var nextThumbnail = this.$el.find( ev.target ).closest( '.slider-button' ).data( 'thumbnail' );
		this.emit( ImageOverlay.EVENT_SLIDE, nextThumbnail );
	},
	/**
	 * @inheritdoc
	 * @memberof ImageOverlay
	 * @instance
	 */
	preRender: function () {
		var self = this;
		this.options.thumbnails.forEach( function ( thumbnail, i ) {
			if ( thumbnail.getFileName() === self.options.title ) {
				self.options.caption = thumbnail.getDescription();
				self.galleryOffset = i;
			}
		} );
	},
	/**
	 * Setup the next and previous images to enable the user to arrow through
	 * all images in the set of images given in thumbs.
	 * @memberof ImageOverlay
	 * @instance
	 * @param {Array} thumbs A set of images, which are available
	 * @private
	 */
	_enableArrowImages: function ( thumbs ) {
		var offset = this.galleryOffset,
			lastThumb, nextThumb;

		if ( this.galleryOffset === undefined ) {
			// couldn't find a suitable matching thumbnail so make
			// next slide start at beginning and previous slide be end
			lastThumb = thumbs[thumbs.length - 1];
			nextThumb = thumbs[0];
		} else {
			// identify last thumbnail
			lastThumb = thumbs[ offset === 0 ? thumbs.length - 1 : offset - 1 ];
			nextThumb = thumbs[ offset === thumbs.length - 1 ? 0 : offset + 1 ];
		}

		this.$el.find( '.prev' ).data( 'thumbnail', lastThumb );
		this.$el.find( '.next' ).data( 'thumbnail', nextThumb );
	},
	/**
	 * Disables the possibility to arrow through all images of the page.
	 * @memberof ImageOverlay
	 * @instance
	 * @private
	 */
	_disableArrowImages: function () {
		this.$el.find( '.prev, .next' ).remove();
	},

	/**
	 * Handler for retry event which triggers when user tries to reload overlay
	 * after a loading error.
	 * @memberof ImageOverlay
	 * @instance
	 * @private
	 */
	_handleRetry: function () {
		// A hacky way to simulate a reload of the overlay
		this.router.emit( 'hashchange' );
	},

	/**
	 * @inheritdoc
	 * @memberof ImageOverlay
	 * @instance
	 */
	postRender: function () {
		var $img,
			$spinner = icons.spinner().$el,
			thumbs = this.options.thumbnails || [],
			self = this;

		/**
		 * Display media load failure message
		 * @method
		 * @ignore
		 */
		function showLoadFailMsg() {
			self.hasLoadError = true;

			$spinner.hide();
			// hide broken image if present
			self.$el.find( '.image img' ).hide();

			// show error message if not visible already
			if ( self.$el.find( '.load-fail-msg' ).length === 0 ) {
				new LoadErrorMessage( { retryPath: self.router.getPath() } )
					.on( 'retry', self._handleRetry.bind( self ) )
					.prependTo( self.$el.find( '.image' ) );
			}
		}

		/**
		 * Start image load transitions
		 * @method
		 * @ignore
		 */
		function addImageLoadClass() {
			$img.addClass( 'image-loaded' );
		}

		if ( thumbs.length < 2 ) {
			this._disableArrowImages();
		} else {
			this._enableArrowImages( thumbs );
		}

		this.$details = this.$el.find( '.details' );
		this.$el.find( '.image' ).append( $spinner );

		Overlay.prototype.postRender.apply( this );
		this.$details.prepend( detailsButton.$el );

		this.gateway.getThumb( self.options.title ).then( function ( data ) {
			var author, url = data.descriptionurl + '#mw-jump-to-license';

			$spinner.hide();

			self.thumbWidth = data.thumbwidth;
			self.thumbHeight = data.thumbheight;
			self.imgRatio = data.thumbwidth / data.thumbheight;

			// We need to explicitly specify document for context param as jQuery 3
			// will create a new document for the element if the context is
			// undefined. If element is appended to active document, event handlers
			// can fire in both the active document and new document which can cause
			// insidious bugs.
			// (https://api.jquery.com/jquery.parsehtml/#entry-longdesc)
			$img = self.parseHTML( '<img>', document );

			// Remove the loader when the image is loaded or display load fail
			// message on failure
			//
			// Error event handler must be attached before error occurs
			// (https://api.jquery.com/error/#entry-longdesc)
			//
			// For the load event, it is more unclear what happens cross-browser when
			// the image is loaded from cache. It seems that a .complete check is
			// needed if attaching the load event after setting the src.
			// (http://stackoverflow.com/questions/910727/jquery-event-for-images-loaded#comment10616132_1110094)
			//
			// However, perhaps .complete check is not needed if attaching load
			// event prior to setting the image src
			// (https://stackoverflow.com/questions/12354865/image-onload-event-and-browser-cache#answer-12355031)
			$img.on( 'load', addImageLoadClass ).on( 'error', showLoadFailMsg );
			$img.attr( 'src', data.thumburl ).attr( 'alt', self.options.caption );
			self.$el.find( '.image' ).append( $img );

			self.$details.addClass( 'is-visible' );
			self._positionImage();
			self.$el.find( '.details a' ).attr( 'href', url );
			if ( data.extmetadata ) {
				// Add license information
				if ( data.extmetadata.LicenseShortName ) {
					self.$el.find( '.license a' )
						.text( data.extmetadata.LicenseShortName.value )
						.attr( 'href', url );
				}
				// Add author information
				if ( data.extmetadata.Artist ) {
					// Strip any tags
					author = data.extmetadata.Artist.value.replace( /<.*?>/g, '' );
					self.$el.find( '.license' ).prepend( author + ' &bull; ' );
				}
			}
			self.adjustDetails();
		}, function () {
			// retrieving image location failed so show load fail msg
			showLoadFailMsg();
		} );

		this.eventBus.on( 'resize:throttled', this._positionImage.bind( this ) );
	},

	/**
	 * Event handler that toggles the details bar.
	 * @memberof ImageOverlay
	 * @instance
	 */
	onToggleDetails: function () {
		if ( !this.hasLoadError ) {
			this.$el.find( '.cancel, .slider-button' ).toggle();
			this.$details.toggle();
			this._positionImage();
		}
	},

	/**
	 * fixme: remove this redundant function.
	 * @memberof ImageOverlay
	 * @instance
	 * @param {Event} ev
	 */
	onExitClick: function ( ev ) {
		Overlay.prototype.onExitClick.apply( this, arguments );
		this.emit( ImageOverlay.EVENT_EXIT, ev );
	},

	/**
	 * @inheritdoc
	 * @memberof ImageOverlay
	 * @instance
	 */
	show: function () {
		Overlay.prototype.show.apply( this, arguments );
		this._positionImage();
	},

	/**
	 * Fit the image into the window if its dimensions are bigger than the window dimensions.
	 * Compare window width to height ratio to that of image width to height when setting
	 * image width or height.
	 * @memberof ImageOverlay
	 * @instance
	 * @private
	 */
	_positionImage: function () {
		var detailsHeight, windowWidth, windowHeight, windowRatio, $img,
			$window = util.getWindow();

		this.adjustDetails();
		// with a hidden details box we have a little bit more space, we just need to use it
		detailsHeight = !this.$details.is( ':visible' ) ? 0 : this.$details.outerHeight();
		windowWidth = $window.width();
		windowHeight = $window.height() - detailsHeight;
		windowRatio = windowWidth / windowHeight;
		$img = this.$el.find( 'img' );

		if ( this.imgRatio > windowRatio ) {
			if ( windowWidth < this.thumbWidth ) {
				$img.css( {
					width: windowWidth,
					height: 'auto'
				} );
			}
		} else {
			if ( windowHeight < this.thumbHeight ) {
				$img.css( {
					width: 'auto',
					height: windowHeight
				} );
			}
		}

		this.$el.find( '.image-wrapper' ).css( 'bottom', detailsHeight );
		this.$el.find( '.slider-button.prev' ).append( slideLeftButton.$el );
		this.$el.find( '.slider-button.next' ).append( slideRightButton.$el );
		cancelButton.$el.insertBefore( this.$details );
	},

	/**
	 * Function to adjust the height of details section to not more than 50% of window height.
	 * @memberof ImageOverlay
	 * @instance
	 */
	adjustDetails: function () {
		var windowHeight = util.getWindow().height();
		if ( this.$el.find( '.details' ).height() > windowHeight * 0.50 ) {
			this.$el.find( '.details' ).css( 'max-height', windowHeight * 0.50 );
		}
	}
} );

/**
 * fixme: remove this redundant constant.
 * @memberof ImageOverlay
 * @event
 */
ImageOverlay.EVENT_EXIT = 'ImageOverlay-exit';
/**
 * @memberof ImageOverlay
 * @event
 */
ImageOverlay.EVENT_SLIDE = 'ImageOverlay-slide';

module.exports = ImageOverlay;
