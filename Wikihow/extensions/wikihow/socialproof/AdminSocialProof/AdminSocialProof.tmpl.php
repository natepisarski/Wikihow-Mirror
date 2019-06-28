<div class='spa_sectioncontents'>

	<h2 class='spa_left'>Import Master Expert Verified Sheet</h2>
	<label class="spa_go_to_sheet">Go to <a target="_blank" href="<?= $sheetLink ?>">sheet</a></label>
	<div class='clearall'></div>

	<div class='button primary spa_buttonlarge' id='spa_import'>
		<div id='import_button_text'>Import</div>
	</div>

	<hr>

	<div id='loader_container' class='progress'>
	  <span>i</span>
	  <span>m</span>
	  <span>p</span>
	  <span>o</span>
	  <span>r</span>
	  <span>t</span>
	  <span>i</span>
	  <span>n</span>
	  <span>g</span>
	</div>
</div>

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

<div id="spa_details_container" class="hidden">

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
