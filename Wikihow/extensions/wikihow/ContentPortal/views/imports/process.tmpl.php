
<div id="message"></div>

<? foreach($importer->invalidArticles as $article): ?>
	<?// partial('shared/errors', ['errors' => $article->errors->get_raw_errors()]) ?>
<? endforeach ?>

<? $groups = $importer->articleGroups() ?>
<? foreach($groups as $group => $articles): ?>
	<? if (!empty($articles)): ?>

		<div id="<?= "panel-{$group}" ?>" class="group panel panel-default">
			<div class="panel-heading">
					<input class="toggle-all" <?= disabled($articles[0]) ?> type="checkbox" id="<?= $group ?>-select-all" data-panel="#panel-<?= $group ?>" />
				<label for="<?= $group ?>-select-all">
					<?= labelForGroup($group) ?>
				</label>
			</div>

			<div class="panel-body">
				<table class="table table-striped table-condensed">
					<thead>
						<tr>
							<th width="50%">Title</th>
							<th class="text-center">Is Wrm?</th>
							<th>Article Id</th>
							<th>Category</th>
							<th>Assignee</th>
							<th>Notes</th>
						</tr>
					</thead>

					<tbody class="small">

						<? foreach($articles as $article): ?>

							<tr class="article">
								<td>
									<textarea class="data hidden"><?= json($article) ?></textarea>
									<label>
										<input class="article-select" <?= disabled($article) ?> type="checkbox" name="articles[]" value="<?= $article->title ?>"/>
										<?= $article->title ?>
									</label>
									<?= errorsFor($article, 'title') ?>
								</td>
								<td class="text-center">
									<? if ($article->is_wrm): ?>
									 	<i class="fa fa-check-circle text-success"></i>
									<? else: ?>
										<i class="fa fa-ban text-danger"></i>
									<? endif; ?>
								</td>
								<td>
									<? if ($article->wh_article_id): ?>
										<a href="<?= $article->wh_article_url ?>" target="_blank">
											<i class="fa fa-link"></i>
											<?= $article->wh_article_id ?>
										</a>
									<? endif ?>
								</td>
								<td>
									<?= catFromCache($article->category_id) ?>
									<?= errorsFor($article, 'category_id') ?>
								</td>
								<td>
									<?= assignedUsername($article->assigned_id) ?>
									<?= $article->errors->on('assigned_id') ?>
								</td>
								<td>
									<?= $article->import_notes ?>
								</td>
							</tr>
						<? endforeach ?>

					</tbody>
				</table>
			</div>

			<div class="panel-footer">
				<button class="btn btn-info import" data-target="<?= "panel-{$group}" ?>">
					Import selected
				</button>
			</div>
		</div>

	<? endif; ?>
<? endforeach ?>

<?= addScript("WH.importer.init();") ?>

<script type="text/jst" id="success-msg">
	{{#if success}}
		<div class="alert alert-success">
			<strong>The following titles have been imported</strong>
			<ul>
				{{#each success}}
					<li>{{this}}</li>
				{{/each}}
			</ul>
		</div>
	{{/if}}

	{{#if errors}}
		<div class="alert alert-danger">
			<strong>The following titles could not be imported</strong>
			<ul>
				{{#each errors}}
					<li>{{@key}}</li>
						<ul>
							{{#each this}}
								<li>{{this}}</li>
							{{/each}}
						</ul>
					</li>
				{{/each}}
			</ul>
		</div>
	{{/if}}
</script>
