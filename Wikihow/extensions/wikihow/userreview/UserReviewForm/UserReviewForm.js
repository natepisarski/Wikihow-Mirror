/*global mediaWiki, jQuery, WH*/
( function ( mw, $ ) {
	window.WH = WH || {};

	WH.UserReviewForm = function () {
		this.submittedReviewId = null;
		this.userReviewEndpoint = '/Special:UserReviewForm';
		this.hasLoggedIn = false;
	};

	WH.UserReviewForm.prototype = {
		getScrollingElement: function () {
			return WH.isMobileDomain ? $( '#mw-mf-viewport' ) : $( 'body' );
		},
		machinifyLog: function () {
			WH.maEvent( 'opti_testimonial', {
				category: 'opti',
				pagetitle: mw.config.get( 'wgTitle' ),
				testemail: $( '#email' ).val(),
				testdetail: $( '#review' ).val(),
				testfirst: $( '#first-name' ).val(),
				testlast: $( '#last-name' ).val(),
				testarticleid: mw.config.get( 'wgArticleId' ),
				testsource: WH.isMobileDomain ? 'mobile' : 'desktop',
				testwithpageloadstat: 'yes',
				testdetailunstripped: '',
			}, false );
		},
		validateInput: function ( key, object ) {
			$( object ).css( 'border-color', '#eee' );
			if ( $( object ).val().length < 1 ) {
				$( object ).css( 'border-color', 'red' );
				return false;
			}
			return true;
		},
		postUserReview: function () {
			var urf = this;
			var valid = true;
			$( '.required' ).each( function ( key, object ) {
				var currValid = urf.validateInput( key, object );
				valid = valid && currValid;
			} );

			if ( valid ) {
				$.when(
					$.post( urf.userReviewEndpoint,
						{
							action: 'post_review',
							articleId: mw.config.get( 'wgArticleId' ),
							firstName: $( '#first-name' ).val(),
							lastName:$( '#last-name' ).val(),
							review:$( '#review' ).val(),
							rating:$( '.ur_helpful_icon_star.mousedone' ).length,
							image:$( '#urf_uci_image' ).val()
						},
						'json'
					),
					mw.config.get( 'wgUserId' ) == null && urf.initSocialLoginForm()
				).then( function ( result ) {
					$( '#urf-content-container' ).fadeOut( 'fast',function () {
						if( mw.config.get( 'wgUserId' ) == null ) {
							$( '#urf-social-login' ).fadeIn( 'fast' );
							WH.sendToOpti( 'page', 'pageName', '526710254_howtohero_more_info' );
						} else {
							$( '#urf-thanks' ).fadeIn( 'fast' );
							WH.sendToOpti( 'page', 'pageName', '526710254_howtohero_more_info' );
						}
					} );
					if( result.success ) {
						urf.submittedReviewId = result.success.id;
					}
				} );
				urf.machinifyLog();
			} else {
				$( '#urf-submit' ).prop( 'disabled', false );
			}
		},
		loadUserReviewForm: function () {
			var urf = this;
			$.get( urf.userReviewEndpoint,
				{ action:'get_form' },
				function ( result ) {
					if ( $( '#urf_form_container' ).length < 1 ) {
						$( 'body' ).append( result.html );
					}
					urf.starBehavior();
					$( '#urf-popup' ).magnificPopup( {
						fixedContentPos: false,
						fixedBgPos: true,
						showCloseBtn: false,
						overflowY: 'auto',
						preloader: false,
						type: 'inline',
						closeBtnInside: true,
						callbacks: {
							beforeClose: function () {
								if( urf.hasLoggedIn ) {
									window.location.reload();
								}
							}
						}
					} );
					$( '.urf-close' ).click( function () {
						urf.hideUserReviewForm();
					} );
					$( '#urf-submit' ).click( function () {
						$( '#urf-submit' ).prop( 'disabled', true );
						urf.postUserReview();
					} );
					var scrollingElement = urf.getScrollingElement();
					scrollingElement.addClass( 'modal-open' );
					$( '#urf-popup' ).trigger( 'click' );
				},
				'json'
			);
		},
		setUCIImage: function ( imageName ) {
			$( '#urf_uci_image' ).val( imageName );
		},
		hideUserReviewForm: function () {
			var urf = this;
			var scrollingElement = urf.getScrollingElement();
			scrollingElement.removeClass( 'modal-open' );
			$.magnificPopup.close();
		},
		starBehavior: function () {
			$( '.ur_star_container' ).each( function ( index ) {
				$( this ).bind( {
					mouseenter: function () {
						for ( var j = 1; j <= index+1; j++ ) {
							$( '#ur_star' + j + ' > div' ).addClass( 'mousevote' );
						}
					},
					mouseleave: function () {
						for ( var j = 1; j <= index+1; j++ ) {
							$( '#ur_star' + j + ' > div' ).removeClass( 'mousevote' );
						}
					},
					click: function () {
						$( '.ur_star_container .ur_helpful_icon_star' ).removeClass( 'mousedone mousevote' );
						for ( var j = 1; j <= index+1; j++ ) {
							$( '#ur_star' + j + ' > div' ).addClass( 'mousedone' );
						}
					}
				} );
			} );
		},
		initSocialLoginForm: function () {
			var urf = this;
			var whLoginDone = function ( data ) {
				// Called after the social signup/login is complete
				urf.hasLoggedIn = true;

				$( '#urf-social-login-done .urf-social-avatar' ).attr( 'src', data.user.avatarUrl );
				$( '#urf-social-login-done .urf-user-link' ).attr( 'href', '/User:'+data.user.username ).html( data.user.realName );

				var properties = {
					category: 'account_signup',
					type: data.type,
					prompt_location: 'stories'
				};
				WH.maEvent( 'account_signup', properties, false );

				//now we have the new userid, so we need to associate it with the submitted story
				$.ajax( {
					type: 'GET',
					url: 'https://' + window.location.hostname + urf.userReviewEndpoint,
					data: { action: 'update', us_id: urf.submittedReviewId, us_user_id: data.user.userId },
					dataType: 'jsonp',
					jsonpCallback: 'wh_jsonp_ur'
				} ).done( function ( response ) {
					$( '#urf-social-login .urf-social-close' ).on( 'click', function ( e ) {
						e.preventDefault();
						window.location.reload();
					} );

				} );

				$( '#urf-social-login-before' ).hide();
				$( '#urf-social-login-done' ).show();
			};

			return WH.social.setupAutoLoginButtons( {
				fb: '#urf-social-login .facebook_button',
				gplus: '#urf-social-login .google_button'
			}, whLoginDone );
		}
	};
} ( mediaWiki , jQuery ) );
