<script type='text/javascript'>
	var initAds = $.getScript('https://s.yimg.com/uv/dm/scripts/syndication.js', function() {
		var adOptions = {
			DeskTop: {
                AdRange: '<?= $rangeTop ?>',
				SiteLink: false,
				EnhancedSiteLink: false
			},
			Mobile: {
                AdRange: '<?= $rangeTop ?>',
				SiteLink: false,
				EnhancedSiteLink: false
			}
		};
		var templateOptions = {
			DeskTop : {
				AdUnit : {
					borderColor : "#d2d8cd",
					lineSpacing : 20, //﴾valid values 8-25﴾adSpacing : 15,
					adSpacing: 5, // valid values 5-15
					font : "Helvetica,arial,sans-serif",
					urlAboveDescription: true,
					color: "#545"
				},
				Title : {
					fontsize : 16, //﴾ valid value 8-18 ﴾, color : "#ABABAB",
					underline : false,
					bold: true,
					color: '#363',
					onHover : {
						underline : true
					}
				},
				Description : {
					color: "#545"
				},
				URL : {
					color: '#363',
					onHover : {
						underline : true
					}
				},
				LocalAds: {
					color: "#363",
					onHover : {
						underline : true
					}
				},
				SmartAnnotations : {
					color: "#545454",
				},
				MerchantRating: {
					color: "#363",
					onHover : {
						underline : true
					}
				}
			},
			Mobile : {
				AdUnit : {
					borderColor : "#d2d8cd",
					lineSpacing : 20, //﴾valid values 8-25﴾adSpacing : 15,
					adSpacing: 5, // valid values 5-15
					font : "Helvetica,arial,sans-serif",
					urlAboveDescription: true,
					color: "#545"
				},
				Title : {
					fontsize : 16, //﴾ valid value 8-18 ﴾, color : "#ABABAB",
					underline : false,
					bold: true,
					color: '#363',
					onHover : {
						underline : true
					}
				},
				Description : {
					color: "#545"
				},
				URL : {
					color: '#363',
					onHover : {
						underline : true
					}
				},
				LocalAds: {
					color: "#363",
					onHover : {
						underline : true
					}
				},
				SmartAnnotations : {
					color: "#545454",
				},
				MerchantRating: {
					color: "#363",
					onHover : {
						underline : true
					}
				}
			}
		};

		var adOptions2 = $.extend(true, {}, adOptions);
		var templateOptions2 = $.extend(true, {}, templateOptions);
		adOptions2.DeskTop.AdRange = adOptions2.Mobile.AdRange = '3-3';

		var adOptions3 = $.extend(true, {}, adOptions);
		var templateOptions3 = $.extend(true, {}, templateOptions);
		adOptions3.DeskTop.AdRange = adOptions3.Mobile.AdRange = '4-6';

		var onNoAd = function(errObj, slotInfo) {
			if (slotInfo.ypaAdDivId) {
				$('#' + slotInfo.ypaAdDivId).remove();
			}
		};

		var slotIdPrefix = '<?=$slotIdPrefix?>';
		var adConfig = '<?=$adConfig;?>';
		var adTagType =  '<?=$adTypeTag;?>';

		window.ypaAds.insertMultiAd({
			ypaAdConfig   : adConfig,
			ypaAdTypeTag  : adTagType,
			ypaPubParams : {
				query: <?= $query ?>,
			},
			ypaPageCount: <?= $page ?>,
			ypaAdSlotInfo : [
				{
					EnhancedSiteLink: false,
					SiteLink: false,
					ypaAdSlotId : slotIdPrefix + 'WH_Top_Center',
					ypaAdDivId  : 'search_adblock_top',
					ypaAdWidth  : '722',
					ypaAdHeight : '127',
					ypaSlotOptions : {
						AdOptions: adOptions,
						TemplateOptions : templateOptions
					},
					ypaOnNoAd: onNoAd
				},
				{
					EnhancedSiteLink: false,
					SiteLink: false,
					ypaAdSlotId : slotIdPrefix + 'WH_Mid_Center',
					ypaAdDivId  : 'search_adblock_middle',
					ypaAdWidth  : '722',
					ypaAdHeight : '127',
					ypaSlotOptions : {
						AdOptions: adOptions2,
						TemplateOptions : templateOptions2
					},
					ypaOnNoAd: onNoAd
				},
				{
					EnhancedSiteLink: false,
					SiteLink: false,
					ypaAdSlotId : slotIdPrefix + 'WH_Bottom_Center',
					ypaAdDivId  : 'search_adblock_bottom',
					ypaAdWidth  : '722',
					ypaAdHeight : '127',
					ypaSlotOptions : {
						AdOptions: adOptions3,
						TemplateOptions : templateOptions3
					},
					ypaOnNoAd: onNoAd
				}
			]
		});
	});
</script>
