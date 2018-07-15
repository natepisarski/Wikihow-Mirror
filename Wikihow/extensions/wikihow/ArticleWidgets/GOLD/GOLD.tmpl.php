<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>The Value of Scrap Gold</title>
<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/extensions/wikihow/ArticleWidgets/common/css/style.css,/extensions/wikihow/ArticleWidgets/GOLD/css/styles.css,/extensions/wikihow/ArticleWidgets/components/whUpDown/wh.updown.css,/extensions/wikihow/ArticleWidgets/components/whDropDown/wh.dropdown.css,/extensions/wikihow/ArticleWidgets/components/whLabel/wh.label.css&') . WH_SITEREV ?>"; /*]]>*/</style>
<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f/extensions/wikihow/common/jquery-1.4.1.min.js,/extensions/wikihow/ArticleWidgets/common/js/tabs.js,/extensions/wikihow/ArticleWidgets/components/whUpDown/jq.wh.updown.js,/extensions/wikihow/ArticleWidgets/components/whDropDown/jq.wh.dropdown.js,/extensions/wikihow/ArticleWidgets/components/whLabel/jq.wh.label.js,/extensions/wikihow/ArticleWidgets/libs/wh.finance.js&') . WH_SITEREV ?>"></script>
</head>
<body>
	<div id="wrapper">
    	<div id="header">
        	<h1>The Value of Scrap Gold</h1>
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
                        	<h1>Details</h1>
                        	<div class="label label_2">Gold Karats</div>
                            <div class="form" id="karat_uk"></div>
                            
                            <div class="label">Pounds</div>
                            <div class="form" id="lbs"></div>
                            
                            <div class="label">Ounces</div>
                            <div class="form" id="oz"></div>
                            
                        </div><!--end left-->
                        
                        <div class="right no_result">
                        	<h1>Value</h1>    
                            <div class="result" id="result_uk"></div>
                                      
                        </div><!--end right-->
                        
               <div class="more"></div>
                     </div><!--end tab_content-->
                     
                     <div id="tab2" class="tab_content">
        				<div class="left">
                        	<h1>Details</h1>
                                <div class="label label_2">Gold Karats</div>
                                <div class="form" id="karat_si"></div>
                                
                                <div class="label">Grams</div>
                                <div class="form" id="gr"></div>
                        </div><!--end left-->
                        
                        <div class="right no_result">
                        	<h1>Value</h1>    
                        	<div class="result" id="result_si"></div>                      
                        </div><!--end right-->
                        
                       
               <div class="more more_2"></div>
    				</div><!--end tab_content-->
                     
               </div><!--end tab_container-->
               <div class="cop"><span>Powered by</span><a href="http://www.wikihow.com/" title = "wikiHow">wikiHow</a></div>
            </div><!--end content-->
            <div class="bottom"></div>
    


    </div><!--end wrapper-->

<script type="text/javascript">

function calc_uk(lbs,oz,karat) {
    if(oz <= 0 && lbs <=0){
        $("#result_si,#result_uk").empty().whLabel({value : "Please, fill out all required fields on the left side and then you see the result here automatically", lines: 4, fade: false, startFontSize: 16});
        return;
    }
    var w = lbs * 453.5 + oz * 28.3;
    $("#result_uk").empty().whLabel({value : "$" + parseInt(scrap_gold_value(w,karat)).toString(), lines: 1, fade: false, startFontSize: 30});
}

function calc_si(weight,karat) {
    if(weight <= 0) {
        $("#result_si,#result_uk").empty().whLabel({value : "Please, fill out all required fields on the left side and then you see the result here automatically", lines: 4, fade: false, startFontSize: 16});
        return;
    }
    $("#result_si").empty().whLabel({value : "$" + parseInt(scrap_gold_value(weight,karat)).toString(), lines: 1, fade: false, startFontSize: 30});
}

function reset_values() {
	lbs = 0;
	oz = 0;
	karat_uk_v = 10;
	karat_si_v = 10;
	gr = 0;
}


function tabs_switch(a) {

		reset_values();

        $("#lbs").empty().whUpDown({units:"lbs", width: 150, value: 0, maxLength: 4, min: 0, cut: 4, fixFloat: 1, onChange: function(element,value){
            lbs = value;
            calc_uk(lbs,oz,karat_uk_v);
        } });
        
        $("#oz").empty().whUpDown({units:"oz", width: 150, value: 0, min: 0, max: 16, maxLength: 4, cut: 4, fixFloat: 1, resetToZero: true, onChange: function(element,value){

            if(value >= 16) {
                console.log(">16");
                lbs++;
                oz=0;
                $("#lbs").whUpDown({ "value" : lbs});
                calc_uk(lbs,oz,karat_uk_v);
                return;
            }
            oz = value;
            calc_uk(lbs,oz,karat_uk_v);
        } });
        
        $("#karat_uk").empty().whDropDown({width: 120, options: { "10" : "10k", "14" : "14k", "18" : "18k" }, the_default: "10k", onChange: function(element,value){
            karat_uk_v = value;
            calc_uk(lbs,oz,karat_uk_v);
        
        }});

        $("#karat_si").empty().whDropDown({width: 120, options: { "10" : "10k", "14" : "14k", "18" : "18k" }, the_default: "10k", onChange: function(element,value){
            karat_si_v = value;
            calc_si(gr,karat_si_v);
        
        }});

        $("#gr").empty().whUpDown({units:"gr", width: 150, value: 0, maxLength: 4, min: 0, cut: 4, fixFloat: 1, onChange: function(element,value){
            gr = value;
            calc_si(gr,karat_si_v);
        } });
        
        $("#result_si,#result_uk").empty().whLabel({value : "Please, fill out all required fields on the left side and then you see the result here automatically", lines: 4, fade: false, startFontSize: 16});
                
        $("#tab1").animate({opacity: 1, duration: 300});
}



var lbs = 0;
var oz = 0;
var karat_uk_v = 10, karat_si_v = 10;
var gr = 0;

$(function() {

    $.getJSON("http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20yahoo.finance.quotes%20where%20symbol%20in%20(%22GLD%22)&env=http://datatables.org/alltables.env&format=json&callback=?",function(data){
        gold_quote=data.query.results.quote.Bid*10;

        tabs_switch(null);
        $("#result_si, #result_uk").empty().whLabel({value : "Please, fill out all required fields on the left side and then you see the result here automatically", lines: 4, fade: false, startFontSize: 16});
    });

});


$("*").mousedown(function() {

console.log(this);

});

</script>    

</body>
</html>
