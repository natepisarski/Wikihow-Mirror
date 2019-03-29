(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.Contribute = {
		init: function() {
			WH.maEvent("contribute_landing", {}, false);
			this.clickHandlers();
		},

		clickHandlers: function() {
			$(document).on("click", ".na_often", function(e){
				e.preventDefault();
				$(".na_often").removeClass("active");
				$(this).addClass("active");
			});
			$(document).on("click", ".na_amount", function(e){
				e.preventDefault();
				$(".na_amount").removeClass("active");
				$(this).addClass("active");
			});
			$(document).on("click", ".na_other", function(e){
				$(".na_amount").removeClass("active");
			});
			$(document).on("click", ".na_payment", function(e){
				e.preventDefault();
				var howOften = $(".na_often.active").text();
				var howMuch;
				if($(".na_amount.active").length > 0) {
					howMuch = $(".na_amount.active").data("amount");
				} else {
					howMuch = $(".na_other").val();
				}
				//alert("Sending $" + howMuch + " (" + howOften + ") to machinify");
				WH.maEvent("contribute_submit", {amount: howMuch, frequency: howOften}, false);
				$(this).hide();
				$("#na_soon").show();
			});
		}

	};
	$(document).ready(function() {
		WH.Contribute.init();
	});
})();
