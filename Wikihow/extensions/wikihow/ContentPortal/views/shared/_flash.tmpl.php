<? if (hasFlash()): ?>
	<div class="row">
		<div class="col-md-12">
			<? $msg = getFlash() ?>
			<div id="flash" class="alert alert-dismissible fade in alert-<?= $msg['class'] ?>">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<?= $msg['message'] ?>
			</div>
		</div>
	</div>
<? endif; ?>
