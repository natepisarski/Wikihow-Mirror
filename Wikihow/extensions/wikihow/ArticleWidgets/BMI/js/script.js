$(document).ready(function () {
    //When page loads...
    $(".tab_content").hide(); //Hide all content
    $("ul.tabs li:first").addClass("active").show(); //Activate first tab
    $(".tab_content:first").show(); //Show first tab content
    //On Click Event
    $("ul.tabs li").click(function () {
		$("#weight_uk").text(0);
		$("#weight_kg").text(0);
		$("#height_uk_ft").text(0);
		$("#height_uk_in").text(0);
		$("#height_cm").text(0);
		$(".no_result").show();
        $(".result_set").hide();
        $("ul.tabs li").removeClass("active"); //Remove any "active" class
        $(this).addClass("active"); //Add "active" class to selected tab
        $(".tab_content").hide(); //Hide all tab content
        var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to identify the active tab + content
        $(activeTab).fadeIn(); //Fade in the active ID content
        return false;
    });
}); // JavaScript Document
//------------------------------------------------------------------------------------------------------------------------------------
var _buf_current_val = 0;
var b_tracked = false;
/**
 * Creates an input element in div (selector) with value = div's text
 */
function makeInput(selector) {
    var current = $(selector).text();
    var width = 40;
    var maxlen = 2;
    var id = $(selector).attr("id");
    if (id == "weight_uk" || id == "weight_kg" || id == "height_cm") maxlen = 3;
    $(selector).empty().append("<input type='text' id='current_input' maxlength='" + maxlen + "'/>");
    $("#current_input").val(current).css("width", width).focus(function () {
        $(this).parent().parent().addClass("active_input");
        if ($(this).val() <= 0) $(this).val("");
        _buf_current_val = $(this).val();
    }).focus().focusout(function () {
        $(this).parent().parent().removeClass("active_input");
        var val = _buf_current_val;
        if ($(this).val() != "" || $(this).val() != 0) val = $(this).val();
        if (typeof (val) == "string" && val.length == 0) val = 0;
        $(this).parent().text(val);
        onchange();
        $(this).remove();
    }).keydown(function (event) {
        // Allow only backspace and delete
        if (event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 37 || event.keyCode == 39) {
            // let it happen, don't do anything
        } else {
            // Ensure that it is a number and stop the keypress
            if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105)) {
                event.preventDefault();
            }
            if (event.keyCode == 13) {
                $(this).focusout();
            }
        }
        onchange();
    }).keyup(function() {
    	onchange(this);
    });
}
/**
 * Increases value of selector
 */
function increase(selector) {
	if($(selector).attr("id") == "height_uk_in" && parseInt($(selector).text()) >=12) {
		$(selector).text("0");
		$("#height_uk_ft").text(parseInt($("#height_uk_ft").text()) + 1);
		return;
	}
    $(selector).text(parseInt($(selector).text()) + 1);
    onchange();
    return (false);
}
/**
 * Decreases value of selector
 */
function decrease(selector) {
    var current = parseInt($(selector).text());
    if (current >= 1) $(selector).text(parseInt($(selector).text()) - 1);
    onchange();
    return (false);
}
/**
 * Calc BMI in UK
 */
function calc_uk(lbs, ft, inch) {
    if (lbs <= 0 || ft <= 0) return Number.NaN;
    var inches = ft * 12 + parseInt(inch);
    return (lbs * 703 / (inches * inches));
}
/**
 * Calc BMI in si
 */
function calc_si(kg, cm) {
    if (kg <= 0 || cm <= 0) return Number.NaN;
    var m = cm / 100;
    return (kg / (m * m));
}
/**
 * Get text state
 */
function get_state(bmi) {
    if (bmi <= 18.5) return "the <span>UNDERWEIGHT</span> weight range";
    if (bmi > 18.5 && bmi <= 25) return "the <span>NORMAL</span> weight range";
    if (bmi > 25 && bmi <= 30) return "the <span>OVERWEIGHT</span> weight range";
    if (bmi > 30) return "the <span>OBESE</span> weight range";
}
function showresult(in_bmi) {
    var bmi = Math.abs(in_bmi).toFixed(2);
    var left;
    if (bmi <= 18.5) left = bmi * 1.8 - 8;
    if (bmi > 18.5 && bmi <= 25) left = (bmi - 18.5) * 10 + 29;
    if (bmi > 25 && bmi <= 30) left = (bmi - 18.5) * 13.5 + 8;
    if (bmi > 30) left = (bmi - 18.5) * 13.5 + 8;
    if (left > 191) left = 191;
    if (left < 0) left = -8;
    $(".i_m").stop().animate({
        left: left
    }, 1000);
    $(".result_set").find(".scale").text(bmi);
    $(".about_scale").html(get_state(bmi));
}
function onchange(object) {
	if (!b_tracked) {
		//alert("!!!");
		b_tracked = true;
	}
		
	var data = new Array();
	data['weight_uk'] = parseInt($("#weight_uk").text());
	data['height_uk_ft'] = parseInt($("#height_uk_ft").text());
	data['height_uk_in'] = parseInt($("#height_uk_in").text());	
	data['weight_kg'] = parseInt($("#weight_kg").text());
	data['height_cm'] = parseInt($("#height_cm").text());
	if(object) {
		var value = $(object).val();
		var parent_id = $(object).parent().attr("id");
		data[parent_id] = value;
	}
    if ($("#tab1").is(':visible')) {
        var bmi = calc_uk( data['weight_uk'] , data['height_uk_ft'] , data['height_uk_in'] );
        if (!isNaN(bmi)) {
            $("#tab1").find(".no_result").hide();
            $("#tab1").find(".result_set").fadeIn();
            showresult(bmi);
        }
    }
    if ($("#tab2").is(':visible')) {
        var bmi = calc_si(data['weight_kg'], data['height_cm'] );
        if (!isNaN(bmi)) {
            $("#tab2").find(".no_result").hide();
            $("#tab2").find(".result_set").fadeIn();
            showresult(bmi);
        }
    }
}
$(document).ready(function () {
    $(".input").click(function () {
        if ($("#current_input").length == 0) makeInput($(this).find(".editable_div"));
    });
    $(".more").click(function () {
        increase($(this).parent().find(".input > .editable_div"));
    });
    $(".small").click(function () {
        decrease($(this).parent().find(".input > .editable_div"));
    });
});