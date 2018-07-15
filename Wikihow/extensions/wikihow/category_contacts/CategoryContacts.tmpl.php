<div>
	<h3><?=wfMessage('cc_add_hdr')->text()?></h3>
	<div id="cc_add_action" class="cc_action">
		<?=wfMessage('cc_add_inst',$add_link)->text()?><br />
		<a href="#" id="cc_add_btn" class="button primary cc_button">Add</a>
		<div id="cc_add_result_good" class="cc_result cc_good_result"></div>
		<div id="cc_add_result_bad" class="cc_result cc_bad_result"></div>
	</div>
	
	<h3><?=wfMessage('cc_stop_hdr')->text()?></h3>
	<div id="cc_stop_action" class="cc_action">
		<?=wfMessage('cc_stop_inst',$stop_link)->text()?><br />
		<a href="#" id="cc_stop_btn" class="button primary cc_button">Stop</a>
		<div id="cc_stop_result_good" class="cc_result cc_good_result"></div>
		<div id="cc_stop_result_bad" class="cc_result cc_bad_result"></div>
	</div>
</div>

