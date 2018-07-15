<style>
	table { border: 0; border-spacing: 4px; width: 100%; }
	.outer { border: 1px solid #ddd; }
	th { font-size: 9px; }
	tr { vertical-align: top; }
	tr.out>td { padding: 10px; }
	.mid {vertical-align: middle; }
	.wid-id { display: none; }
	.odd { background-color: #ddd; }
	h4 { padding: 5px 0 5px 0; letter-spacing: 0.2em }
	.dlabel { color: #999; }
</style>

<? /*
* This code hasn't worked since Community Dashboard moved to the titus
* server. To re-enable it, we'd have to make the server-side functionality
* for restarting the daemon.
*
<h4>Refresh Data Control</h4>
<div class="status outer" style="padding: 10px;">
	<i style="text-decoration: underline;">Status</i><br/>
	<span style="font-weight: bold; font-size: 15px;">loading ...</span><br/>
	<br/>
	<i style="text-decoration: underline;">Actions</i><br/>
	<ol style="margin-left: 15px;">
		<li><a href="#" class="refresh">refresh status</a></li>
		<li><a href="#" class="restart">restart script</a> (use caution)</li>
	</ol>
</div>
*/ ?>

<br/>

<h4>Widget Customization</h4>
<div class="outer">
<table class="big">
	<tr>
		<th>Order</th>
		<th>Priority</th>
		<th>Widget</th>
		<th>Maxima</th>
		<th>Baselines / Goals</th>
	</tr>
	<? foreach ($widgets as $i=>$widget): ?>
		<?
			$isPriority = isset($priorities[$widget]);
			$checked = $isPriority ? 'checked="yes"' : '';
			$thresh = @$thresholds[$widget];
			$baseline = @$baselines[$widget];
			$currentVal = @$current[$widget];
		?>
		<tr class="out">
			<td class="mid"><span class="wid-id"><?= $widget ?></span><input type="text" size="2" value="<?= $isPriority ? $i+1 : '' ?>" /></td>
			<td class="mid"><input type="checkbox" <?= $checked ?> /></td>
			<td class="mid"><?= $titles[$widget] ?></td>
			<td>
				<table><tr>
					<td style="width: 70px">Low max</td><td><input class="lowmax" type="text" size="5" value="<?= $thresh['low'] ?>" placeholder="e.g. 50" /></td>
				</tr><tr>
					<td>Mid max</td><td><input class="medmax" type="text" size="5" value="<?= $thresh['med'] ?>" placeholder="100" /></td>
				</tr><tr>
					<td>High max</td><td><input class="highmax" type="text" size="5" value="<?= $thresh['high'] ?>" placeholder="150" /></td>
				</tr></table>
			</td>
			<td>
				<input class="base" type="radio" name="group-<?= $widget ?>" value="natural" <?= $baseline ? '' : 'checked="checked"' ?> /> natural goal<br/>
				<input class="base" type="radio" name="group-<?= $widget ?>" value="custom" <?= $baseline ? 'checked="checked"' : '' ?> />
					custom goal <input class="custbase" type="text" size="5" value="<?= $baseline ?>" placeholder="e.g. 75" /><br/>
				<br/>
				<?= $currentVal !== '' && $currentVal !== null ? 'current: <b>' . $currentVal . '</b>' : '<i>current value unknown</i>' ?><br/>
			</td>
		</tr>
	<? endforeach; ?>
</table>

<hr style="color: #eee; background-color: #eee;" />

<div style="margin: 7px;">
	<button class="save" style="margin-left: 15px;" disabled="disabled">save</button>
	<a href="/Special:AdminCommunityDashboard" style="margin-left: 5px;">cancel</a><br/>
</div>

</div>

<script>
(function($) {

	$(function() {
		pingRefreshScript();
		$('tr.out:even').addClass('odd');

		$('.save')
			.prop('disabled', false)
			.click(function () {
				var checked = $('table input:checkbox').filter(':checked');
				if (checked.length > 3) {
					alert('You must choose 3 or fewer community priorities');
					return false;
				}
				$('.save').prop('disabled', true);
				$.post('/Special:AdminCommunityDashboard/save-settings',
					{ settings: serializeSettings() },
					function(data) {
						if (data && !data['error']) {
							// reload page
							window.location.href = window.location.href;
						} else {
							$('.save').prop('disabled', false);
							var err = data ? data['error'] : 'network error';
							alert('saving error: ' + err);
						}
					},
					'json');
				return false;
			});

		$('.refresh')
			.click(function () {
				pingRefreshScript();
				return false;
			});

		$('.restart')
			.click(function () {
				$('.status span').html('loading ...');
				$('.restart').replaceWith('<i>restarting script now ... refresh page if you need to restart again</i>');
				$.post(
					'/Special:AdminCommunityDashboard/refresh-stats-restart',
					function (data) {
						if (data && !data['error']) {
							$('.status span').html(data['status']);
						} else {
							var err = data ? data['error'] : 'network error';
							$('.status span').html('error occurred: ' + err);
						}
					},
					'json');
				return false;
			});
	});

	function pingRefreshScript() {
		$('.status span').html('loading ...');
		$.post('/Special:AdminCommunityDashboard/refresh-stats-status',
			function (data) {
				if (data && !data['error']) {
					$('.status span').html(data['status']);
				} else {
					var err = data ? data['error'] : 'network error';
					$('.status span').html('error occurred: ' + err);
				}
			},
			'json');
	}

	function serializeSettings() {
		var rows = $('tr.out');

		// get priorities
		var prio = [];
		$(rows).each(function () {
			var id = $('.wid-id', this).text();
			var ispri = $('input:checkbox', this).is(':checked');
			var order = $('td:first input:text', this).val();
			order = castInt(order);
			prio.push({id: id, ispri: ispri, order: order});
		});

		// sort by priority (and order if both things are a priority)
		prio = prio.sort(function (a, b) {
			if (a['ispri'] && b['ispri']) return a['order'] - b['order'];
			if (a['ispri']) return -1;
			if (b['ispri']) return 1;
			return 0;
		});

		// construct priorities output array
		var priorities = [];
		$(prio).each(function () {
			if (this['ispri']) priorities.push(this['id']);
		});

		// construct thresholds output array
		var thresholds = {};
		$(rows).each(function() {
			var id = $('.wid-id', this).text();
			var low = $('.lowmax', this).val();
			var med = $('.medmax', this).val();
			var high = $('.highmax', this).val();
			thresholds[id] = {
				low: castInt(low),
				med: castInt(med),
				high: castInt(high)
			};
		});

		// construct baselines output array
		var baselines = {};
		$(rows).each(function() {
			var id = $('.wid-id', this).text();
			var base = $('.base:checked', this).val();
			var custom = $('.custbase', this).val();
			if (base == 'custom') {
				var baseline = castInt(custom);
			} else {
				var baseline = 0;
			}
			baselines[id] = baseline;
		});

		return $.toJSON({
			priorities: priorities,
			thresholds: thresholds,
			baselines: baselines
		});
	}

	// my version of parseInt
	function castInt(n) {
		n = parseInt(n, 10);
		if (isNaN(n)) n = 0;
		return n;
	}

})(jQuery);
</script>
