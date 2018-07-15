<div class="btn-group">
	<div class="btn-group">
		<button type="button" class="f-assigned btn btn-default btn-xs dropdown-toggle <?= $article->isUnassigned() ? 'btn-danger' : '' ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			<?= articlesUsername($article) ?>
			&nbsp;
			<i class="fa fa-user"></i>
			<span class="caret"></span>
		</button>
		<ul class="dropdown-menu assign-user">
			<?
			$users = compatableUsers($article, $users);
			if (empty($users)):
			?>
				<li>
					<span class="label label-warning">
						<?= 'No ' . pluralize($article->state->title) . " found for {$article->category->title}" ?>
					</span>
				</li>
			<?
			endif;
			foreach($users as $user):
			?>
				<li>
					<a class="assign-user" <?= dataAttr(["user" => $user->id, "role" => $article->state_id, "article" => $article->id]) ?> href="#">
						<?= $user->id == $article->assigned_id ? "<s>{$user->username}</s>" : $user->username ?>
						<span class="badge"><?= count($user->active_articles) ?></span>
					</a>
				</li>
			<? endforeach; ?>

			<li>
				<span class="label label-danger" style="display:block;width:100%;">Administrators</span>
			</li>

			<? foreach($adminUsers as $user): ?>
				<li>
					<a class="assign-user" <?= dataAttr(["user" => $user->id,  "role" => $article->state_id, "article" => $article->id]) ?> href="#">
						<?= $user->id == $article->assigned_id ? "<s>{$user->username}</s>" : $user->username ?>
						<span class="badge"><?= count($user->active_articles) ?></span>
					</a>
				</li>
			<? endforeach; ?>
		</ul>
	</div>
</div>
