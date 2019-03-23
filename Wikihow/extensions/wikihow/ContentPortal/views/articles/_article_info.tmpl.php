
<div class="info-container">
	<h4><?= $article->title ?></h4>
	<?= partial('articles/_completed_steps') ?>

	<ul class="nav nav-tabs" role="tablist">
		<li role="presentation" class="active">
			<a href="#history" role="tab" data-toggle="tab">Article History</a>
		</li>
		<li role="presentation">
			<a href="#notes" role="tab" data-toggle="tab">Article Notes</a>
		</li>
	</ul>

	<!-- Tab panes -->
	<div class="tab-content well-body">
		<div role="tabpanel" class="tab-pane active" id="history">
			<?
				if (!$article->events):
					echo alert("There have been no events logged for this article.");
				endif;
				foreach($article->events as $event): 
					echo partial('events/_event', ['event' => $event]);
				endforeach; 
			?>
		</div>

		<div role="tabpanel" class="tab-pane" id="notes">
			<?
				if (!$article->notes):
					echo alert("There are no notes relating to this article.");
				endif;
				echo partial('notes/index', ['article' => $article]); 
			?>
		</div>

	</div>
</div>
