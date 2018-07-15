
<div class="well">
	<h2>Events for Auto-Assign Rules</h2>
	<p><a href="<?= url('rules') ?>">Manage the rules</a></p>
	<?= paginate() ?>
	<div class="well-body">
		<?
			foreach($events as $event):
			echo partial('events/_rule_event', ['event' => $event]);
			endforeach;
		?>
	</div>
	<?= paginate() ?>
</div>