<div class='loading'>
	<center><!-- I'm horrible, I know -->
		<p>Loading</p>
		<p><img src='../../../skins/common/images/spinner.gif'></img></p>
	</center>
</div>
<!-- the inline style here prevents a FOUC -->
<div class='tqt' style='display: none'>
	<div class='tqt-query-builder'>
		<div class='tqt-query-builder-gui'>
			<p>
			Titus <a href="https://docs.google.com/a/wikihow.com/spreadsheet/ccc?key=0Ag-sQmdx8taXdC1BWWlFdFVBa3FJM09rZUZhemliZEE#gid=0">cheat sheet</a>
			</p>
			<h3>Filters</h3>
			<div class="sqlwhere"></div>
			<h3>Fields</h3>
			<div class="query-builder form-inline sqlselect">
				<dl class='rules-group-container'>
					<dt class='rules-group-header'>
					</dt>
					<dd class='rules-group-body'>
						<ul id='selectcontainers' class='rules-list'>
						</ul>
					</dd>
					<dt class='rules-group-header'></dt>
					<dd>
						<div class='btn-group group-actions'>
							<button type='button' id='newselect' class='btn btn-xs btn-success'><i class='fa fa-plus-circle'>&nbsp;Add field</i></button>
						</div>
						<div class='btn-group group-conditions'></div>
						<div class='error-container'></div>
					</dd>
				</dl>
			</div>
		</div>

		<!-- Placing this style in the CSS tends to make the ACE editor too small
				 due to style/js rendering and execution order, so we inline it here -->
		<div style='display: none' class='tqt-query-builder-sql'>
			<div id='tqt-query-builder-ace'></div>
		</div>
	</div>

	<div class='tqt-query-all'>
		<h3>Languages/URLs</h3>
		<div class="sqllang query-builder form-inline">
			<div class="rules-group-container">
				<div>
					<label for='page-filter'>Filter by</label>
					<select id='page-filter' name='page-filter'>
						<option value='urls'>URLs</option>
						<option value='all'>All languages</option>
						<option value='noen'>Non-English</option>
						<?php foreach($languages as $lg) { ?>
						<option value="<?php print $lg['languageCode'] ?>"><?php print $lg['languageCode'] ?> - <?php print $lg['languageName'] ?></option>.
						<?php } ?>
					</select>
				</div>
				<textarea class="urls" rows="1000" name="urls" id="urls"></textarea>
				<br/>
				<input id="ti_exclude" name="ti_exclude" type="checkbox"></input>&nbsp;Filter invalid, not found and <strong>wikiphoto-article-exclude-list</strong> articles
			</div>
		</div>
	</div>

	<div class='tqt-query-builder tqt-query-builder-buttons query-builder'>
		<button type="button" class="fetch btn btn-sm btn-success" value="CSV"><i class='fa fa-download'></i>&nbsp;Gimme</button>
		<button type='button' id='getsql' class="btn btn-sm"><i class='fa fa-code'></i>&nbsp;SQL</button>
	</div>

	<hr>
	<div class='tqt-type query-builder'>
		<h3>Vault</h3>
		<div class='rules-group-container'>
			<select id='vaultselect'>
				<option value='new' selected>New query...</option>
				<?
					if (!empty($curUserQueries)) {
				?>
						<optgroup label='<?=$curUser?>'>
				<?
						foreach ($curUserQueries as $query_info) {
				?>
							<option value='<?=$query_info['id']?>'><?=$query_info['name']?></option>
				<?
						}
				?>
						</optgroup>
				<?
					}
				?>
				<?
					foreach ($allQueries as $user=>$queries) {
				?>
						<optgroup label='<?=$user?>'>
				<?
						foreach ($queries as $query_info) {
				?>
							<option value='<?=$query_info['id']?>'><?=$query_info['name']?></option>
							<!-- <?=$query_info['']?> -->
				<?
						}
				?>
						</optgroup>
				<?
					}
				?>
			</select>
			<div id='tqt-vault' class='tqt-vault'>
				<input id='qv-name' name='qv-name' class='qv-input' placeholder='Query name'></input>
				<input id='qv-desc' name='qv-desc' class='qv-input' placeholder='Query description'></input>
				<button type="button" class='tqt-save-vault tqt-vault-post btn btn-xs'><i class='fa fa-save'></i>&nbsp;Save</button>

				<!-- These styles are inlined because they otherwise wreak havoc with other
						 styles from other plugins -->
				<button type="button" class='tqt-save-your-vault tqt-vault-post btn btn-xs' style='display: none'><i class='fa fa-save'></i>&nbsp;Copy</button>
				<button type="button" class='tqt-update-vault tqt-vault-post btn btn-xs' style='display: none'><i class='fa fa-save'></i>&nbsp;Update</button>
				<button type="button" class='tqt-update-vault tqt-vault-post tqt-delete-vault btn btn-xs btn-danger' style='display: none'><i class='fa fa-trash'></i>&nbsp;Delete</button>
			</div>
		</div>
	</div>
</div>

<div style='display:none;' id='sqlselect_template'>
	<li class='rule-container'>
		<div class="rule-header">
			<div class="btn-group pull-right rule-actions">
				<button type="button" class="btn btn-xs btn-danger deletebutton">
					<i class="fa fa-minus-circle">&nbsp;Delete</i>
				</button>
			</div>
		</div>
		<div class='drag-handle'>
			<i class='fa fa-sort'></i>
		</div>
		<div class='rule-filter-container'>
			<select id='changeme'></select>
		</div>
		<div class='rule-operator-container'>
			<label for='name'>Labeled as</label>
			<input type='text' name='name' class='sqllabel'></input>
		</div>
	</li>
</div>

<script type='text/javascript'>
	var rawfields = <?= $dbfields ?>;
</script>
