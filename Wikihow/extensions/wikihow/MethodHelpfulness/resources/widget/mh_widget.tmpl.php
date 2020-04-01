<div id='mhw-wrapper' class='mhw-outer'>
<? foreach ($ctaDetails as $cta=>$ctaData) { ?>
	<div class='mhw-info-box'>
		<h3><?=$ctaData['displayName']?></h3>
		<table class='mhw-table'>
			<tbody>
	<? if ($ctaTotals[$cta] !== false) { ?>
				<tr class='mhw-tr'>
					<td class='mhw-td mhw-td-total'>
						Total article votes
					</td>
					<td class='mhw-td'>
						<?=$ctaTotals[$cta]?>
					</td>
				</tr>
				<tr><td>&nbsp;</td></tr>
	<? } ?>
				<tr class='mhw-tr mhw-tr-header'>
	<? foreach ($ctaData['headers'] as $header) { ?>
					<th class='mhw-th'>
						<?=$header?>
					</th>
	<? } ?>
				</tr>
	<? foreach ($ctaData['rows'] as $row) { ?>
				<tr class='mhw-tr mhw-tr-data'>
		<? foreach ($row as $k=>$datum) { ?>
					<td class='mhw-td mhw-k-<?=$k?>'>
						<?=$datum?>
					</td>
		<? } ?>
				</tr>
	<? } ?>
			</tbody>
		</table>
	</div>
<? } ?>
</div>

