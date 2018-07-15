<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Percentages</title>
<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/extensions/wikihow/ArticleWidgets/common/css/style.css,/extensions/wikihow/ArticleWidgets/PERCENT/css/styles.css,/extensions/wikihow/ArticleWidgets/components/whUpDown/wh.updown.css,/extensions/wikihow/ArticleWidgets/components/whLabel/wh.label.css&') . WH_SITEREV ?>"; /*]]>*/</style>
<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f/extensions/wikihow/common/jquery-1.4.1.min.js,/extensions/wikihow/ArticleWidgets/common/js/tabs.js,/extensions/wikihow/ArticleWidgets/components/whUpDown/jq.wh.updown.js,/extensions/wikihow/ArticleWidgets/components/whLabel/jq.wh.label.js,/extensions/wikihow/ArticleWidgets/libs/wh.math.js&') . WH_SITEREV ?>"></script>
</head>
<body>
	<div id="wrapper">
    	<div id="header">
        	<h1>Percentages</h1>
			<div class="corner_left"></div>
			<div class="corner_right"></div>
        </div><!--end header-->
        	<div id="content" class="percentages">
            	<div class="tab_container">
    				<div id="tab1" class="tab_content">
        				<div class="left">
                        	<h1>Details</h1>
                        	<div class="label">Part</div>
                            <div class="form" id="part"></div>
                            
                            <div class="label">Total</div>
                            <div class="form" id="total"></div>
                        </div><!--end left-->
                        
                        <div class="right no_result">
                        	<h1>Percentages</h1>
                            <div id="result"></div>
                            
                        </div><!--end right-->
                     </div><!--end tab_content-->
               </div><!--end tab_container-->
               <div class="more"></div>
               <div class="cop"><span>Powered by</span><a href="http://www.wikihow.com/" title = "wikiHow">wikiHow</a></div>
            </div><!--end content-->
            <div class="bottom"></div>
    


    </div><!--end wrapper-->
 
 <script>
 var total = 0;
 var part = 0;
 
 $(function() {
    $("#result").empty().whLabel({value : "Please enter a decimal number (eg. 10) in both fields", lines: 2, fade: false, startFontSize: 16});        
 });
 
 
 $("#part").whUpDown({units:"", width:183, value: 0, maxLength: 16, cut: 10, onChange: function(element,value){
     part = value;
     var perc = percentage(total, value);
     if(isFinite(perc))
         $("#result").whLabel({value : perc + "%", lines: 1, fade: false});
 } });
 
 $("#total").whUpDown({units:"", width: 183, value: 0, maxLength: 16, cut: 10, onChange: function(element,value){
    total = value;
    var perc = percentage(value, part);
    if(isFinite(perc))
         $("#result").whLabel({value : perc + "%", lines: 1, fade: false});
 } });
 
 </script>   
    
</body>
</html>
