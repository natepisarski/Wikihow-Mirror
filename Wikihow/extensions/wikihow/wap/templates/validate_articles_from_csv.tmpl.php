<hr class="csv_divider">

<table class="csv_upload_table">
	<tr>
		<th>&nbsp;</th>
		<th>DB User</th>
		<th>CSV user</th>
	</tr>

<?php foreach ($items as $status => $itemList): ?>

	<?php if (!$itemList) { continue; } ?>

	<tr class="row_divider">
		<th><?= strtoupper($status) ?></th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
	</tr>

	<?php

	foreach ($itemList as $item):
		extract($item); // $aid, $anchor, $article, $csvUId, $csvUname, $dbUId, $dbUname, $url, $usersMatch
		$titleLink = Html::element('a', ['href' => $url], $anchor);

		$rowClass = '';
		$csvRadioChecked = ''; // to check the CSV user radio button
		$radiosDisabled = '';  // to enable/disable both radio buttons in the row
		switch ($status) {
			case WAPArticle::STATE_INVALID:
			case WAPArticle::STATE_EXCLUDED:
				// The article is not valid, so we can't mark it as complete
				$showDbUser = $showCsvUser = false;
				$rowClass = 'csv_dim_row';
				break;

			case WAPArticle::STATE_NEW:
			case WAPArticle::STATE_UNASSIGNED:
				// The article doesn't have a DB user, but we can complete it with the CSV user
				$showDbUser = false;
				$showCsvUser = true;
				break;

			case WAPArticle::STATE_ASSIGNED:
				// Here we can choose the DB or CSV user
				$showDbUser = $showCsvUser = true;
				$csvRadioChecked = 'checked';
				break;

			case WAPArticle::STATE_COMPLETE:
				// Here we can choose too...
				$showDbUser = $showCsvUser = true;
				if ($usersMatch) {
					// ... but if the users match, there is no need to complete this article again
					$rowClass = 'csv_dim_row';
					$csvRadioChecked = 'checked';
					$radiosDisabled = 'disabled';
				}
				break;
		}
	?>

		<tr class="<?= $rowClass ?>">
			<td><?= $titleLink ?></td>
			<td>
				<?php if ($showDbUser): ?>
					<input type='radio' class='checked_article' name='article_<?=$aid?>' <?= $radiosDisabled ?>
						data-aid='<?=$aid?>' data-uid='<?=$dbUId?>' /> <?= $dbUname ?>
				<?php else: ?>
					-
				<?php endif ?>
			</td>
			<td>
				<?php if ($showCsvUser): ?>
					<input type='radio' class='checked_article' name='article_<?=$aid?>' <?= $csvRadioChecked ?> <?= $radiosDisabled ?>
						data-aid='<?=$aid?>' data-uid='<?=$csvUId?>' /> <?= $csvUname ?>
				<?php else: ?>
					-
				<?php endif ?>
			</td>
		</tr>

	<?php endforeach ?>

<?php endforeach ?>

</table>

<div style="margin-top: 10px">
	<button id="<?=$buttonId?>" style="padding: 5px;"><?=$buttonTxt?></button>
</div>

