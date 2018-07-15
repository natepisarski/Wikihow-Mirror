<div
	class='kb-box' id='kb-box-<?=$id?>' data-id='<?=$id?>'
	data-aid='<?=$aid?>' data-topic='<?=$topic?>' data-phrase='<?=$phrase?>'
	data-thumbUrl='<?=$thumbUrl?>' data-thumbAlt='<?=$thumbAlt?>'
>
	<div class='kb-box-inner'>
		<div class='kb-gradients'></div>
		<div class='kb-top kb-green'>
			<div class='kb-top-prompt'>
				<div class='kb-top-tell-us'>
					<?= wfMessage('kb-prompt') ?>
				</div>
				<div class='kb-top-topic'>
					<?=$topic?>?
				</div>
			</div>
		</div>
		<div class='kb-bottom kb-bottom-border kb-noselect'>
			<div class='kb-bottom-wrapper'>
				<div class='kb-bottom-left-wrapper'>
					<a href="#" class='kb-yes' role='button' tabindex='0'>
						<?= wfMessage('kb-yes') ?>
					</a>
				</div>
				<div class='kb-bottom-right-wrapper'>
					<a href="#" class='kb-no' role='button' tabindex='0'>
						<?= wfMessage('kb-no') ?>
					</a>
				</div>
			</div>
			<div class='kb-bottom-stripe kb-bottom-border'></div>
		</div>
	</div>
</div>

