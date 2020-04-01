(function($) {

$.fn.whLabel = function(i_params) {

    var whLabel_code = "<div class='whlabel_element'/>";
    
    /* Default settings */
    var whLabel_default_properties = {
        width: 240, // Width in pixels
        value: "",
        lines : 1,
        fade: false,
        startFontSize: "",
        onChange: function(element,font_size) {}
    };
    
    var params = jQuery.extend({},whLabel_default_properties,i_params);
    
    $(this).empty().append(whLabel_code);
    $(".whlabel_element",this).hide().width(params.width).text(params.value);
    
    if(typeof(params.startFontSize) == "number")
        $(".whlabel_element",this).css("font-size",params.startFontSize);
        
    $(this).append("<div class='wh_l_dub'>");
    $(".wh_l_dub",this).width(params.width).hide().text(params.value);
    
    if(typeof(params.startFontSize) == "number")
        $(".wh_l_dub",this).css("font-size",params.startFontSize);   
		
    //var lh = parseInt($(".wh_l_dub",this).css('line-height'));
    var lh = parseInt($(".wh_l_dub",this).css('font-size'));
    var h = $(".wh_l_dub",this).height();
	
    var new_fs = lh;
    while (params.lines < h/lh) {
        new_fs--;
        $(".wh_l_dub",this).css('font-size',new_fs + "px");
        //lh = parseInt($(".wh_l_dub",this).css('line-height'));
		lh = parseInt($(".wh_l_dub",this).css('font-size'));
        h = $(".wh_l_dub",this).height();
    }
    params.onChange(this,new_fs);
    $(".wh_l_dub",this).remove();
    
    if(params.fade)
        $(".whlabel_element",this).css("font-size", new_fs).fadeIn("fast");//.animate({ fontSize: new_fs },200);
    else
        $(".whlabel_element",this).css("font-size", new_fs).show();
    

}


})( jQuery );