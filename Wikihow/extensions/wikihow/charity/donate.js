(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.Donate = {
		show_button_message: false,


		init: function() {
			this.loadHtml();
			this.addHandlers();
		},

		loadHtml: function() {
			var img_num = this.imageNumber();
			var custom_non_profit = this.customNonProfitName();

			this.show_button_message = custom_non_profit == 'WaterOrg';

			$.get( '/Special:Charity',
				{
					action: 'load',
					donate_image: img_num,
					non_profit: custom_non_profit
				},
				function(data) {
					if (data && data.html) $('#donate_section').replaceWith(data.html);
				},
				'json'
			);
		},

		addHandlers: function() {
			$(document).on("click", "#donate_button", function(e){
				if (WH.Donate.show_button_message) {
					e.preventDefault();
					WH.Donate.buttonResponseMessage();
				}

				WH.maEvent("donate_click", {
					articleId: mw.config.get('wgArticleId'),
					articleTitle: mw.config.get('wgTitle'),
					charityImage: WH.Donate.donateImage()
				}, false);
			});

			$(document).on("click", "#donate_close", function(){
				WH.maEvent("donate_close", { articleId: mw.config.get('wgArticleId'), articleTitle: mw.config.get('wgTitle') }, false);
				$(this).closest('.section').slideUp();
				WH.Donate.markHideCharityPreference();
				return false;
			});
		},

		imageNumber: function() {
			var forced_image = Number($('#donate_section').data('image_number'));
			var image_count = $('#donate_section').data('image_count');
			if (!image_count) image_count = 1;

			var num = forced_image ? forced_image : Math.floor(Math.random() * image_count) + 1;
			return num;
		},

		customNonProfitName: function() {
			var data_non_profit_name = $('#donate_section').data('non_profit_name');
			var non_profit_name = $("<div/>").html(data_non_profit_name).text(); //sanitize
			return non_profit_name.length ? non_profit_name : '';
		},

		donateImage: function() {
			var image_src = $('#donate img').attr('src');
			var image = image_src.match(/images\/(.*)/);
			if (!image || typeof(image[1]) == 'undefined') return image_src;
			return image[1];
		},

		markHideCharityPreference: function() {
			var api = new mw.Api();
			api.get( {
				action: 'query',
				meta: 'userinfo',
				uiprop: 'preferencestoken'
			} )
			.then( function( data ) {
				if ( data.query && data.query.userinfo ) {
					var token = data.query.userinfo.preferencestoken;

					api.post( {
						action: 'options',
						token: token,
						change: 'showcharitysection=0'
					} );
				}
			} );
		},

		buttonResponseMessage: function() {
			$('#donate_button').replaceWith(mw.message('donate_button_response').text());
		}
	};

	$(document).ready(function() {
		WH.Donate.init();
	});
})($,mw);
