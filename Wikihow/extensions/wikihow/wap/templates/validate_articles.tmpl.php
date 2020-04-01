<? foreach ($urlsByLang as $langCode => $urls) { ?>
<h3 langcode='<?=$langCode?>'>Language: <?=$langCode?></h3>
<div id='<?=$langCode?>'>
<? if (sizeof($urls['invalid'])) { ?>
<h4>Invalid Urls</h4>
<ul class='url_list'>
<?
	foreach ($urls['invalid'] as $url) {
		$url = $url['url'];
		echo "<li>$url</li>";	
	}
?>
</ul>
<? } ?>

<? 
	if (sizeof($urls['excluded'])) { 
		$checked = $buttonId == 'remove_articles' ? "checked='checked'" : "";
?>
<h4><input type='checkbox' id='v_check_excluded' <?=$checked?>/> Urls on exclude list</h4>
<ul class='url_list'>
<?
	foreach ($urls['excluded'] as $url) {
		$aid = $url['aid'];
		$langCode = $url['lang'];
		$url = $url['url'];
		echo "<li><input type='checkbox' value='$aid' langcode='$langCode' class='checked_article v_check_excluded' $checked/> $url</li>";
	}
?>
</ul>
<? } ?>

<? if (sizeof($urls['assigned'])) { ?>
<h4><input type='checkbox' id='v_check_assigned'/> Urls already assigned</h4>
<ul class='url_list'>
<?
	foreach ($urls['assigned'] as $url) {
		$aid = $url['a']->getArticleId();
		$langCode = $url['lang'];
		$ca = $url['a'];
		$url = $url['url'];
		echo "<li><input type='checkbox' value='$aid' langcode='$langCode' class='checked_article v_check_assigned'/> $url - ";
		echo $linker->linkUserByUserText($ca->getUserText()) . "</li>";	
	}
?>
</ul>
<? } ?>

<? if (sizeof($urls['completed'])) { ?>
<h4><input type='checkbox' id='v_check_completed'/> Urls already completed</h4>
<ul class='url_list'>
<?
	foreach ($urls['completed'] as $url) {
		$aid = $url['a']->getArticleId();
		$langCode = $url['lang'];
		$ca = $url['a'];
		$url = $url['url'];
		echo "<li><input type='checkbox' value='$aid' langcode='$langCode' class='checked_article v_check_completed'/> $url - ";
		echo $linker->linkUserByUserText($ca->getUserText()) . "</li>";	
	}
?>
</ul>
<? } ?>
<? if (sizeof($urls['unassigned'])) { ?>
<h4><input type='checkbox' id='v_check_unassigned' checked='checked'/> Urls not assigned</h4>
<ul class='url_list'>
<?
	foreach ($urls['unassigned'] as $url) {
		$aid = $url['a']->getArticleId();
		$langCode = $url['lang'];
		$ca = $url['a'];
		$url = $url['url'];
		echo "<li><input type='checkbox' value='$aid' langcode='$langCode' class='checked_article v_check_unassigned' checked='checked'/> $url</li>";
	}
?>
</ul>
<? } ?>

<? if (sizeof($urls['new'])) {?>
<h4><input type='checkbox' id='v_check_new' checked='checked'/> Urls not yet in <?=$system?></h4>
<ul class='url_list'>
<?
	foreach ($urls['new'] as $url) {
		$aid = $url['aid'];
		$langCode = $url['lang'];
		$url = $url['url'];
		echo "<li><input class='checked_article v_check_new' type='checkbox' value='$aid' langcode='$langCode' checked='checked' /> $url</li>";	
	}
?>
</ul>
<? } 
?>
</div>
<?
 } // end foreach
?>
<div style="margin-top: 10px">
<button id="<?=$buttonId?>" style="padding: 5px;"><?=$buttonTxt?></button>
</div>

