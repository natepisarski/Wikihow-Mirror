<?  if (empty($tags)) { ?>
	<h4>Tags successfully removed.</h4>
<? } else { ?>
    The following tags were not removed due to article still being assigned: <?=implode(", ", $linker->linkTags($tags))?>
<? } ?>

