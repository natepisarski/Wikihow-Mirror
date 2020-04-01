
<!DOCTYPE html>
<html>
<head>
	<title>Content Portal Nightly Summary</title>
	<? date_default_timezone_set('America/Los_Angeles'); ?>
	<style type="text/css">
		<?= file_get_contents(APP_DIR . '/assets/css/mail.css') ?>
	</style>
</head>
<body class="mail">

	<div class="container">
		<div class="jumbotron text-center">
			<h2>Content Portal Summary</h2>
			<p class="lead">
				Sent: <?= date("m/d/y h:i") ?>
				Summary of moved, deleted and redirected articles that are in the portal.
			</p>
			<a href="<?= absUrl('exports/dump') ?>" class="btn btn-primary btn-lg">
				Download Full CSV Dump
			</a>
		</div>

		<div class="well">
			<h3>Moves</h3>
			<? if (!empty($results['moves'])): ?>
				<table class="table table-striped">
					<thead>
						<tr>
							<th>ID</th>
							<th>Original title / URL</th>
							<th>New Title / URL</th>
						</tr>
					</thead>
					<tbody>
						<? foreach($results['moves'] as $move): ?>
							<tr>
								<td rowspan=2><?= $move['article']->wh_article_id ?></td>
								<td><?= $move['article']->title ?></td>
								<td><?= $move['title']->getText() ?></td>
							</tr>
							<tr>

								<td><?= autoLink($move['article']->wh_article_url) ?>
								<td><?= autolink(URL_PREFIX . $move['title']->getPartialUrl()) ?></td>
							</tr>
						<? endforeach; ?>
					</tbody>
				</table>
			<? endif; ?>
		</div>

		<div class="well">
			<h3>Deletes</h3>
			<? if (!empty($results['deletes'])): ?>
				<table class="table table-striped">
					<thead>
						<tr>
							<th>ID</th>
							<th>Title</th>
							<th>URL</th>
						</tr>
					</thead>
					<tbody>
						<? foreach($results['deletes'] as $delete): ?>
							<tr>
								<td><?= $delete['article']->wh_article_id ?></td>
								<td><?= $delete['article']->title ?></td>
								<td><?= autoLink($delete['article']->wh_article_url) ?>
							</tr>
						<? endforeach; ?>
					</tbody>
				</table>
			<? endif; ?>
		</div>

		<div class="well">
			<h3>Redirects</h3>
			<? if (!empty($results['redirects'])): ?>
				<table class="table table-striped">
					<thead>
						<tr>
							<th>ID</th>
							<th>Title</th>
							<th>URL</th>
						</tr>
					</thead>
					<tbody>
						<? foreach($results['redirects'] as $redirect): ?>
							<tr>
								<td><?= $redirect['article']->wh_article_id ?></td>
								<td><?= $redirect['article']->title ?></td>
								<td><?= autoLink($redirect['article']->wh_article_url) ?>
							</tr>
						<? endforeach; ?>
					</tbody>
				</table>
			<? endif; ?>
		</div>
	</div>



</body>
</html>
