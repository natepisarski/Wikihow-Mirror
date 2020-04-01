(function($) {

function isInt(n) {
   return typeof n == 'number' && n % 1 == 0;
}


$.fn.whInputText = function(i_params) {
    
    /* Element HTML-code */
    var whInputText_code = '<div class="whinputtext_element"><div class="wh_it_input"><div class="wh_it_editable_value">0</div></div><div>';
    
    /* Default settings */
    var whInputText_default_properties = {
        width: 150, // Width in pixels
        value: 0, // Default value
        onChange: function(element,value) {}, // On Change event handler
        min: 0, // Minimum value
        max: null, // Maximum value
        maxLength: 30, // Input maxlength
        onlyNumber: false,
        onlyBinary: false,
        cut: 0 // Cut text with wh_it_ellipsis
    };

    var params = jQuery.extend({},whInputText_default_properties,i_params);
    var instance = this; // Element instance
    var current_value_buffer = params.value; // History buffer, used when input is created
    
    $(this).append(whInputText_code);    
    $(".whinputtext_element",this).width(params.width);
    $(".wh_it_input",this).width(params.width);
    $(".wh_it_editable_value",this).text(params.value);
    

    /* On click, creating input field */
    $(this).find(".wh_it_editable_value").click(function() {
    
        if($(this).find("input").size() > 0) // Don't create input field if exists (bug with clicking on transparent input)
            return;

        if(params.cut) // If cut was specified
            $(this).removeClass("wh_it_ellipsis"); // Remove ellipsis (...) at edit time

        $(this).empty().append("<input type='text' class='wh_it_editable_input'>"); // Create element
        var input_width = $(this).width() - $(instance).find(".wh_it_units").width() - 10;           
        
        /* Input text element */
        $(".wh_it_editable_input",instance)
            .val(current_value_buffer) // Set current value
            .width(input_width) // Set width calculated before
            .attr("maxlength",params.maxLength) 
            .focus() 
            .focusout(function() { // Process typed value on focus out
                if(params.onlyNumber || params.onlyBinary) { // If field is protected to use only digits or binary (1 and 0)    
                    var new_value = parseInt($(this).val(),10); // Sometimes users type some shit
                    if( isInt(new_value) ) // So we need to check it
                        if(isInt(params.max) && new_value > params.max)
                            current_value_buffer = params.max;
                        else if(isInt(params.min) && new_value < params.min)
                                current_value_buffer = params.min;
                            else if(isInt(new_value))
                                current_value_buffer=new_value;
                } else
                    current_value_buffer = $(this).val();
                
                $(".wh_it_editable_value",instance).text(current_value_buffer);
                $(".active_input").removeClass("active_input");
                $(this).remove();
                
                /* Cut with ellipsis if need (...) */
                if(params.cut) {
                    if(current_value_buffer.toString().length > params.cut) {
                            $(instance).find(".wh_it_editable_value").addClass("wh_it_ellipsis");
                            var new_value = current_value_buffer.toString().substr(current_value_buffer.toString().length - params.cut, params.cut);
                            $(instance).find(".wh_it_editable_value").text(new_value);                    
                        }
                    else
                        $(instance).find(".wh_it_editable_value").removeClass("wh_it_ellipsis");
                }
                
                params.onChange(instance,current_value_buffer);
            
        }).keydown(function(event) { /* Keydown on input element */

            // Allow only backspace, delete, and arrows
            if ( event.keyCode == 46 || event.keyCode == 8 || event.keyCode == 37 || event.keyCode == 39  || event.keyCode == 189 ) {
                // let it happen, don't do anything
            }
            else {
                // Ensure that it is a number and stop the keypress
                if (params.onlyNumber)
                if ((event.keyCode < 48 || event.keyCode > 57) && (event.keyCode < 96 || event.keyCode > 105 ))
                    event.preventDefault(); 
                if (params.onlyBinary)
                    if ((event.keyCode == 48 || event.keyCode == 49  || event.keyCode == 97  || event.keyCode == 96)) {
                           //ok             
                    }
                    else
                        event.preventDefault();
                if (event.keyCode == 13)
                    $(this).focusout(); 
                if(event.keyCode == 27)
                    $(this).val("").focusout();
                
            }
        }).keyup(function(){  
            if($(this).val() != "")
                params.onChange(instance,$(this).val());
        });    
        
        $(this).parent().addClass("active_input"); // Add this class to parent of editable value element
        
    });
    
};
})( jQuery );