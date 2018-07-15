(function($) {

function isInt(n) {
   return typeof n == 'number';// && n % 1 == 0;
}


$.fn.whUpDown = function(i_params) {
    
    /* Element HTML-code */
    var whUpDown_code = '<div class="whupdown_element"><div class="wh_ud_input"><span class="wh_ud_units"></span><div class="wh_ud_editable_value">0</div>  </div> <a class="wh_ud_increase"></a> <a class="wh_ud_decrease"></a>';

    /* Default settings */
    var whUpDown_default_properties = {
        width: 150, // Width in pixels
        units: "", // Units (m, cm, ft...)
        value: 0, // Default value
        onIncrease: function(element,value) {}, // On Increase (up click) event handler
        onDecrease: function(element,value) {}, // On Decrease (down click) event handler
        onChange: function(element,value) {}, // On Change event handler
        min: null, // Minimum value
        max: null, // Maximum value
        maxLength: 10, // Input maxlength
        editable: true, // Make element editable on click
        fixFloat: 2,
        resetToZero: false, // reset if Max
        cut: 0 // Cut text with wh_ud_ellipsis
    };
    var element_exists = $(this).find(".whupdown_element").length;    
    var instance = this; // Element instance
    
    if(!element_exists) {
        $(this).append(whUpDown_code);   
        $(this).data("params", jQuery.extend({},whUpDown_default_properties,i_params) );
    } else
        $(this).data("params", jQuery.extend({},$(this).data("params"),i_params));
    
    var params = $(this).data("params");

    var current_value_buffer = parseFloat(parseFloat(params.value).toFixed(params.fixFloat)); // History buffer, used when input is created     
        
    $(".wh_ud_units",this).text(params.units);
    $(".whupdown_element",this).width(params.width);
    $(".wh_ud_input",this).width(params.width-32); // 32 - buttons width + corrections
    
    /* If cut is need and specified in params */
    if(params.cut) {
        if(current_value_buffer.toString().length > params.cut) {
            var shorten_value = current_value_buffer.toString().substr(current_value_buffer.toString().length - params.cut, params.cut);
            $(".wh_ud_editable_value",instance).addClass("wh_ud_ellipsis").text(shorten_value);
        } else { 
            $(".wh_ud_editable_value",instance).text(current_value_buffer).removeClass("wh_ud_ellipsis");
            }
    } else
        $(".wh_ud_editable_value",instance).text(current_value_buffer);   
    
    /* UP key click */
    $(".wh_ud_increase",this).unbind("click").click(function() {
        var editable_value = $(".wh_ud_editable_value",instance);

        if( (params.max != null && params.max > current_value_buffer) || params.max == null) {
            current_value_buffer++;
            current_value_buffer = parseFloat(current_value_buffer.toFixed(params.fixFloat));
            params.onChange(instance, current_value_buffer);
            params.onIncrease(instance,current_value_buffer);
        } else
            if(params.max <= current_value_buffer && params.resetToZero)
                current_value_buffer = 0;      
        
        if(params.cut) {
            if(current_value_buffer.toString().length > params.cut) {
                $(editable_value).addClass("wh_ud_ellipsis");
                var shorten_value = current_value_buffer.toString().substr(current_value_buffer.toString().length - params.cut, params.cut);
                $(editable_value).text(shorten_value);          
                return;          
            } else
                if(params.max <= current_value_buffer && params.resetToZero)
                   current_value_buffer = 0;    
        }
        $(editable_value).text(current_value_buffer);     
        
    });
    
    /* DOWN key click */
    $(this).find(".wh_ud_decrease").unbind("click").click(function() {
        var editable_value = $(".wh_ud_editable_value",instance);
        
        if( (params.min != null && params.min < current_value_buffer) || params.min == null) { // Check min value
            current_value_buffer--; 
            current_value_buffer = parseFloat(current_value_buffer.toFixed(params.fixFloat));
            params.onChange(instance, current_value_buffer);
            params.onIncrease(instance,current_value_buffer);
        } else
            if(params.min >= current_value_buffer && params.resetToZero)
                current_value_buffer = 0; 
    
        if(params.cut) { // If cut is specified
            if(current_value_buffer.toString().length > params.cut) { // And current string length is less than limit
                $(editable_value).addClass("wh_ud_ellipsis"); // Add ellipsis (...)
                var shorten_value = current_value_buffer.toString().substr(current_value_buffer.toString().length - params.cut, params.cut); // Cut the string
                $(editable_value).text(shorten_value); // And display it, original value is now stored in current_value_buffer_variable
                return;          
            } else // If no cut need
                $(editable_value).removeClass("wh_ud_ellipsis"); // Remove ellipsis (...)
        }
        $(editable_value).text(current_value_buffer); 
        
    });
    
    if(params.editable)
        /* On click, creating input field */
        $(this).find(".wh_ud_editable_value").unbind("click").click(function() {

            if($(this).find("input").size() > 0) // Don't create input field if exists (bug with clicking on transparent input)
                return;
 
            if(params.cut) // If cut was specified
                $(this).removeClass("wh_ud_ellipsis"); // Remove ellipsis (...) at edit time

            $(this).empty().append("<input type='text' class='wh_ud_editable_input'>"); // Create element
            var input_width = $(this).width() - $(instance).find(".wh_ud_units").width() -20    ;           
            
            /* Input text element */
            $(".wh_ud_editable_input",instance)
                .val(current_value_buffer) // Set current value
                .width(input_width) // Set width calculated before
                .attr("maxlength",params.maxLength) 
                .focus() 
                .focusout(function() { // Process typed value on focus out
                
                    var new_value = parseFloat(parseFloat($(this).val(),10).toFixed(params.fixFloat)); // Sometimes users type some shit
                    
                    if(isNaN(new_value) && !isNaN(current_value_buffer))
                        new_value = current_value_buffer; // Restore default value
    
                    else if(isNaN(new_value) && isInt(params.min))
                        new_value = params.min;
                        else if(isNaN(new_value) && isInt(parseFloat(params.value)))
                            new_value = parseFloat(params.value);
                                                

                    if( isInt(new_value) ) // So we need to check it
                        if(isInt(params.max) && new_value > params.max)
                            current_value_buffer = params.max;
                        else if(isInt(params.min) && new_value < params.min)
                                current_value_buffer = params.min;
                            else if(isInt(new_value))
                                current_value_buffer=new_value;
                                        
                    $(".wh_ud_editable_value",instance).text(current_value_buffer);
                    $(".active_input").removeClass("active_input");
                    $(this).remove(); // Remove <input>
                    
                    /* Cut with ellipsis if need (...) */
                    if(params.cut) {
                        if(current_value_buffer.toString().length > params.cut) {
                                $(instance).find(".wh_ud_editable_value").addClass("wh_ud_ellipsis");
                                var new_value = current_value_buffer.toString().substr(current_value_buffer.toString().length - params.cut, params.cut);
                                $(instance).find(".wh_ud_editable_value").text(new_value);                    
                            }
                        else
                            $(instance).find(".wh_ud_editable_value").removeClass("wh_ud_ellipsis");
                    }

                    params.onChange(instance,current_value_buffer);
                
            }).keydown(function(event) { /* Keydown on input element */
                // Allow only backspace, delete, and arrows
                if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 37 || event.keyCode == 39  || event.keyCode == 189 || event.keyCode == 190) {
                    // let it happen, don't do anything
                }
                else {
                    // Ensure that it is a number and stop the keypress
                    if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 ))
                        event.preventDefault(); 
                    if (event.keyCode == 13)
                        $(this).focusout(); 
                    if(event.keyCode == 27)
                        $(this).val("").focusout();
                    
                }
            }).keyup(function(){  
                var new_value = parseFloat($(this).val(),10);           
                if( isInt(new_value) ) // So we need to check it
                    if(isInt(params.max) && new_value > params.max)
                        current_value_buffer = params.max;
                    else if(isInt(params.min) && new_value < params.min)
                            current_value_buffer = params.min;
                        else if(isInt(new_value))
                            current_value_buffer=new_value;
                if(!isInt(new_value))
                    current_value_buffer = params.min;
                params.onChange(instance,current_value_buffer);
            
            });    
            
            $(this).parent().addClass("active_input"); // Add this class to parent of editable value element
            
        });
    
};
})( jQuery );