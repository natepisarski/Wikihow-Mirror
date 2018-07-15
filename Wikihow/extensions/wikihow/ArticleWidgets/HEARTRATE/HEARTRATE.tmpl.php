<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Your Target Heart Rate</title>
<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/extensions/wikihow/ArticleWidgets/common/css/style.css,/extensions/wikihow/ArticleWidgets/HEARTRATE/css/styles.css,/extensions/wikihow/ArticleWidgets/components/whUpDown/wh.updown.css,/extensions/wikihow/ArticleWidgets/components/whLabel/wh.label.css&') . WH_SITEREV ?>"; /*]]>*/</style>
<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f/extensions/wikihow/common/jquery-1.4.1.min.js,/extensions/wikihow/ArticleWidgets/components/whUpDown/jq.wh.updown.js,/extensions/wikihow/ArticleWidgets/components/whLabel/jq.wh.label.js,/extensions/wikihow/ArticleWidgets/libs/wh.health.js&') . WH_SITEREV ?>"></script>
</head>
<body>
	<div id="wrapper">
    	<div id="header">
        	<h1>Your Target Heart Rate</h1>
			<div class="corner_left"></div>
			<div class="corner_right"></div>
        </div><!--end header-->
        	<div id="content" class="thr">
            	<div class="tab_container">
    				<div id="tab1" class="tab_content">
        				<div class="left">
                        	<h1>My Personal Info</h1>
                        	<div class="label label_first">Current Age is</div>
                            <div class="form" id="age"></div><!--end form-->
                            <div class="line"></div>
                            
                            <p>Find your resting heart rate<br />
                            as soon as you wake up</p>
                            <div class="float">
                                <div class="lanel">First day</div>
                                <div class="form" id="day1"></div>
                            </div>
                            
                            <div class="float float_2">
                                <div class="lanel">Second day</div>
                                <div class="form" id="day2"></div> 
                            </div>
                           
							<div class="float float_2">	
                                <div class="lanel">Third day</div>
                                <div class="form" id="day3"></div>
                            </div>
                            
                        </div><!--end left-->
                        
                        <div class="right no_result">
                        	<h1>My Target Heart Rate</h1>    
                            <div class="result" id="thr">
                            </div><!--end form-->                       
                        </div><!--end right-->
                     </div><!--end tab_content-->
               </div><!--end tab_container-->
               <div class="more"></div>
               <div class="cop"><span>Powered by</span><a href="http://www.wikihow.com/" title = "wikiHow">wikiHow</a></div>
            </div><!--end content-->
            <div class="bottom"></div>
    


    </div><!--end wrapper-->
</body>
<script type="text/javascript">
var val1 = 0, val2 = 0, val3 = 0, age = 0;

$(function() { 
    $("#thr").whLabel({value : "Please, fill out all required fields on the left side and then you see the result here automatically", lines: 3, startFontSize: 24, fade: false});        
});


$("#age").whUpDown({units:"", width: 91, min: 0, maxLength: 3, max: 99, value: 0, onChange: function(element,value){
    age = value;
	if (isNaN(age)) age = 0;
    if(val1 != 0 && val2 !=0 && val3!=0 && age!=0)
    $("#thr").whLabel({value : target_heart_rate(val1,val2,val3,age), lines: 1, fade: false});
} });

$("#day1").whUpDown({units:"", width: 90, min: 0, maxLength: 3,  max: 300, value: 0, onChange: function(element,value){
    val1 = value;
	if (isNaN(val1)) val1 = 0;
    if(val1 != 0 && val2 !=0 && val3!=0 && age!=0)
    $("#thr").whLabel({value : target_heart_rate(val1,val2,val3,age), lines: 1, fade: false});
} });

$("#day2").whUpDown({units:"", width: 90, min: 0, maxLength: 3, max: 300, value: 0, onChange: function(element,value){
    val2 = value;
	if (isNaN(val2)) val2 = 0;
    if(val1 != 0 && val2 !=0 && val3!=0 && age!=0)
    $("#thr").whLabel({value : target_heart_rate(val1,val2,val3,age), lines: 1, fade: false});
} });

$("#day3").whUpDown({units:"", width: 90, min: 0, maxLength: 3, max: 300, value: 0, onChange: function(element,value){
    val3 = value;
	if (isNaN(val3)) val3 = 0;
    if(val1 != 0 && val2 !=0 && val3!=0 && age!=0)
    $("#thr").whLabel({value : target_heart_rate(val1,val2,val3,age), lines: 1, fade: false});
} });

</script>
</html>
