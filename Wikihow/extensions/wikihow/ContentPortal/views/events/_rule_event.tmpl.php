<div class="alert alert-<?= $event->type ?> rule-event">
	<p class='rule-date'><?= humanTime($event->created_at) ?></p>
	<p><?= $event->message ?></p>
</div>
