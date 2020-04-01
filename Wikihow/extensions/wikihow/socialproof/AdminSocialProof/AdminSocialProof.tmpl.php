<div id='spa_wrap' style="display:none;">
	<div class='spa_sectioncontents'>

		<h2 class='spa_left'>Import Master Expert Verified Sheet</h2>
		<label class="spa_go_to_sheet">Go to <a target="_blank" href="<?= $sheetLink ?>">sheet</a></label>
		<div class='clearall'></div>

		<button class='spa_button button primary' id='spa_import'>Import</button>
		<button class='spa_button button primary' id='spa_in_progress' disabled>In progress</button>

		<hr>

	</div>

	<div id="spa_details_container" class="hidden">

		<table class="spa_table">
			<tr>
				<td><b>Last run result:</b></td>
				<td id='spa_last_run_result'>&nbsp;</td>
			</tr>

			<tr>
				<td><b>Last run time:</b></td>
				<td id='spa_last_run_start'>&nbsp;</td>
			</tr>
		</table>

		<hr>


		<div id="spa_error_container" class="hidden">
			<h5 class='spa_error'>Errors</h5>
			<div id='spa_errors' class='spa_results_list'></div>
		</div>

		<div id="spa_warn_container" class="hidden">
			<h5 class='spa_warn'>Warnings</h5>
			<div id='spa_warnings' class='spa_results_list'></div>
		</div>

		<div id="spa_stats_container" class="hidden">
			<h5>Stats</h5>
			<div id='spa_stats' class='spa_results_list'></div>
		</div>

	</div>
</div>
