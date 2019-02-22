<? if ($style == 'url'): ?>
	<h2>URL Config Editor</h2>
<? endif; ?>

<div id="#cs-edit">
	<form method='post' action='/Special:<?= $specialPage ?>'>
	<span>Select the tag or config data you want to edit.</span><br>
	<br>
	<select id='config-key'>
		<option value=''>--</option>
	<? if ($bURL): ?>
		<option value="wikihow-watermark-article-list">wikihow-watermark-article-list</option>
		<option value="wikiphoto-article-exclude-list">wikiphoto-article-exclude-list</option>
		<option value="wikihow-methodtoc-test-01">wikihow-methodtoc-test-01</option>
		<option value="wikihow-methodtoc-test-02">wikihow-methodtoc-test-02</option>
		<option value="wikihow-nointro-test">wikihow-nointro-test</option>
	<? else: ?>
		<? foreach ($configs as $config): ?>
			<option value='<?= $config ?>'><?= $config ?></option>
		<? endforeach; ?>
	<? endif; ?>
	</select><br>
	</form>
</div>

<div id="edit-existing" style="display:none">
	<br>
	<? if ($bURL) echo '<b>Add new:</b>'; ?>
	<div>
		<p id="edit-restriction"></p>
		<textarea id='config-val' type='text' rows='20' cols='70'></textarea>
		<p id="article-list-notice">This is an <b>article list</b>. It can be a list of article IDs, article names, or URLs. You will be warned if an
			article could not be found. Article lists are more efficient than non-article list config messages because they are parsed when they are
			saved by this console.</p>
		<p class="display-prob">A/B probability (optional): <input id="change-prob" name="change-prob" type="number"/><br></p>
	</div>
	<div>
		<button id='config-save' disabled='disabled'>save</button> &nbsp; &nbsp; &nbsp; &nbsp;
		<button id='config-delete' disabled='disabled'>delete</button><br>
		<br>
	</div>
	<div class='admin-result change-result'></div>
	<div id='url-list'></div>
	<input type='hidden' id='display-style' value='<?=$style?>' />
</div>

<div id="cs-add">
	<br>
	Or, create a new tag. <a id="create-new-link" href="#">Add one now</a>.<br>
</div>

<div id="add-new" style="display:none">
	<br>
	<p>New tag name: <input id="new-key" name="new-key" type="text" size="30" /><br></p>
	<p><label><input id="is-article-list" name="is-article-list" type="checkbox" checked="checked" /> Is this an article list?</label>
		(<a id="article-explain" href="#">explain</a>)<br></p>
	<p class="display-prob">A/B probability (optional): <input id="new-prob" name="new-prob" type="number"/><br></p>
	<br>
	<div>
		<textarea id='config-val-new' type='text' rows='20' cols='70'></textarea>
	</div>
	<button id='config-create' disabled='disabled'>save</button> &nbsp; &nbsp; &nbsp; &nbsp;<a id="config-create-cancel" href="#">cancel</a><br>
	<br>
	<div class='admin-result add-result'></div>
	<div>
		<a id="reload-page" href="#">refresh page</a><br>
	</div>
</div>

<div id="cs-edit-history">
	<br>
	<br>
	<p><span style="text-decoration: underline">History</span> (last 20 changes)</p>
	<tt>
	<table>
		<colgroup>
			<col style="width:10%">
			<col style="width:14%">
			<col style="width:76%">
		</colgroup>
		<tbody>
			<tr>
				<th></th>
				<th>When</th>
				<th>Summary</th>
			</tr>
			<? foreach ($history as $item): ?>
				<tr>
					<td>
						<a href="#" class="csh-view" data-cshid="<?= $item['csh_id'] ?>">details</a>
					</td>
					<td>
						<?= date( 'Y-m-d', wfTimestamp(TS_UNIX, $item['csh_modified']) ) ?>
					</td>
					<td>
						<?= $item['csh_log_short'] ?>
					</td>
				</tr>
			<? endforeach; ?>
		</tbody>
	</table>
	</tt>
</div>

<div id="dialog-box" title="ROSKOMNADZOR informs">
	<h3>Article lists</h3>
	<p>Messages that are a list of wikiHow articles should always be saved as an <b>article list</b>. Article lists are treated
	more efficiently than basic lines of text that must be parsed with every server request. Article lists are more efficient because
	the list name is treated as a <b>tag</b> that loaded with all other articles tags once per request.</p>
	<h3>Examples of article lists</h3>
	<ul>
		<li>Article IDs, one per line, describing which articles to exclude from a particular feature.</li>
		<li>Article URLs, one per line, that are part of a new test feature. If this feature is
		  part of an A/B test, you can set the probability that the A variant is displayed.</li>
	</ul>
	<br>
	<h3>Examples of things that shouldn't be article lists</h3>
	<ul>
		<li>A list of usernames who have access to a fancy new feature.</li>
		<li>Categories from which a new tool shouldn't fetch articles.</li>
		<li>Nordic gods not significant enough to have their own page on Wikipedia.</li>
		<li>Collections of lesser known facts about the pets of Bebeth and Scott.</li>
	</ul>
	<br>
	<h3>Avoid non-article lists</h3>
	<p>Note that this latter category is treated less efficiently by the system, so care should be
	  taken that messages like these are not used in the normal flow of serving average requests on
	  wikiHow. These message are inefficient because they must be requested serially from memcache or database, and they are
	  usually parsed with every request. Consider using a more efficient format such as: keeping an
	  array of values in php code, using a Mediawiki message if the data is small, or using a new table if the
	  data is large.</p>
</div>

<div id="csh-details-dialog-box" title="Change details" style="display:none">
	<p class="underline">Key</p>
	<p class="csh-key"></p>

	<p class="underline">Summary</p>
	<p class="csh-summary"></p>

	<p class="underline">Changes</p>
	<p class="csh-changes"></p>
</div>
