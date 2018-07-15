<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Convert from Decimal to Binary</title>
<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/extensions/wikihow/ArticleWidgets/common/css/style.css,/extensions/wikihow/ArticleWidgets/DECTOBIN/css/styles.css,/extensions/wikihow/ArticleWidgets/components/whInputText/wh.inputtext.css,/extensions/wikihow/ArticleWidgets/components/whLabel/wh.label.css&') . WH_SITEREV ?>"; /*]]>*/</style>
<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f/extensions/wikihow/common/jquery-1.4.1.min.js,/extensions/wikihow/ArticleWidgets/common/js/tabs.js,/extensions/wikihow/ArticleWidgets/components/whInputText/jq.wh.inputtext.js,/extensions/wikihow/ArticleWidgets/components/whLabel/jq.wh.label.js,/extensions/wikihow/ArticleWidgets/libs/wh.math.js&') . WH_SITEREV ?>"></script>
</head>
<body>
	<div id="wrapper">
    	<div id="header">
        	<h1>Convert from Decimal to Binary</h1>
			<div class="corner_left"></div>
			<div class="corner_right"></div>
        </div><!--end header-->
        	<div id="content">
            	<div class="tab_container">
    				<div id="tab1" class="tab_content">
        				<div class="left">
                        	<h1>Decimal</h1>
                            <div class="form" id="dec">
                            </div><!--end form-->
                        </div><!--end left-->
                        
                        <div class="right no_result">
                        	<h1>Binary</h1>    
                            <div id="bin">
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

    $(function() { 
        $("#bin").whLabel({value : "Please enter a decimal number (eg. 2502)", lines: 2, fade: false});        
    });

    // From 0 to Inf.
    $("#dec").whInputText({units:"", width: 220, min: 0, value:0, onlyNumber: true, maxLength: 15, cut: 33, onChange: function(instance,value) {
        $("#bin").whLabel({value : dec2bin(value), lines: 2, fade: false, startFontSize: 24, onChange: function(element,new_fs) {
            if($(element).find(".whlabel_element").height() == 24)
                $(element).css("margin-top",20);
            if($(element).find(".whlabel_element").height() == 48)
                $(element).css("margin-top",8);   
            if($(element).find(".whlabel_element").height() == 72)
                $(element).css("margin-top",14);
        }});        
    }});
    
    

</script>

</html>
