<div class='methodhelpfulness' id='mh-bottom-form'>
	<div class='mhbf-inner' id='mh-bottom-form-inner'>
		<div class='mhbf-header'>
			<div class='mhbf-headline'>
				<?=wfMessage('mhbf-headline')->text()?>
			</div>
			<div class='mhbf-header-prompt'>
				<?=wfMessage('mhbf-header-prompt')->text()?>
			</div>
		</div>
		<form class='mh-form' id='mhbf-form' action='javascript:void(0);'>
			<ul class='mh-selector-list' id='mhbf-selector-list'>
	<? foreach ($methods as $k=>$method) { ?>
				<li class='mh-selector-item mhbf-selector-item'>
					<div class='mhbf-selector-element'>
						<input type='checkbox' class='mh-selector-checkbox mhbf-selector-checkbox' id='mhbf-selector-checkbox-<?=$k?>' name='mhbf-selector-checkbox'>
						<label class='mhbf-selector-label' for='mhbf-selector-checkbox-<?=$k?>'></label>
					</div>
					<label class='mhbf-selector-text mhbf-selector-element mh-wordwrap' for='mhbf-selector-checkbox-<?=$k?>'><?=htmlspecialchars($method)?></label>
				</li>
	<? } ?>
			</ul>
			<div class='mhbf-form-lower'>
				<input class='button primary mh-button mh-submit mhbf-button mh-submit-inactive' id='mhbf-submit' value='<?=wfMessage('submit')->text()?>' type='button' />
				<div class='mh-spinner mhbf-spinner mh-submit-inactive'>
					<img src='<?=wfGetPad('/extensions/wikihow/rotate.gif')?>' alt='<?=wfMessage('mhbf-submitting')?>' />
				</div>
			</div>
		</form>
	</div>
	<div class='mhbf-inner mhbf-hidden' id='mhbf-inner-details'>
		<form class='mh-form' id='mhbf-details-form' action='javascript:void(0);'>
			<div class='mhbf-header'>
				<div class='mhbf-headline'>
					<?=wfMessage('mhbf-d-headline')->text()?>
				</div>
				<div class='mhbf-header-prompt'>
					<?=wfMessage('mhbf-d-header-prompt')->text()?>
				</div>
			</div>
			<textarea class='mh-details' id='mhbf-details' placeholder='<?=wfMessage('ratearticle_notrated_textarea')->text()?>' name='submit' maxlength='254'></textarea>
			<p id="mhbf-public-prompt"><?=wfMessage("ratearticle_publicprompt")->text()?></p>
			<input type="radio" class="mhbf-public" name="mhbf-public" value="yes" /> <label class="mhbf-public-label"><?=wfMessage('ratearticle_publicyes')->text()?></label> <br />
			<input type="radio" class="mhbf-public" name="mhbf-public" value="no" /> <label class="mhbf-public-label"><?=wfMessage('ratearticle_publicno')->text()?></label><br /><br />
			<div id="mhbf-public-info">
				<input type="text" name="mhbf-firstname" id="mhbf-firstname" class="mhbf-input" placeholder="<?=wfMessage('ratearticle_firstname')->text()?>" />
				<input type="text" name="mhbf-lastname" id="mhbf-lastname" class="mhbf-input" placeholder="<?=wfMessage('ratearticle_lastname')->text()?>" />
				<p id="mhbf-public-error" style="display:none;"><?= wfMessage("ratearticle_error")->text()?></p>
				<p><?= wfMessage("ratearticle_public_agree")->text()?></p>
			</div>
			<input class='button primary mh-button mh-submit-custom mh-details-submit mhbf-button mh-submit-inactive' id='mhbf-details-submit' value='<?=wfMessage('submit')->text()?>' type='button' />
			<div class='mh-spinner mhbf-spinner mh-submit-inactive'>
				<img src='<?=wfGetPad('/extensions/wikihow/rotate.gif')?>' alt='<?=wfMessage('mhbf-submitting')?>' />
			</div>
		</form>
	</div>
	<div class='clearall'></div>
</div>
<div class='mhbf-hidden' id='mhbf-thanks'>
	<?=wfMessage('ratearticle_reason_submitted_yes')->text()?>
</div>

