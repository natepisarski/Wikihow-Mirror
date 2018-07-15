( function ( mw, $ ) {

	window.WH = window.WH || {};

	WH.usercompletedimages = (function() {
		// Add feedback link to article images
		var timer = 0;
		var loadingMore = false;
		$('#uci_fileupload').on("click", function() {
			$('#uci_progress .uci_progress_bar').css( 'width', '0px');
			$('#image_upload_response').hide();
			$('#uci_preview').empty();
		});

		$("#uci_images").on("click", ".swipebox", function(e) {
			e.preventDefault();
			var index = $(".swipebox").index($(this));
			$.swipebox( WH.ucilightbox, {
				beforeSetSlide: function() {
					WH.ga.sendEvent('picturepatrol','lightboxview');
				},
				initialIndexOnArray: index,
				afterSetDim: function() {
					// we need to correct the size of this box becuase of the
					// floating header
					var newHeight = window.innerHeight ? window.innerHeight : $( window ).height();
					newHeight = newHeight - 39;
					$('#swipebox-overlay').height(newHeight);
				},
				afterOpen: function() {
					WH.ga.sendEvent('picturepatrol','lightboxopen');
					$('#swipebox-slider').on('click', '.current > img', function(e) {
						e.stopPropagation();
					});
					$('#swipebox-slider').on('click', function(e) {
						$.swipebox.close();
					});
				},
			});
		});

		$('.uci_more').on('click', function(e) {
			e.preventDefault();
			if (loadingMore == true) {
				return;
			}

			loadingMore = true;
			var limitVal = 18;

			var domain = 'desktop';
			if (WH.isMobileDomain) {
				domain = 'mobile';
				limitVal = 4;
			}
			WH.ga.sendEvent('picturepatrol','showmore', domain);

			$.post('/Special:PicturePatrol', {
				showmore: true,
				domain: domain,
				hostPage: wgTitle,
				offset: $('.uci_thumb_wrapper').length,
				limit: limitVal,
			},
			function(result) {
				loadingMore = false;
				if ( result.end === true ) {
					$('.uci_more').hide();
				}

				var newHtml = "", ago_msg = "";
				$.each(result['thumbs'], function( i, thumb ) {
					ago_msg = domain == 'mobile' ? thumb['timeago'] : mw.msg('uploaded_timeago',thumb['timeago']);
					newHtml += "<a class='uci_thumbnail swipebox ucis_swipebox' pageid='"+thumb['pageId']+"'' href='"+thumb['lbSrc']+"''>"
											+ "<div class='uci_thumb_wrapper'><img src='"+thumb['src']+"' alt='' class='defer' /></div>"
											+ "<div class='uci_thumbnail_description'>"+ago_msg+"</div></a>";
				});

				var newHtml = WH.usercompletedimages.setupFlagging($(newHtml));

				$('#uci_images').append(newHtml);
				$('#uci_fileinput_square_wrapper').appendTo('#uci_images');
				$('.uci_more').insertAfter('#ui_upload_response');
			},
			'json');
		});

		return {
			setupFlagging : function(target) {
				// no flagging for anons
				if(mw.user.isAnon()) {
					return target;
				}

				// there is no image flagging on mobile for now
				if (WH.isMobileDomain) {
					return target;
				}

				//do not flag the placeholder
				if (target.attr('id') == 'uci_fileinput_square_wrapper') return target;

				var flagLink = "<a class='uci_img_flag' href='#' style='display:none'><span class='uci_img_ico'></span>Flag</a>";
				target.prepend(flagLink);
				target.hover(function() {
					var img = this;
					timer = setTimeout(function(){$(img).find('.uci_img_flag').fadeIn();}, 500);
				}, function() {
					clearTimeout(timer);
					$(this).find('.uci_img_flag').hide();
				});
				return target;
			},
			init : function() {
				WH.usercompletedimages.setupFlagging($('.uci_flaggable'));
				if ($("#uci_sidebox").length > 0) $("#uci_sidebox").show();
			},
		};
	}());

	$( function() {
		WH.usercompletedimages.init();
	});
}( mediaWiki, jQuery ) );
