( function ( mw, $ ) {
	window.WH = window.WH || {};


	$.fn.diagram = function(params){
		function rotate(angle) {
			return {
				"-webkit-transform": "rotate(" + angle + "deg)",
				   "-moz-transform": "rotate(" + angle + "deg)",
				    "-ms-transform": "rotate(" + angle + "deg)",
				     "-o-transform": "rotate(" + angle + "deg)",
				        "transform": "rotate(" + angle + "deg)"
			};
		}
		var defaults = {
			size: "40",
			borderWidth: "9",
			bgFill: "#D8D8D8",
			frFill: "#93B777",
			textSize: 14+'px',
			textColor: '#C8C8C8',
			percent: '0%'
		};

		var options = $.extend({}, defaults, params);

		var $this = $(this);
		var dataAttr = options.percent;
		var data = parseFloat(dataAttr);

		var cssMain = {
			"position": "relative",
			"width": options.size + "px",
			"height": options.size + "px",
			"border": options.borderWidth + "px " + "solid " + options.bgFill,
			"border-radius": "50%",
			"z-index": "1"
		};

		var cssElems = {
			"position": "absolute",
			"top": -options.borderWidth + "px",
			"right": -options.borderWidth + "px",
			"bottom": -options.borderWidth + "px",
			"left": -options.borderWidth + "px",
			"border": options.borderWidth + "px " + "solid",
			"border-radius": "50%"
		};

		$this.css(cssMain);

		var text = $('<span></span>')
			.appendTo($this)
			.css({
				"display": "block",
				"position": "relative",
				"z-index": "2",
				"text-align": "center",
				"font-size": options.textSize,
				"font-weight": "normal",
				"height": options.size + "px",
				"line-height": options.size + "px",
				"color": options.textColor
			})
			.text(dataAttr);
		var bg = $('<div></div>')
			.appendTo($this)
			.css(cssElems)
			.css({
				"border-color": options.frFill,
				"border-left-color": "transparent",
				"border-bottom-color": "transparent",
				"z-index": "1"
			});

		var fill = $('<div></div>')
			.appendTo($this)
			.css(cssElems)
				.css({
				"border-color": options.bgFill,
				"border-left-color": "transparent",
				"border-bottom-color": "transparent",
				"z-index": "1"
			});

		var angle;
		if (data >= 0 && data <= 50) {
			angle = (225 - 45)/50*data + 45;
		} else {
			angle = (405 - 225)/50*data + 225;
			fill.css({
				"border-color": options.frFill,
				"border-left-color": "transparent",
				"border-bottom-color": "transparent",
				"z-index": "1"
			});
		}
		bg.css(rotate(45));
		fill.css(rotate(angle));

		return this;
	};

	WH.usercompletedimagesupload = (function() {
		var url = "/Special:UserCompletedImages?viapage=" + wgPageName;
		var startTime = null;
		var domain = WH.isMobileDomain ? "mobile" : "desktop";
		var submitted = 0;
		var originalAdd = $.blueimp.fileupload.prototype.options.add;

		$(document).bind('drop dragover', function (e) {
			e.preventDefault();
		});

		$(document).on("click", "#uci_userreview_cta a", function(e){
			e.preventDefault();
			if( WH.isMobileDomain ) {
				var ext = 'ext.wikihow.UserReviewForm.mobile';
			} else {
				var ext = 'ext.wikihow.UserReviewForm';
			}
			mw.loader.using(ext, function () {
				var urf = new window.WH.UserReviewForm();
				urf.loadUserReviewForm();
				$(document).on("click", '#urf-popup', function(){
					var urf = new window.WH.UserReviewForm();
					urf.setUCIImage($("#uci_userreview_cta").data("image"));
				});
			});
		});

		$('#uci_fileupload').fileupload({
			url: url,
			formData: {mobile: WH.isMobileDomain },
			dataType: 'json',
			disableImageMetaDataLoad: false,
			disableImageLoad: false,
			previewMaxWidth: 126*3,
			previewMaxHeight: 120*3,
			pasteZone: null,
			dropZone: $('#uci_fileupload'),
			previewCrop: true,

            add: function (e, data) {
				// limit to 1 file upload at a time
				submitted = submitted + 1;
				if (submitted > 1) {
					return;
				}
				console.log(" my add here");
                var $this = $(this);
                data.process(function () {
                    return $this.fileupload('process', data);
                });
                originalAdd.call(this, e, data);
            },
			submit: function (e, data) {
				$('#uci_progress').show();
				$('.uci_fileinput_loading').show();
				$('.uci_fileinput_center').hide();
				$('#uci_fileinput_square').removeClass('uci_fileinput_square_bg');
				$('#uci_upload_response_error').hide();

				$('#uci_progress_spin').show();
				WH.ga.sendEvent('picturepatrol', 'uploadbegin', domain);
				startTime = new Date().getTime();
			},
			done: function (e, data) {
				if (data.result.error) {
					$('#uci_upload_response_error').show();
					$('#uci_progress').hide();
					$('#uci_fileinput_square').addClass('uci_fileinput_square_bg');
					$('.uci_fileinput_button').show();
					$('.uci_fileinput_loading').hide();
					$('#uci_fileinput_spin').hide();
					$('.uci_fileinput_center').show();
					submitted = 0; //reset submitted
					WH.ga.sendEvent('picturepatrol','uploaderror', data.result.error);
				} else {
					var response = data.result.uploadResponse ? data.result.uploadResponse : data.result.successMessage;

					if (data.files.length > 0) {
						var file = data.files[0];
						var lbSrc = "/images/test.png";
						var newImg = "<a class='swipebox ucis_swipebox' href=#></a>";
						var a = $(newImg).append(file.preview);
						a = $(a).after("<div class='uci_thumbnail_description uci_just_uploaded'>"+response+"</div>");

						//no longer empty!
						$('#uci_fileinput_square_wrapper').removeClass('uci_empty');

						$('#uci_fileinput_square').delay(10).fadeOut('slow', function() {
							var replaced = $(this).replaceWith(a);
							$('#uci_fileinput_square').fadeIn("slow");
						});

						// add to the lightbox array if it exists
						WH.ucilightbox = WH.ucilightbox || [];
						var durl = file.preview.toDataURL();
						var newLB = {"pageId":12345, 'href':durl};

						var index = $('.uci_thumb_wrapper').length;
						WH.ucilightbox.splice(index, 0, newLB);
						var count = parseInt($('.ucis_count').text());
						$('.ucis_count').html(count + 1);

					}

					WH.ga.sendEvent('picturepatrol','uploadcomplete', domain);

					var endTime = new Date().getTime();
					var timeSpent = endTime - startTime;
					ga('send', 'timing', 'picturepatrol', 'upload complete', timeSpent);

					//now make the userreview cta show
					$("#uci_userreview_cta").show();
					$("#uci_userreview_cta").data("image", data.result.titleDBkey);
				}
			},
			progressall: function (e, data) {
				var progress = parseInt(data.loaded / data.total * 100, 10);
				$('#uci_progress .uci_progress_bar').css( 'width', progress + '%');
				$('#uci_fileinput_spin').empty();
				$('#uci_fileinput_spin').diagram({"percent": progress +'%'});
			}
		}).on('fileuploadprocessalways', function (e, data) {

			var index = data.index;
			var file = data.files[index];

			if (file.error) {
				$('#uci_upload_response').html(file.error);
				$('#uci_upload_response').show();
			}
		}).prop('disabled', !$.support.fileInput)
			.parent().addClass($.support.fileInput ? undefined : 'disabled');
		}());


}( mediaWiki, jQuery ) );
