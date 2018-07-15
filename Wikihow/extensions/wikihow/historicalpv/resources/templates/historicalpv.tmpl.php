<form id='pvform' action='/Special:HistoricalPV' method='POST' enctype='multipart/form-data'>
	<?php if ($job_id) { ?>
	<div id='flash' class='alert alert-success fade in'>
		<a href="#" class="close" aria-label="close">×</a>
		<strong>Success!</strong> Job <?php print $job_id ?> added to the queue. You will receive an email when the job has completed!
	</div>
	<?php }?>
	<div id='error' class='alert alert-error fade in' style='display: none'>
		<a href="#" class="close" aria-label="close">×</a>
		<strong>Error!</strong> <span id='error_message'></span>
	</div>
	<div id='content'>
		<div>
			<label for='col'>Give me</label>
			<select id='col' name='col'>
				<?php foreach($redshift_fields as $f) { ?>
					<option value='<?php print $f ?>'><?php print $f ?></option>
				<?php }?>
			</select>
			<label id='date_type_label' for='date_type'>for these dates</label>
			<select id='date_type' name='date_type'>
				<option value='daily'>Daily</option>
				<option value='weekly'>Weekly (Fridays)</option>
				<option value='monthly'>Monthly (last Friday of the month)</option>
				<option value='specific'>Specific dates</option>
			</select>
		</div>
		<div id='date_range'>
			<label for='date_start'>Starting on</label>
			<input id='date_start' name='date_start' type='text' />
			<label for='date_end'> and ending on</label>
			<input id='date_end' name='date_end' type='text' />
		</div>
		<div id='date_list' style='display:none'>
			<label for='dates'>Dates (one per line)</label><br />
			<textarea id='dates' name='dates' rows=10 style='width: 100%' />
		</div>
		<div id='urls'>
			<label for='urls'>For these URLs / Pages</label>
			<textarea id='urls' name='urls' rows=10 style='width: 100%' />
			<label for='upload_file'>...or upload a file</label>
			<input type='file' id='upload_file' name='upload_file' />
		</div>
	</div>
	<div>
		<label for='email'>Email for notifications</label>
		<input type='text' name='email' />
	</div>
	<br />
	<input type='submit' class='btn' value='Go!' />
</form>
<?php if ($job_status) { ?>
	<table>
		<thead>
			<tr>
				<th>Job ID</th>
				<th>Status</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach($job_status as $id => $status) { ?>
			<tr>
				<td><?php print $id ?></td>
				<td class='border-left'>
				<?php if (substr($status, 0, 4) === 'http') {
					$url_parts = explode("/", $status);
					$filename = explode("?", $url_parts[4])[0];
					print "Finished: <a href='" . $status . "' download='" . $filename . "'>Download</a>";
				} else {
					print $status;
				} ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>
