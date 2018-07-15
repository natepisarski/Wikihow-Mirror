<?= partial("shared/errors") ?>

<form id="article-form" class="f-article prevent-double" action="<?= urlFor($article) ?>" method="post">

	<div class="form-group title article-title-group">
		<label>WikiHow article title</label>

		<input name="article[title]" value="<?= $article->title ?>" <?= $article->id ? "disabled" : "" ?> class="f-title input-lg form-control" placeholder="Wikihow Title">
		<? if ($article->id): ?>
			<div id="lock-toggle" class="input-lock locked" title="Unlock Article" data-content="Unlock the article to change it or link to an article on WikiHow."></div>
		<? endif; ?>

		<!-- <input name="article[wh_article_url]" value="<?// $article->wh_article_url ?>" class="input-lg form-control"> -->
		<!-- <input name="article[wh_article_id]" value="<?// $article->wh_article_id ?>" class="input-lg form-control"> -->
		<!-- <span id="helpBlock" class="help-block">A WikiHow article title</span> -->
	</div>

	<div class="checkbox">
		<label>
			<input type="hidden" name="article[is_wrm]" value="<?= $article->id ? $article->is_wrm : 0 ?>">
			<input type="checkbox" name="article[is_wrm]" <?= $article->id ? 'disabled="true"' : '' ?> value="1" <?= checkedIf($article->is_wrm, 1) ?>>
			Is WRM?
		</label>
	</div>

	<div class="row">
		<div class="form-group col-md-12" id="note-input">
			<div class="checkbox">
				<label>
					<input id="note-toggle" type="checkbox" name="addNote" value="1" <?= checkedIf(params('addNote', 0) || $article->lastNote(), 1) ?>>
					Add a note to assignee?
				</label>
			</div>
			<textarea name="note" rows="6" class="form-control hidden"><?= params('note') ?></textarea>
		</div>
	</div>

	<div class="row">

		<div class="form-group category col-md-4">
			<label>Category</label>
			<select id="category" name="article[category_id]" class="form-control" placeholder="Category">
				<? foreach($categories as $category): ?>
					<option value="<?= $category->id ?>" <?= selectedIf($category->id, $article->category_id) ?>>
						<?= $category->title ?>
					</option>
				<? endforeach; ?>
			</select>
		</div>

		<div class="form-group category col-md-4">
			<label>Article State</label>
			<input type="hidden" name="article[state_id]" value="<?= $article->state_id ?>">
			<select id="state" name="article[state_id]" <?= stateDisabled($article) ?> class="form-control" placeholder="Category">
				<? foreach(getArticleRoles($article->state_id) as $role): ?>
					<option value="<?= $role->id ?>" <?= $role->public ? '' : 'disabled=true' ?> <?= selectedIf($role->id, $article->state_id) ?>>
						<?= $role->present_tense ?>
					</option>
				<? endforeach; ?>
			</select>
		</div>

		<div id="assign-form" class="form-group assignment col-md-4">
			<label>Assign To</label>
			<div id="users" class="animated">
			</div>
		</div>
	</div>


	<hr/>

	<input type="submit" class="btn btn-primary btn-lg" value="Save"/>
	<a class="btn btn-default btn-lg" href="<?= url("articles") ?>">Cancel</a>

</form>

<!-- handlebars and javascript -->
<script type="text/handlebars" id="dropdown-tpl">
	{{#if enabled}}
		<select name="article[assigned_id]" class="form-control f-assignment" placeholder="Category">
			{{#if users.length}}
				{{#each users}}
					<option value="{{id}}" {{isSelected id ../article.assigned_id}}>{{username}}</option>
				{{/each}}
				<option disabled>---- Admins ----</option>
			{{/if}}

			{{#each admins}}
				<option value="{{id}}" {{isSelected id ../article.assigned_id}}>{{username}}</option>
			{{/each}}
		</select>

		{{#unless users.length}}
			<div class="help-block bg-warning">
				There are no <strong>{{role.title}}s</strong> that have been assigned to the category <strong>{{category.title}}</strong>.
				Please either assign some <strong>{{role.title}}s</strong> to <strong>{{category.title}}</strong>, or assign one of the admins listed above.
			</div>
		{{/unless}}
	{{/if}}
</script>

<? addScript("WH.articleForm.initialize({$article->to_json()});") ?>
