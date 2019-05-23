(function($) {
	'use strict';
	window.WH = window.WH || {};
	window.WH.MobileSearchHeader = {

		isNotTablet: $(window).width() < 600,

		init: function() {
			$('#hs').click($.proxy(function() {
				this.openHeaderSearch();
			},this));

			$('#hs_close').click($.proxy(function() {
				this.closeHeaderSearch();
				return false;
			},this));

			$('#hs_query').keyup($.proxy(function() {
				this.swapActionIcon();
			},this));

			$('#hs form').submit($.proxy(function() {
				WH.maEvent( 'mobile_search_submit_test', { isTablet: !this.isNotTablet, language: mw.config.get('wgContentLanguage') } );
			},this));

			//sync up the notifications click with the extra padding we give it
			$('#secondary-button.user-button').click(function() {
				$('#hs').removeClass('hs_notif');
			})
		},

		openHeaderSearch: function() {
			if (!$('#hs').hasClass('hs_active')) {
				this.swapActionIcon();

				$('#hs').addClass('hs_active')
				.one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', $.proxy(function() {
					$('#hs_query').focus();
					this.allowClickClose();
				},this));

				WH.maEvent( 'mobile_search_open_test', { isTablet: !this.isNotTablet, language: mw.config.get('wgContentLanguage') } );
			}
		},

		closeHeaderSearch: function() {
			$('#hs').removeClass('hs_active');
			$('.hs_action').css('display', 'none');
			this.disallowClickClose();
		},

		swapActionIcon: function() {
			var showX = $('#hs_query').val().length === 0;

			if (showX) {
				$('#hs_submit').css('display', 'none');
				$('#hs_close').css('display', 'block');
			}
			else {
				$('#hs_close').css('display', 'none');
				$('#hs_submit').css('display', 'block');
			}
		},

		allowClickClose: function() {
			$(window).on('click.header_click_close', function(e) {
				if ($(e.target).attr('id') != 'hs_query') {
					WH.MobileSearchHeader.closeHeaderSearch();
				}
			});
		},

		disallowClickClose: function() {
			$(window).off('click.header_click_close');
		}

	}

	if (WH.MobileSearchHeader.isNotTablet) {
		WH.MobileSearchHeader.init();
	}

})(jQuery);