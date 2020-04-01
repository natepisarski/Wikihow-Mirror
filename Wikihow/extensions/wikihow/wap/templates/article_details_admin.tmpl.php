<h3>Language: <?=$lang?></h3>
<? if (!$article || !$article->exists()) {?>
	<h4>Article doesn't exist in the system</h4>
<? } else { ?>
	<div style="margin-top: 10px">
	Article Details for: <?= Html::element('a', ['href' => $article->getUrl(), 'target' => '_blank'], $article->getUrl()) ?>
	<ul>
	<?
		$assignedLink = empty($user) ? "Not assigned" : $linker->linkUser($user);
		$assignedOn = empty($user) ? "N/A" : $article->getReservedDate();
		$completedOn = $article->isCompleted() ?  $article->getCompletedDate() : "Not completed";
	?>
	<li>Id: <?=$article->getPageId()?></li>
	<li>Language Code: <?=$article->getLangCode()?></li>
	<li>Assigned to: <?=$assignedLink?></li>
	<li>Assigned on: <?=$assignedOn?></li>
	<li>Completed on: <?=$completedOn?></li>
	<li>Notes: <?=$article->getNotes()?></li>
	<?
	echo "<li>Tags: ";
	if (!empty($tags)) {
		echo implode(", ", $linker->linkTags($tags));
	} else {
		echo "No tags";
	}
	echo "</li>";
	?>
	</ul>
	</div>
<? } ?>
