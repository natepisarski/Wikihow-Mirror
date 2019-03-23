
<? if ($article->hasWritingDoc()): ?>
	<h3>Writing Documents</h3>

	<table class="table table-condensed table-striped">
		<thead>
			<tr>
				<th width="50%">Doc</th>
				<th>Created</th>
			</tr>
		</thead>
		<tbody>
			<tr class="doc-tr">
				<!-- <td><?= $article->writingDoc()->doc_id ?></td> -->
				<td>
					<i class="fa fa-file-word-o"></i>
					<a class="doc" target="google-editor" href="<?= $article->writingDoc()->doc_url ?>">
						<?= $article->title ?>
					</a>
				</td>
				<td><?= humanTime($article->writingDoc()->created_at) ?></td>
			</tr>
		</tbody>
	</table>
<? endif ?>


<? if (!empty($article->verifyDocs())): ?>
	<h3>Verification Documents</h3>

	<table class="table table-condensed table-striped">
		<thead>
			<tr>
				<th width="50%">Doc</th>
				<th>Created</th>
			</tr>
		</thead>
		<tbody>

			<? foreach($article->verifyDocs() as $index => $doc): ?>
				<tr class="doc-tr">
					<!-- <td><?= $doc->doc_id ?></td> -->
					<td>
						<i class="fa fa-file-word-o"></i>

						<a class="doc" target="google-editor" href="<?= $doc->doc_url ?>">
							<?= $article->title ?>
						</a>
							<sup>V<?= $index + 1 ?></sup>
					</td>
					<td><?= humanTime($doc->created_at) ?></td>
				</tr>
			<? endforeach ?>

		</tbody>
	</table>
<? endif ?>
