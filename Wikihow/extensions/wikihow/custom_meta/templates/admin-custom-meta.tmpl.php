<style>
	.acm-section {
		padding: 1em;
		margin-bottom: 15px;
		border: 1px solid #dddddd;
		border-radius: 4px;
		background-color: #fff
	}
	.acm-section h3 {
		margin-bottom: 1em;
	}
	.acm-section table {
		border-collapse: collapse;
	}
	.acm-section td {
		vertical-align: top;
		padding: 1em;
		font-size: smaller;
	}
	.acm-section tr:nth-child(even) {
		background: #eee;
	}
	.acm-section th {
		padding: 1em;
		background: #e5eedd;
		text-align: left;
	}
	.acm-navigation {
		position: absolute;
		top: -5.25em;
		right: 1em;
	}
	.acm-navigation .button {
		margin-right: 0;
		display: block;
		float: left;
		border-radius: 0;
	}
	.acm-navigation .button:not(:last-child) {
		border-right: none;
	}
	.acm-navigation .button:first-child {
		border-top-left-radius: 4px;
		border-bottom-left-radius: 4px;
	}
	.acm-navigation .button:last-child {
		border-top-right-radius: 4px;
		border-bottom-right-radius: 4px;
	}
</style>
<div class="acm-navigation">
	<a class="button" href="/Special:AdminEditPageTitles">titles</a>
	<a class="button" href="/Special:AdminEditMetaInfo">meta descriptions</a>
	<a class="button <?= $type == 'title' ? 'primary' : '' ?>" href="/Special:AdminTitles">bulk titles</a>
	<a class="button <?= $type == 'title' ? '' : 'primary' ?>" href="/Special:AdminMetaDescs">bulk meta descriptions</a>
</div>
<form id='admin-upload-form' name='adminUploadForm' enctype='multipart/form-data' method='post' action='/Special:<?= $action ?>'>
	<input type="hidden" name="action" value="save-list" />
	<div class="acm-section">
		<h3>Download</h3>
		<button id="admin-get" class="button">retrieve current list</button><br/>
	</div>
	<div class="acm-section">
		<h3>Upload</h3>
		<input type="file" id="adminFile" name="adminFile" class="btn"><br/>
	</div>
	<br/>
	<div class="acm-section">
		<h3>Processing Results</h3>
		<div id="admin-result"></div>
	</div>
</form>
<br/>
<div class="acm-section" id="recent-summary">
	<h3>Recent Activity</h3>
	<?= $recent ?>
</div>
