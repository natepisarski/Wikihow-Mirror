
<div class="well">
	<h2>Events for <?= $user->username ?></h2>
	<p class="lead">
		Here you can view all actions of <?= $user->username ?>.
	</p>
	<?= paginate() ?>
	<div class="well-body">
		<? 
			foreach($events as $event):
			echo partial('events/_event', ['event' => $event]);
			endforeach;
		?>
	</div>
	<?= paginate() ?>
</div>
