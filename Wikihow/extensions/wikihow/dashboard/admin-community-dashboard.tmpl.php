
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
