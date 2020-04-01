
<div class="stats-container">
	<table width="100%" class="stats-table">
		<thead>
			<tr>
				<th>Usage Statistics:</th>
				<th>
					<form method="get" url="<?= $formUrl ?>" id="date-range-form">
						<label>Choose time frame:</label>
						<input type="hidden" name="<?= UsageStats::SHOW_STATS_PARAM ?>" value="true" />
						<select name="<?= UsageStats::RANGE_FILTER_PARAM ?>">
							<? foreach($rangeOptions as $label => $value): ?>
								<option value="<?= $value ?>" <?= $currentRange == $value ? 'selected' : null ?>>
									<?= $label ?>
								</option>
							<? endforeach; ?>
						</select>
					</form>
				</th>
			</tr>
		</thead>
		<tbody>
			<? foreach($stats as $key => $value): ?>
				<tr>
					<td><?= $key ?></td>
					<td class="<?= (strpos(strtolower($key), 'percent') !== false) ? 'percent' : 'numeric' ?>">
						<?= number_format($value) ?>
					</td>
				</tr>
			<? endforeach ?>
		</tbody>
	</table>
</div>

<script>
	$(document).ready(function () {
	   $('#date-range-form select').change(function () {
		  $(this).parent().submit();
	   });
	});
</script>
