<div>Go to the <a href=<?=$sheetLink?>>Master Expert Verified Sheet</a></div>
<div class='spa_sectioncontents'>
	<h2 class='spa_section'>Import Master Expert Verified Sheet</h2>
	<div class='button primary spa_buttonlarge' id='spa_import'><div id='import_button_text'>Import</div></div>
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
<h5>Current Status:</h5>
<div id='spa_is_running' class='spa_results_list'>
	<?=$currentStatus?>
</div>
<h5>Sheet Stats:</h5>
<div id='spa_stats' class='spa_results_list'>
	<?=$stats?>
</div>
<h5>Last Run Start Time:</h5>
<div id='spa_last_run_start' class='spa_results_list'>
	<?=$lastRunStart?>
</div>
<h5>Last Run Finish Time:</h5>
<div id='spa_last_run_finish' class='spa_results_list'>
	<?=$lastRunFinish?>
</div>
<h5>Last Run Result:</h5>
<div id='spa_last_run' class='spa_results_list'>
	<?=$lastRunResult?>
</div>
<h5>Last Run Warnings:</h5>
<div id='spa_warnings' class='spa_results_list'>
	<?=$lastRunWarnings?>
</div>
<h5>Last Run Errors:</h5>
<div id='spa_errors' class='spa_results_list'>
	<?=$lastRunErrors?>
</div>
<h5>Last Run Info:</h5>
<div id='spa_info' class='spa_results_list'>
	<?=$lastRunInfo?>
</div>
