<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>How Many Calories You Need to Eat to Lose Weight</title>
<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/extensions/wikihow/ArticleWidgets/common/css/style.css,/extensions/wikihow/ArticleWidgets/CALORIES/css/styles.css,/extensions/wikihow/ArticleWidgets/components/whLabel/wh.label.css&') . WH_SITEREV ?>"; /*]]>*/</style>
<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f/extensions/wikihow/common/jquery-1.4.1.min.js,/extensions/wikihow/ArticleWidgets/common/js/tabs.js,/extensions/wikihow/ArticleWidgets/components/whUpDown/jq.wh.updown.js,/extensions/wikihow/ArticleWidgets/components/whDropDown/jq.wh.dropdown.js,/extensions/wikihow/ArticleWidgets/components/whLabel/jq.wh.label.js,/extensions/wikihow/ArticleWidgets/libs/wh.health.js&') . WH_SITEREV ?>"></script>
</head>
<body>
	<div id="wrapper">
    	<div id="header">
        	<h1>How Many Calories You Need to <br />Eat to Lose Weight</h1>
			<div class="corner_left"></div>
			<div class="corner_right"></div>
            <ul class="tabs">
                <li><a href="#tab1"><p>Imperial</p></a></li>
                <li><a href="#tab2"><p>Metric</p></a></li>
            </ul>        	
            
        </div><!--end header-->
        	<div id="content">
            	<div class="tab_container">
    				<div id="tab1" class="tab_content">
        				<div class="left">
                        	<h1>My Goal</h1>
                        	<div class="label">I would like to lose weight by about:</div>
                            <div class="form" id="target"></div><!--end form-->
                            <div class="line"></div>
                        	<h1>My Personal Info</h1>
							
                            <div class="float">
                                <div class="lanel">My gender is:</div>
                                <div class="form" id="gender"></div>
                            </div><!--end float-->
                            
                            <div class="float float_2">
                                <div class="lanel">Current age is:</div>
                                <div class="form" id="age"></div> 
                            </div><!--end float--> 
                            
                            
                            <div class="float float_2">
                                <div class="lanel">Activity:</div>
                                <div class="form" id="activity"></div> 
                            </div><!--end float-->
                            
                            
                            <div class="float">
                                <div class="lanel">My weight is:</div>
                                <div class="form" id="weight"></div>  
                            </div><!--end float-->  
                            
                            <div class="float float_3">
                                <div class="lanel">I measured my height:</div>
                                <div class="form" id="height_ft"></div>
								<div class="form" id="height_in"></div>
                            </div><!--end float-->

                        </div><!--end left-->
                        
                        <div class="right no_result">
                        	<h1>I need to eat</h1>    
                            <div class="result" id="calories"></div>
                            <div class="addon" id="addon"></div>
                            <!--end form-->                       
                        </div><!--end right-->
                     </div><!--end tab_content-->
                     
                     <div id="tab2" class="tab_content">
                     
                     
                     <div class="left">
                                             	<h1>My Goal</h1>
                                             	<div class="label">I would like to lose weight by about:</div>
                                                 <div class="form" id="target_si"></div><!--end form-->
                                                 <div class="line"></div>
                                             	<h1>My Personal Info</h1>
                     							
                                                 <div class="float">
                                                     <div class="lanel">My gender is:</div>
                                                     <div class="form" id="gender_si"></div>
                                                 </div><!--end float-->
                                                 
                                                 <div class="float float_2">
                                                     <div class="lanel">Current age is:</div>
                                                     <div class="form" id="age_si"></div> 
                                                 </div><!--end float--> 
                                                 
                                                 
                                                 <div class="float float_2">
                                                     <div class="lanel">Activity:</div>
                                                     <div class="form" id="activity_si"></div> 
                                                 </div><!--end float-->
                                                 
                                                 
                                                 <div class="float">
                                                     <div class="lanel">My weight is:</div>
                                                     <div class="form" id="weight_si"></div>  
                                                 </div><!--end float-->  
                                                 
                                                 <div class="float float_3">
                                                     <div class="lanel">I measured my height:</div>
                                                     <div class="form" id="height_m"></div>
                     								<div class="form" id="height_cm"></div>
                                                 </div><!--end float-->
                     
                                             </div><!--end left-->
                                             
                                             <div class="right no_result">
                                             	<h1>I need to eat</h1>    
                                                 <div class="result" id="calories_si"></div>
                                                 <div class="addon" id="addon_si"></div>
                                                 <!--end form-->                       
                                             </div><!--end right-->
                     </div>
                     
               </div><!--end tab_container-->
               <div class="more"></div>
               <div class="cop"><span>Powered by</span><a href="http://www.wikihow.com/" title = "wikiHow">wikiHow</a></div>
            </div><!--end content-->
            <div class="bottom"></div>
    


    </div><!--end wrapper-->

<script type="text/javascript">
var w = 0, ft = 0, inch = 0, target = 1, age = 0, gender = -1, activity = 1, m = 0, cm = 0;

function show_addon(lb) {
    $("#addon").text("calories per day to lose "+lb+"lb/wk");
}

function show_addon_si(g) {
    g = g *500;
    $("#addon_si").text("calories per day to lose "+g+"g/wk");
}

function remove_help_si() {
    $(".addon").show();
    $("#calories_si").empty();
}

function remove_help() {
    $(".addon").show();
    $("#calories").empty();
}

function calc_uk() {

    if( w!= 0 && ft != 0 && target != 0 && age != 0 && activity != 0 && gender != -1 && activity != 1) {
        remove_help();
        $("#calories").removeClass("hint").empty().whLabel({value:calories(w,ft,inch,age,gender,activity,target), fade: false, startFontSize:40, width:130,  lines: 1 });
        return;
    }
    
    $("#calories").addClass("hint").empty().whLabel({value:"Please, fill out all required fields on the left side and then you see the result here automatically", fade: false, lines: 6, startFontSize:24, width:130 });
    
}

function calc_si() {

    if( w!= 0 && m != 0 && target != 0 && age != 0 && activity != 0 && gender != -1 && activity != 1) {
        remove_help_si();
        $("#calories_si").removeClass("hint").empty().whLabel({value:calories(w,m,cm,age,gender,activity,target), fade: false, startFontSize:40, width:130, lines: 1 });
        return;
    }
    
    $("#calories_si").addClass("hint").empty().whLabel({value:"Please, fill out all required fields on the left side and then you see the result here automatically", fade: false, lines: 6, startFontSize:24, width:130 });
    
}

function tabs_switch() {


$("#calories_si").addClass("hint").empty().whLabel({value:"Please, fill out all required fields on the left side and then you see the result here automatically", fade: false, lines: 6, startFontSize:24, width:130 });
$("#calories").addClass("hint").empty().whLabel({value:"Please, fill out all required fields on the left side and then you see the result here automatically", fade: false, lines: 6, startFontSize:24, width:130 });
$(".addon").hide();


w = 0,ft = 0,inch = 0, target = 1, age = 0, gender = -1, activity = 1, m = 0, cm = 0;
show_addon(1);
show_addon_si(1);

$("#weight").empty().whUpDown({units:"lb", width: 112, maxLength:  3, min: 0, max: 300, value: 0, onChange: function(element,value){
    w = value;
    calc_uk();
} });

$("#height_ft").empty().whUpDown({units:"ft", width: 112, maxLength:  3, min: 0, max: 100, value: 0, onChange: function(element,value){
    ft = value;
    calc_uk();
} });

$("#height_in").empty().whUpDown({units:"in", width: 112, maxLength:  3, min: 0, max: 16, value: 0, onChange: function(element,value){
    
    
    if(value >= 16) {
        ft++;
        inch=0;
        $("#height_ft").whUpDown({ "value" : ft});
        $("#height_in").empty().whUpDown({units:"in", "value" : inch,width: 112, maxLength:  3, min: 0, max: 16});
        calc_uk();
        return;
    }
    inch = value;
    calc_uk();
} });


$("#age").empty().whUpDown({units:"yr", width: 112, min: 0, maxLength:  3, max: 99, value: 0, onChange: function(element,value){
    age = value;
    calc_uk();
} });

$("#target").empty().whDropDown({width:65, the_default: 1, options: { "1" : "1", "2" : "2", "3" : "3" }, onChange: function(element,value){
    target = value;
    show_addon(target);
    calc_uk();
} });

$("#gender").empty().whDropDown({width: 102, the_default: "Select", options: {"0" : "Female", "1" : "Male"}, onChange : function(element,value) {
    gender = value;
    calc_uk(); 
}});

$("#activity").empty().whDropDown({width: 102, the_default: "Select", options: {"1.2" : "Sitting", "1.375" : "Lightly", "1.55" : "Gently","1.725" : "Very", "1.9" : "Extra" }, onChange : function(element,value) {
    activity = value;
    calc_uk();    
}});












$("#weight_si").empty().whUpDown({units:"kg", width: 112, maxLength:  3, min: 0, max: 300, value: 0, onChange: function(element,value){
    w = value;
    calc_si();
} });

$("#height_m").empty().whUpDown({units:"m", width: 112, maxLength:  3, min: 0, max: 100, value: 0, onChange: function(element,value){
    m = value;
    calc_si();
} });

$("#height_cm").empty().whUpDown({units:"cm", width: 112, maxLength:  3, min: 0, max: 99, value: 0, onChange: function(element,value){
    cm = value;
    calc_si();
} });


$("#age_si").empty().whUpDown({units:"yr", width: 112, min: 0, maxLength:  3, max: 99, value: 0, onChange: function(element,value){
    age = value;
    calc_si();
} });

$("#target_si").empty().whDropDown({ width:65, the_default: 1, options: { "1" : "1", "2" : "2", "3" : "3" }, onChange: function(element,value){
    target = value;
    show_addon_si(parseInt(target));
    calc_si();
} });

$("#gender_si").empty().whDropDown({width: 102, the_default: "Select", options: { "0" : "Female", "1" : "Male"}, onChange : function(element,value) {
    gender = value;
    calc_si();   
}});

$("#activity_si").empty().whDropDown({width: 102, the_default: "Select", options: { "1.2" : "Sitting", "1.375" : "Lightly", "1.55" : "Gently","1.725" : "Very", "1.9" : "Extra" }, onChange : function(element,value) {
    activity = value;
    calc_si();   
}});


}

$(function() {
tabs_switch();
});

</script>
    
</body>

</html>
