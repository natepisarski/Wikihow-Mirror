(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.WikihowContentAds = {
		ads: {
			0: {
				"image": "papercrafts.svg",
				"header": "Discover a new hobby with wikiHow’s course on paper crafts.",
				"text": "Take the full course to become a crafting expert.",
				"buttonText": "Sign up",
				"class": "c01"
			},
			1: {
				"image": "papercrafts.svg",
				"header": "Discover a new hobby with wikiHow’s course on wildlife origami for kids.",
				"text": "Take the full course to become a crafting expert.",
				"buttonText": "Sign up",
				"class": "c02"
			},
			2: {
				"image": "papercrafts.svg",
				"header": "Discover a new hobby with wikiHow’s course on daily crafts for kids.",
				"text": "Take the full course to become a crafting expert.",
				"buttonText": "Sign up",
				"class": "c03"
			},
			3: {
				"image": "science.svg",
				"header": "Get the wikiHow Science Camp subscription box!",
				"text": "We’ll send you new activities every month, plus all the supplies you need.",
				"buttonText": "Sign up",
				"class": "sb01"
			},
			4: {
				"image": "artbox.svg",
				"header": "Get the wikiHow Art Camp subscription box!",
				"text": "We’ll send you new activities every month, plus all the supplies you need.",
				"buttonText": "Sign up",
				"class": "sb02"
			},
			5: {
				"image": "craft.svg",
				"header": "Get the wikiHow Craft Camp subscription box!",
				"text": "We’ll send you new activities every month, plus all the supplies you need.",
				"buttonText": "Sign up",
				"class": "sb03"
			},
			6: {
				"image": "productive.png",
				"header": "Get the wikiHow Adulting 101 subscription box!",
				"text": "We’ll send you new activities every month, plus all the supplies you need.",
				"buttonText": "Sign up",
				"class": "sb04"
			},
			7: {
				"image": "reading.png",
				"header": "Join the wikiHow Book Club!",
				"text": "Dive into literary classics with wikiHow and fellow book-lovers.",
				"buttonText": "Sign Up",
				"class": "bc01"
			},
			8: {
				"image": "dog_ebook.png",
				"header": "Get the wikiHow eBook on how to take care of a dog.",
				"text": "A complete guide for dog-owners, all in one place",
				"buttonText": "Sign up",
				"class": "eb01"
			},
			9: {
				"image": "cat_ebook.png",
				"header": "Get the wikiHow eBook on how to take care of a cat.",
				"text": "A complete guide for cat-owners, all in one place",
				"buttonText": "Sign up",
				"class": "eb02"
			},
			10: {
				"image": "house_ebook.png",
				"header": "Get the wikiHow eBook on how to fix stuff around the house.",
				"text": "Everything you need to know to maintain your house in one book",
				"buttonText": "Sign up",
				"class": "eb03"
			},
			11: {
				"image": "draw.png",
				"header": "Learn advanced drawing skills in a live online class.",
				"text": "Sign up to join our live workshops taught by wikiHow instructors.",
				"buttonText": "Sign up",
				"class": "lc01"
			},

		},

		init: function() {
			if($("#hp_wca_container").length > 0) {
				var urlParams = new URLSearchParams(window.location.search);
				if(urlParams.has("id")) {
					var random = urlParams.get("id");
				} else {
					var random = Math.floor(Math.random() * 12);
				}

				var $container = $("#hp_wca_container");
				var selectedAd = WH.WikihowContentAds.ads[random];
				$container.addClass(selectedAd["class"]);
				$("img", $container).attr("src", "/extensions/wikihow/WikihowContentAds/images/" + selectedAd["image"]);
				$("h4", $container).html(selectedAd["header"]);
				$("p", $container).html(selectedAd["text"]);
				$("a", $container).html(selectedAd["buttonText"]);
				$("#hp_wca").show();

				$("a", $container).on("click", function(e){
					e.preventDefault();
					if(WH.event) {
						//send the event
						if(mw.config.get("wgUserId") == null) {
							var user_state = "logged out";
						} else {
							var user_state = "logged in"
						}
						WH.event('hp_promo_concepttest_01_click_open_em', {'type': $(this).parents("#hp_wca_container").attr("class"), 'user_state': user_state});
					}

					//show the lightbox
					$("#wca_lightbox").show();
					$("#wca_close").on("click", function(e) {
						e.preventDefault();
						$("#wca_lightbox").hide();
					});
					$("#wca_lightbox #wca_email").on("click", function(e){
						e.preventDefault();
						if($("#wca_lightbox input").val() != "") {
							//send to back end
							$.ajax({
								url: '/Special:WikihowContentAds',
								type: 'POST',
								dataType: 'json',
								data:
									{
										'action': 'save-email',
										'campaign': $("#hp_wca_container").attr("class"),
										'email': $("#wca_lightbox input").val()
									}
							});
							$("#wca_lightbox h2").html("Thanks!");
							$("#wca_lightbox p:first").html("Thanks for your interest! We'll reach out to you with more information about our new educational activities and experiences.");
							$("#wca_lightbox p:last").html("");
							$("#wca_lightbox #wca_email, #wca_lightbox input").hide();
						} else {
							$("#wca_lightbox input").addClass("error");
						}
					});

				});

			}
		}
	};

	$(document).ready(function() {
		WH.WikihowContentAds.init();
	});
})();
