
<div class="well">
	<h1>Mass Import of Articles with CSV</h1>

	<div class="well-body">
		<?= partial('shared/errors', ['title' => 'There were problems uploaded file.']); ?>

		<?
			if (isset($errors)):
				foreach($errors as $error):
					echo partial('shared/errors', ['title' => $error['article']['title'], 'errors' => $error['errors']]);
				endforeach;
			endif;
		?>

		<form class="prevent-double" action="<?= url('imports/process') ?>" method="post" enctype="multipart/form-data">
			<label>Choose CSV file</label>
			<input type="file" class="form-control input-lg" name="csv">
			<input type="hidden" name="form" value="upload"/>
			<hr/>
			<input class="btn btn-primary" type="submit" value="Upload">
		</form>
	</div>

	<div class="well-body">
		<h3>
			Fields.
			<small>
			The capitalization does not matter, but the CSV file must have the following headers. Red fields must be included.
			</small>
		</h3>
		 <table class="table table-bordered table-condensed">
			 <thead>
			 	<tr>
					<th width="10%">Article ID</th>
					<th>URL</th>
					<th class="bg-danger" width="10%">Is Wrm</th>
					<th class="bg-danger">Category</th>
					<th class="bg-danger">State</th>
					<th>Url to User</th>
					<th>Notes</th>
				</tr>
			</thead>
			<tbody>

				<tr class="small">
					<td>145698</td>
					<td>http://www.wikihow.com/Train-A-Dog</td>
					<td>true</td>
					<td>Veterinary</td>
					<td>Edit</td>
					<td>Dr. Carrie</td>
					<td>Nice work!</td>
				</tr>

<!-- 				<tr class="small">
					<td>Optional, Leave blank if a new Article. <span class="bg-warning">If existing, this must be included.</span></td>
					<td>Optional, but either Id or Url must be present. <span class="bg-warning">If a new article this must be included.</span></td>
					<td>Required</td>
					<td>Required. This is the title of the category that is in the Content Portal. Example: Medical</td>
					<td>Optional, if left blank it will be in state Writing. <span class="bg-warning">But if present, it must be one of the following strings relating to a State.</span></td>
					<td>Optional, if left blank it in state unassigned. <span class="bg-warning">If present, it must be a url to a user's profile, or a WikiHow username.</span></td>
				</tr> -->

				<tr class="small">
					<td>
						<strong>Example Values:</strong>
						if a new article
						145698
					</td>

					<td valign="top">
						<strong>Example Values:</strong>
						A valid WikiHow Url, or a title
					</td>

					<td>
						<strong>Accepted Values:</strong>
						true / false, if false, an id or url must be included.
					</td>


					<td class="text-left">
						<strong>Example Values:</strong>
						An existing or new category in the Content Portal
					</td>

					<td class="text-left">
						<strong>Accepted Values:</strong>
						<?
							foreach(ContentPortal\Role::publicRoles() as $role) {
								echo "$role->key, $role->title, $role->present_tense, $role->past_tense";
							}
						?>
					</td>

					<td>
						<strong>Example Values:</strong>
						A username of someone who has been added to the portal,
						a url to a user's profile on Wikihow.
					</td>

					<td>
						<strong>Example Values:</strong>
						A short note giving details or helpful insights.
					</td>
				</tr>
			</tbody>
		</table>

	</div>
</div>
