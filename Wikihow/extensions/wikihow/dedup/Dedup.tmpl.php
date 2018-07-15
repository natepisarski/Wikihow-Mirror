<form action="/Special:Dedup?act=getBatch" method="post" id="dedup_form">
<textarea name="queries" id="queries">
</textarea><br/>
<input type="checkbox" name="internalDedup"/>Only find distinct queries within input</input>
<br>
<input type="checkbox" name="internalDupTool" id="internalDupTool"/>Put results into dup title tool</input>
<br><br>
<input type="submit"/>
</form>
<p style="display: none;" id="deduptool_message">
	Queries loaded into <a href="/Special:DedupTool" target="_blank">Dedup Tool</a>. Results can be download <a href="/Special:AdminDedupTool" target="_blank">here</a> once they are ready.
</p>
