<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Body Mass Index Widget (BMI)</title>
<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/extensions/wikihow/ArticleWidgets/BMI/css/style.css&') . WH_SITEREV ?>"; /*]]>*/</style>
<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f/extensions/wikihow/common/jquery-1.4.1.min.js,/extensions/wikihow/ArticleWidgets/BMI/js/script.js&') . WH_SITEREV ?>"></script>
</head>
<body scroll="no" style="overflow:hidden;">
<div id="wrapper">
  <div id="header">
    <ul class="tabs">
      <li><a href="#tab1">
        <p>Imperial</p>
        </a></li>
      <li><a href="#tab2">
        <p>Metric</p>
        </a></li>
    </ul>
	<div id="header_text">BMI Calculator</div>
  </div>
  <!--end header-->
  <div id="content">
    <div class="tab_container">
      <div id="tab1" class="tab_content">
        <div class="left">
          <h1>1. My Personal Info</h1>
          <!--h2>Please, fill out all required fields about you</h2-->
          <div class="form">
            <p class="first">Current weight is:</p>
            <div class="form_input">
              <div class="input"> <span class="si">lbs</span>
                <div class="editable_div" id="weight_uk">0</div>
              </div>
              <!--end input--> 
              <a class="more"></a> <a class="small"></a> </div>
            <!--end form_input-->
            <p class="first tho">Current<br />
              height is:</p>
            <div class="form_input second">
              <div class="input"> <span class="si">ft</span>
                <div class="editable_div" id="height_uk_ft">0</div>
              </div>
              <!--end input--> 
              <a class="more"></a> <a class="small"></a> </div>
            <!--end form_input-->
            <div class="form_input second">
              <div class="input"> <span class="si custom_in">in</span>
                <div class="editable_div" id="height_uk_in">0</div>
              </div>
              <!--end input--> 
              <a class="more"></a> <a class="small"></a> </div>
            <!--end form_input--> 
          </div>
          <!--end form--> 
        </div>
        <!--end left-->
        <div class="line"></div>
        <div class="right no_result">
          <h1>2. My Body Mass Index</h1>
          <!--h2>The result is calculated according to filled info</h2-->
          <!--p>Please, fill out all required fields on the left side and then you see the result here automatically</p-->
		  <p>Fill out the fields on the left and then you will see your result here.</p>
        </div>
        <!--end right-->
        <div class="right result_set">
          <h1>2. My Body Mass Index</h1>
          <!--h2>The result is calculated according to filled info</h2-->
          <div class="scale">24.3</div>
          <div class="about_scale">the <span>NORMAL</span> weight range</div>
          <div class="ruler">
            <div class="i_m" style="bottom:10px; left:-8px;"> I’m here </div>
            <!--end i_m--> 
            <img src="/extensions/wikihow/ArticleWidgets/BMI/images/ruler.png" width="264" height="20" alt="ruler" class="quimby_search_image"> </div>
          <!--end ruler--> 
        </div>
        <!--end right--> 
      </div>
      <!--end tab_content-->
      <div id="tab2" class="tab_content">
        <div class="left">
          <h1>1. My Personal Info</h1>
          <!--h2>Please, fill out all required fields about you</h2-->
          <div class="form">
            <p class="first">Current weight is:</p>
            <div class="form_input">
              <div class="input"> <span class="si">kg</span>
                <div class="editable_div" id="weight_kg">0</div>
              </div>
              <!--end input--> 
              <a class="more"></a> <a class="small"></a> </div>
            <!--end form_input-->
            <p class="first second second_kg second_kg_hs">Current height is:</p>
            <div class="form_input">
              <div class="input"> <span class="si">cm</span>
                <div class="editable_div" id="height_cm">0</div>
              </div>
              <!--end input--> 
              <a class="more"></a> <a class="small"></a> </div>
            <!--end form_input--> 
          </div>
          <!--end form--> 
        </div>
        <!--end left-->
        <div class="line"></div>
        <div class="right no_result">
          <h1>2. My Body Mass Index</h1>
          <!--h2>The result is calculated according to filled info</h2-->
          <!--p>Please, fill out all required fields on the left side and then you see the result here automatically</p-->
		  <p>Fill out the fields on the left and then you will see your result here.</p>
        </div>
        <!--end right-->
        <div class="right result_set">
          <h1>2. My Body Mass Index</h1>
          <!--h2>The result is calculated according to filled info</h2-->
          <div class="scale">24.3</div>
          <div class="about_scale">the <span>NORMAL</span> weight range</div>
          <div class="ruler">
            <div class="i_m" style="bottom:10px; left:-8px;"> I’m here </div>
            <!--end i_m--> 
            <img src="/extensions/wikihow/ArticleWidgets/BMI/images/ruler.png" width="264" height="20" alt="ruler" class="quimby_search_image"> </div>
          <!--end ruler--> 
        </div>
        <!--end right--> 
      </div>
      <!--end tab_content--> 
    </div>
    <!--end tab_container--> 
	<div id="powered_by">Powered by <img src="/skins/WikiHow/images/wikihow_65.png" /></div>
  </div>
  <!--end content-->
</div>
<!--end wrapper-->
</body>
</html>