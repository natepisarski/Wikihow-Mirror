
<ol class="breadcrumb">
	<li><a href="<?= url("articles") ?>">All Articles</a></li>
	<li class="active">Edit Article</li>
</ol>

<?
if ($article->is_redirect) {
	echo alert('This article is a redirect and needs addressed.');
}
?>

<div class="row">
	<div class="col-md-8">
		<div class="well">
			<h2>Edit Article</h2>
			<div class="well-body">
				<?= partial('articles/_form') ?>
			</div>
		</div>
	</div>

	<div class="col-md-4">

		<div class="panel panel-default">
			<div class="panel-heading">Article Info</div>
			<div class="panel-body">

				<dl class="">
					<dt>WH Url:</dt>
					<dd>
						<a href="<?= $article->wh_article_url ?>" target="_blank">
							<i class="fa fa-link"></i>
							<?= $article->wh_article_url ?>
						</a>
					</dd>
					<dt>WH ID:</dt>
					<dd><?= is_null($article->wh_article_id) ? "N/A" : $article->wh_article_id ?></dd>
				</dl>

			</div>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading">History</div>
			<div class="panel-body">
			<?
				foreach($article->events as $event):
					echo partial('events/_event', ['event' => $event]);
				endforeach;
			?>
			</div>
		</div>
	</div>
</div>