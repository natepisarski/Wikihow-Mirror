<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>The Circumference of a Circle</title>
<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/extensions/wikihow/ArticleWidgets/common/css/style.css,/extensions/wikihow/ArticleWidgets/CIRCLECIRCUM/css/styles.css,/extensions/wikihow/ArticleWidgets/components/whUpDownDropDown/wh.updowndropdown.css,/extensions/wikihow/ArticleWidgets/components/whLabel/wh.label.css&') . WH_SITEREV ?>"; /*]]>*/</style>
<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f/extensions/wikihow/common/jquery-1.4.1.min.js,/extensions/wikihow/ArticleWidgets/common/js/tabs.js,/extensions/wikihow/ArticleWidgets/components/whUpDownDropDown/jq.wh.updowndropdown.js,/extensions/wikihow/ArticleWidgets/components/whLabel/jq.wh.label.js,/extensions/wikihow/ArticleWidgets/libs/wh.geometry.js&') . WH_SITEREV ?>"></script>
</head>
<body>
	<div id="wrapper">
    	<div id="header">
        	<h1>The Circumference of a Circle</h1>
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
                        	<h1>Radius</h1>
                            <div class="form form_uk" id="uk">
                            </div><!--end form-->
                        </div><!--end left-->
                        
                        <div class="right no_result">
                        	<h1>Circumference</h1>
                            <div class="result" id="result_uk">0 ft²
                            </div><!--end form-->                       
                        </div><!--end right-->
                     </div><!--end tab_content-->
                     
                     <div id="tab2" class="tab_content">
        				<div class="left">
                        	<h1>Radius</h1>
                            <div class="form form_uk form_si" id="si">
                            </div><!--end form-->
                        </div><!--end left-->
                        
                        <div class="right no_result">
                        	<h1>Circumference</h1>    
                            <div class="result" id="result_si">0 ft²
                            </div><!--end form-->                       
                        </div><!--end right-->
                        
                        
                        
    				</div><!--end tab_content-->
                     
               </div><!--end tab_container-->
               <div class="more"></div>
               <div class="cop"><span>Powered by</span><a href="http://www.wikihow.com/" title = "wikiHow">wikiHow</a></div>
            </div><!--end content-->
            <div class="bottom"></div>
    


    </div><!--end wrapper-->
 <script type="text/javascript">

$(function() {
    $("#result_uk,#result_si").empty().whLabel({value : "Please enter a decimal number (eg. 10)", lines: 2, fade: false, startFontSize: 16});        
});

function tabs_switch(a) {

    $("#result_uk,#result_si").empty().whLabel({value : "Please enter a decimal number (eg. 10)", lines: 2, fade: false, startFontSize: 16});        
 
    $("#si").empty().whUpDownDropDown({units:"", width: 243, min: 0, value: 0, cut: 8, maxLength: 20, options : { "1" : "mm", "10" : "cm", "1000" : "m"}, dropDownDefault: 1, onChange: function(element, val, opt) { 
        var i = { "1" : "mm²" , "10" : "cm²", "1000" : "m²"};
        $("#result_si").whLabel({value : circle_circumference(val) + " " + i[opt],lines: 1, fade: false, startFontSize: 24}); 
        }, onDropDownChange : function(element, val, opt) { 
        var i = { "1" : "mm²" , "10" : "cm²", "1000" : "m²"};
        $("#result_si").whLabel({value : circle_circumference(val) + " " + i[opt],lines: 1, fade: false, startFontSize: 24}); 
    }});
    
    
    
    $("#uk").empty().whUpDownDropDown({units:"", width: 243, min: 0, value: 0, cut: 8, maxLength: 20, options : { "1" : "ft", "10" : "in"}, dropDownDefault: 1, onChange: function(element, val, opt) { 
        var i = { "1" : "ft²" , "10" : "in²"};
        $("#result_uk").whLabel({value : circle_circumference(val) + " " + i[opt],lines: 1, fade: false, startFontSize: 24}); 
        }, onDropDownChange : function(element, val, opt) { 
        var i = { "1" : "ft²" , "10" : "in²"};
        $("#result_uk").whLabel({value : circle_circumference(val) + " " + i[opt],lines: 1, fade: false, startFontSize: 24}); 
        }});

 
} 
tabs_switch(null);
 </script>   
    
</body>
</html>
