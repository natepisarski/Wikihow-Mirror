(function($) {

$.fn.whDropDown = function(i_params) {

    var whDropDown_code = "<div class='whdropdown_element'><div class='wh_dd_input'><div class='wh_dd_value'>0</div><select class='wh_dd_select'/><div class='wh_dd_button'/></div></div>";
    
    /* Default settings */
    var whDropDown_default_properties = {
        width: 150, // Width in pixels
        the_default: "Select..",
        options: { },
        onChange: function(value) {}
    };
    
    var params = jQuery.extend({},whDropDown_default_properties,i_params);
    
    $(this).append(whDropDown_code);
    var instance = $('.whdropdown_element',this);
    
    $("select",instance).width(params.width);
    $(".wh_dd_value",instance).width(params.width);
    
    $(".wh_dd_value", instance).text(params.the_default);
    
    for (value in params.options)
        $("select",instance).append( $("<option>").attr("value", value).text(params.options[value]) );

    $("select",instance).bind("change click focus",function() {
        $(".wh_dd_value", instance).text($(this).find("option:selected").text());
        $(this).width(params.width);
        //$(".wh_dd_input").removeClass("wh_dd_active_input");
        params.onChange(instance, $(this).find("option:selected").attr("value"));
    }).bind('mouseup mousedown',function() {
        $(this).width(params.width);
        //$(".wh_dd_input").addClass("wh_dd_active_input");
    }).bind('focusout blur',function() { 
        $(this).width(params.width); 
        //$(".wh_dd_input").removeClass("wh_dd_active_input");
    });
    
}

})( jQuery );