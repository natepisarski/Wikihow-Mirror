(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.lightbox = {
		
		curHash: '',
		$links: [],
		bodyClass: 'wh-featherlight-open',
		gallery: null,
		
		initialize: function () {
			this.$links = $('a.lightbox');
			
			this.$links.featherlightGallery({
				type: 'ajax',
				afterOpen: $.proxy(this, 'opened'),
				beforeClose: $.proxy(this, 'animateOut'),
				afterClose: $.proxy(this, 'closed'),
				afterContent: $.proxy(this, 'loaded'),
				openSpeed: 1,
				closeSpeed: 500,
				galleryFadeIn: 1,          /* fadeIn speed when image is loaded */
				galleryFadeOut: 1,         /* fadeOut speed before image is loaded */
				closeIcon: '',
				loading: "<div class='img-container'></div>",
				gallery: {
					next: '»',
					previous: '«'
				},
				variant: 'wh-featherlight'
			});
			
			this.checkHash();
		},
		
		checkHash: function () {
			this.curHash = window.location.hash;
			
			if (this.curHash !== '') {
				this.$links.each($.proxy(this, 'checkHref'));
			}
		},
		
		checkHref: function (index, link) {
			var $link = $(link);
			
			if ($link.attr('href') === this.curHash) {
				$link.click();
			}
		},
		
		loaded: function () {
			var gallery = $.featherlight.current();
			
			$('.featherlight-content .img-container').append(gallery.createNavigation('previous'));
			$('.featherlight-content .img-container').append(gallery.createNavigation('next'));
			
			$('.featherlight-content').find('.featherlight-next,.featherlight-previous').bind('click', function () {
				var action = $(this).hasClass('featherlight-next') ? 'next' : 'previous';
				//WH.ga.sendEvent('lightbox', action);
			});
			
			$('.wh-featherlight').append($('.featherlight-close'));
			
			$('#im-info').find('p').each(function () {
				$(this).parent().append("<div class='im-info-item'>" + $(this).html() + "</div>");
			});
			$('#im-info').find('h3,br,p').remove();
		},
		
		opened: function (event) {
			//WH.ga.sendEvent('lightbox', 'opened');
			
			$('.featherlight-content').addClass('animated fadeInDownBig');
			$('body').addClass(this.bodyClass);
		},
		
		animateOut: function () {
			$('.featherlight-content').addClass('animated fadeOutUpBig');
		},
		
		closed: function (event) {
			//WH.ga.sendEvent('lightbox', 'closed');
			
			if (history && history.pushState !== undefined) {
				history.pushState("", document.title, window.location.pathname + window.location.search);
			} else {
				window.location.hash = "";	
			}
			
			$('body').removeClass(this.bodyClass);
		}
	};
	WH.lightbox.initialize();
}());

