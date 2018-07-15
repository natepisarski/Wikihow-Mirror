<div id='ac_method_selector'>
		    This article has: 
			<input type="radio" name="method_format" value="methods"/> Multiple Methods <a class='ac_question methods'></a>
			<input type="radio" name="method_format" value="parts"/> Multiple Parts <a class='ac_question parts'></a>
			<input type="radio" name="method_format" value="neither" checked="checked" /> Neither <a class='ac_question neither'></a>
</div>

<div id='steps' class='stepssection steps'>
	<ul class='ac_methods'></ul> 
</div>
<? 
if (strlen($pageTitle) > 30) {
	$substrLen = $pageTitle[30] == " " ? 34 : 35;
	$pageTitle = substr($pageTitle, 0, $substrLen) . "...";	
}
?>
<div id='ac_add_new_method' class="section">
	<span id='ac_method_selector_text'><?=$methodSelectorText?></span> <span class='ac_article_title'><?=$pageTitle?></span>? <a class='button primary ac_add_new_method'><?=$addMethodButtonTxt?></a>
</div>