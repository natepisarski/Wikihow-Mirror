(function($, mw) {

var nextNum,
	interval,
	desiredText,
	elementNumber,
	typeInterval,
	inputActive = false;

$(document).ready(function(){

    //clear anything that the browser might have cached here
    $("#cse-search-hp input.search_box").val("");

	$(".hp_nav").click(function(){
		if(!$(this).hasClass("on")) {
			nextNum = parseInt($(this).attr("id").substr(4));
			clearTimeout(interval);
			clearInterval(typeInterval);
			rotateImage();
		}
	});

	$("#cse-search-hp input").click(function(){
		inputActive = true;
		clearInterval(typeInterval);
		$("#hp_container .hp_title").html("");
	});

	typewriter(1);
});

function rotateImage() {
	var currentElement, currentNum, nextElement;

	currentElement = $(".hp_top:visible");
	currentNum = parseInt($(currentElement).attr("id").substr(7));
	if(nextNum == null) {
		if($("#hp_top_"+ (currentNum+1)).length != 0)
			nextNum = currentNum + 1;
		else
			nextNum = 1;
	}
	nextElement = $("#hp_top_" + nextNum);

	$(nextElement).fadeIn(800);
	$(currentElement).fadeOut(800);
	$("#nav_" + currentNum).removeClass("on");
	$("#nav_" + nextNum).addClass("on");

	if(!inputActive)
		typewriter(nextNum);

	nextNum = null;
}

function typewriter(en) {
	elementNumber = en;
	desiredText = $("#hp_top_" + elementNumber + " .hp_text").attr("title");

	$("#hp_container .hp_title").html("");

	typeInterval = setInterval(typeText, 150); //how fast the typing happens
}

/***
 *
 * Adds one letter to the text being typed
 *
 ***/
function typeText() {
	var currentString = $("#hp_container .hp_title").html();
	var currentLength = currentString.length;
	var newChar = desiredText.charAt(currentLength);
	$("#hp_container .hp_title").html(currentString + newChar);
	if(currentLength + 1 == desiredText.length) {
		clearInterval(typeInterval);
		interval = setTimeout(rotateImage, 3000);
	}
}

})(jQuery, mw);
