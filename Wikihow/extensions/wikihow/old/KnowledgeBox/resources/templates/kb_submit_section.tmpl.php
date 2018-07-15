<div class='section kb-submit-section' id='knowledgebox-submit' style='display: none;'>
	<div class='kb-submit-header'>
		<div class='kb-submit-image'>
			<div class='kb-submit-image-inner'>
				<div class='kb-placeholder-image'></div>
				<div class='kb-real-image'></div>
			</div>
		</div>
		<div class='kb-submit-header-text'>
			<div class='kb-submit-header-arrow-wrapper'>
				<div class='kb-submit-header-arrow-outer'></div>
				<div class='kb-submit-header-arrow-inner'></div>
			</div>
			<div class='kb-submit-header-text-inner'>
				<div class='kb-submit-header-prompt'>
					<?=wfMessage('kb-submit-prompt')?>
				</div>
				<div class='kb-submit-header-prompt-phrase'>
					...
				</div>
			</div>
		</div>
		<div class='kb-close'>
			<div class='kb-close-line-cw'></div>
			<div class='kb-close-line-ccw'></div>
		</div>
	</div>

	<div class='kb-submit-form-container'>
		<form id='kb-submit-form' action='#'>
			<div
				contenteditable='true' disabled='true'
				class='inactive kb-fancy-input' id='kb-fake-content-box'
			>
				<span class='kb-fancy-input-placeholder' id='kb-placeholder'><?=wfMessage('kb-tell-us-expanded')?></span>
			</div>
			<div class='kb-content-box'>
				<div id='kb-content-data' data-id='0' data-aid='0' data-topic=''></div>
				<textarea class='active kb-fancy-input kb-pad-more' id='kb-content' maxlength='5000'></textarea>
				<div class='kb-tips-box'>
					<div class='kb-tips-toggle'>
						<div class='kb-tips-toggle-btn'>
							<div class='kb-tips-hbar'></div>
							<div class='kb-tips-vbar'></div>
						</div>
						<?=wfMessage('kb-tips')?>
					</div>
					<div class='kb-tips-header'>
						<strong><?=wfMessage('kb-tips-header')?></strong>
					</div>
					<div class='kb-tips-details'>
						<?=wfMessage('kb-tips-details')?><br />
						<strong><?=wfMessage('kb-dont-say')?></strong>: <em><?=wfMessage('kb-dont-say-example')?></em></br>
						<strong><?=wfMessage('kb-do-say')?></strong>: <em><?=wfMessage('kb-do-say-example')?></em></br>
					</div>
				</div>
				<div class='kb-form-bottom'>
					<div class='kb-form-bottom-left'>
						<input placeholder='<?=wfMessage('kb-email-prompt')?>' class='kb-input' id='kb-email'>
						<input placeholder='<?=wfMessage('kb-name-prompt')?>' class='kb-input' id='kb-name'>
					</div>
					<div class='kb-form-bottom-right'>
						<a href="#" class='button primary op-action' id='kb-add' role='button' tabindex='0'><?=wfMessage('submit')?></a>
						<div class='kb-spinner' id='kb-waiting'>
							<img src='<?=wfGetPad('/extensions/wikihow/rotate.gif')?>' alt='' />
						</div>
					</div>
				</div>
			</div>
			<div class='clearall'></div>
		</form>
	</div>
</div>

