<div class='methodhelpfulness mh-method-thumbs' id='mh-method-thumbs-<?=$methodIndex?>' data-method='<?=$currentMethod?>'>
	<div class='mh-method-thumbs-inner mhmt-not-voted'>
		<div class='mhmt-cell mhmt-vote mhmt-down'>
			<div class='mh-submit mhmt-vote-btn-container'></div>
		</div>
		<div class='mhmt-cell mhmt-info'>
			<div class='mhmt-prompt'>
				<div class='mhmt-q'>
					<?=wfMessage('mhmt-question')->text()?>
				</div>
				<div class='mhmt-method'>
					<?=$currentMethod?>
				</div>
			</div>
			<div class='mhmt-cheer'>
				<?$a=explode("\n", wfMessage('mhmt-cheer')->text());echo $a[array_rand($a)];?>
				<?=wfMessage('mhmt-helped');?>
			</div>
			<div class='mhmt-oops'>
				<?$a=explode("\n", wfMessage('mhmt-oops')->text());echo $a[array_rand($a)];?>
				<?=wfMessage('mhmt-thanks');?>
			</div>
		</div>
		<div class='mhmt-cell mhmt-vote mhmt-up'>
			<div class='mh-submit mhmt-vote-btn-container'></div>
		</div>
		<div class='clearall'></div>
	</div>
</div>

