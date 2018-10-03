<div id="uci" class="tool" style="display:none">
	<div id="uci_header" class="tool_header">
		<a href="#" id="uci_keys"><?= wfMessage('uci-shortcuts')->text(); ?></a>
		<h1><?= wfMessage('uci-question')->text(); ?></h1>
		<div id="uci_img_wrap">
			<img src='<?= wfGetPad('/extensions/wikihow/rotate.gif') ?>' id='uci_img_spinner' alt='' />
			<div id="uci_img"></div>
		</div>
		<div id="uci_patrol_buttons" class="uci_patrol_buttons uci_buttons">
			<a href="#" id="uci_resetskip" title="resetskip" class="button secondary" tabindex="2"><?= wfMessage('uci-resetskip')->text() ?></a>
			<a href="#" id="uci_errortest" title="errortest" class="button secondary" tabindex="2"><?= wfMessage('uci-errortest')->text() ?></a>
			<a href="#" id="uci_bad" title="bad" class="button secondary op-action uci_bad" tabindex="3" data-event_action="vote_down"><?= wfMessage('uci-button-no')->text() ?></a>
			<a href="#" id="uci_skip" title="skip" class="button primary uci_button" tabindex="4" data-event_action="not_sure"><?= wfMessage('uci-button-notsure')->text() ?></a>
			<a href="#" id="uci_good" title="good" class="button secondary op-action uci_good" tabindex="5" data-event_action="vote_up"><?= wfMessage('uci-button-yes')->text() ?></a>
		</div>
		<div id="uci_votecomplete">
			<div id="uci_complete_buttons" class="uci_buttons">
				<a href="#" id="uci_confirm" title="confirm" class="button primary uci_button uci_button_right"><?= wfMessage('uci-next')->text() ?></a>
				<a href="#" id="uci_undo" title="undo" class="button secondary uci_button uci_button_left"><?= wfMessage('uci-undo')->text() ?></a>
			</div>
			<div class="clearall"></div>
			<div id="uci_voters">
				<h2 id="uci_complete_message"></h2>
				<div class="clearall"></div>
				<div id="uci_complete_sub"></div>
				<div class="clearall"></div>
				<div id="uci_voter_wrapper">
					<div id="user_voter" class="uci_voter">
						<div class="clearall"></div>
						<div id="uci_user_vote"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="clearall"></div>
	</div>
	<div id="uci_article"></div>
</div>
<div id="uci_error" style="display:none;"><?= wfMessage('uci-error')->text(); ?></div>
<div id="uci_info" style="display:none;"><?= wfMessage('ucipatrol_keys')->text(); ?></div>
