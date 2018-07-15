<div class='domitian_section'>
	<h3 class='domitian_header'>Description</h3>
	<p>
		Domitian Segments aids in understanding how usage breaks down across mobile/desktop and anon/logged in.<br />
		See also <a href='/Special:DomitianSummary'>Domitian Summary</a> and <a href='/Special:DomitianDetails'>Domitian Details</a>.
	</p>
</div>

<div class='domitian_section'>
	<h3 class='domitian_header'>Select tools</h3>
	<div class='domitian_checkboxes' id='domitian_tools'>
		<form id='domitian_tools_form'>
			<ul class='domitian_checkbox_grid'>
<? foreach ($tools as $tool) { ?>
				<li><label>
					<input type='checkbox' name='domitian_tool' id='<?=$tool['id']?>' value='<?=$tool['id']?>' checked><?=$tool['name']?>
				</label></li>
<? } ?>
			</ul>
		</form>
	</div>

	<div class='clearall'></div>

	<div class='domitian_buttons'>
		<a class='button secondary' id='domitian_tools_select_all'>Select all</a>
		<a class='button secondary' id='domitian_tools_select_none'>Select none</a>
	</div>
</div>

<div class='domitian_section'>
	<h3 class='domitian_header'>Select date range</h3>
	<p>
		Note: If your browser does not support a &quot;date picker&quot;, ensure the dates are written in <b><tt>YYYY-MM-DD</tt></b> format.<br />
		Note: Current internal time: <?=$utctime?><br /><br />
	</p>
	<div class='domitian_date_range'>
		<span>From: <input type='date' id='domitian_date_from'></span>
		<span>To: <input type='date' id='domitian_date_to'></span>
	</div>
</div>

<div class='domitian_buttons'>
	<a class='button primary' id='domitian_generate'>Generate CSV</a>
	<a class='button secondary' id='domitian_show_queries'>Show queries</a>
</div>

<div class='domitian_section domitian_hidden' id='domitian_queries'>
	<h3 class='domitian_header'>SQL queries</h3>
</div>

