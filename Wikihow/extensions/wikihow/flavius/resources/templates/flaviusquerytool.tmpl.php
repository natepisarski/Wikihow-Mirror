<div class='loading'>
	<center><!-- I'm horrible, I know -->
		<p>Loading</p>
		<p><img src='../../../skins/common/images/spinner.gif'></img></p>
	</center>
</div>

<div class='tqt' style='display: none'>
	<div class='tqt-query-builder'>
		<div class='tqt-query-builder-gui'>
			<p>
				<a href="https://docs.google.com/a/wikihow.com/document/d/1yiixeaX2dqsh8WuhdgjchBT7sL7-NJgM3YjVWLL9v2g/edit?usp=sharing">Flavius documenation</a>
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
		<h3>Users/Dates</h3>
		<div class="sqllang query-builder form-inline">
			<div class="rules-group-container">
				<div>
					<label for='days'>Time range</label>
					<select id="days">
						<option value="all">Across All Time</option>
						<option value="lw">Across Last Week</option>
						<option value="1">Across 1 Day</option>
						<option value="7">Across 7 Days</option>
						<option value="14">Across 14 Days</option>
						<option value="30">Across 30 Days</option>
						<option value="45">Across 45 Days</option>
						<option value="60">Across 60 Days</option>
					</select>
				</div>
				<div>
					<label for='user-filter'>Users</label>
					<select id='user-filter' name='user-filter'>
						<option value='active' selected>Active users</option>
						<option value='all'>All users</option>
						<option value='these'>These users...</option>
					</select>
				</div>
				<textarea class="userlist" rows="1000" name="userlist" id="userlist"></textarea>
			</div>
		</div>
	</div>

	<div class='tqt-query-builder tqt-query-builder-buttons query-builder'>
		<button type="button" class="fetch btn btn-sm btn-success" value="CSV"><i class='fa fa-download'></i>&nbsp;Gimme</button>
		<button type='button' id='getsql' class="btn btn-sm"><i class='fa fa-code'></i>&nbsp;SQL</button>
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
	var rawfields = <?= json_encode($fields) ?>;
</script>
