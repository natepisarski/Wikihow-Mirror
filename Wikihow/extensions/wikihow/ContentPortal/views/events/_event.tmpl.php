<div class="alert alert-<?= $event->type ?>">
	<p>
		<strong class="pull-right"><?= humanTime($event->created_at) ?></strong>
		<?= $event->message ?>
	</p>
</div>