<?
if ($minorSection) {
	$sectionType = 'minor_section';
	$headerType = 'h3';
	$headlineClass = 'kb-headline-minor';
} else {
	$sectionType = 'section';
	$headerType = 'h2';
	$headlineClass = 'kb-headline';
}

if ($hidden) {
	$hiddenClass = 'kb-disabled';
} else {
	$hiddenClass = '';
}
?>

<div class='<?=$sectionType?> knowledgebox-section sticky <?=$hiddenClass?>' id='knowledgebox' style='display: none;'>
	<<?=$headerType?>>
<?
	if ($altBlock) {
?>
		<div class='altblock'></div>
<?
	}
?>
		<span class='mw-headline <?=$headlineClass?>'><?=$headline?></span>
	</<?=$headerType?>>

<?
	if ($headerLineBreak) {
?>
	<br />
<?
	}
?>
<div class="section_text">
	<div class='kb-row'>
<?
	foreach ($kbBoxes as $kbBox) {
		print "$kbBox\n";
	}
?>
	</div>

	<div class='clearall'></div>

<?
if (isset($kbSubmitSection)) {
	print "$kbSubmitSection\n";
}
?>
	</div>
</div>

