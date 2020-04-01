<div id='avr_body'>
	<div class='avr_items_count'>Total items to review: <span id='avric'><?=$uncleared_count?></span></div>
	<div class='avr_items_position'>Reviewing: <span id='avrip'></span></div>
	<div style="clear:both"></div>
	<div class='avr_buttons hidden'>
		<div class='button avr_button avr_nav avr_next' data-id='avr_next'></div>
		<div class='button avr_button avr_revert' data-id='avr_revert'>Revert</div>
		<div class='button avr_button avr_email_clear' data-id='avr_email_clear'>Email and Clear</div>
		<div class='button avr_button avr_email_watch' data-id='avr_email_watch'>Email and Watch</div>
		<div class='button avr_button avr_clear' data-id='avr_clear'>Clear</div>
		<div class='button avr_button avr_nav avr_prev' data-id='avr_prev'></div>
	</div>
	<div id='avr_items'><?=$uncleared?></div>
	<div style="clear:both"></div>
	<div id='avr_checkbox'><input type="checkbox" name='advanced'>Advanced Options</div>
	<div class='button avr_button avr_advanced' data-id='avr_uh'>Update Historical</div>
	<div style="clear:both"></div>
	<div id="avr_results"></div>
</div>
