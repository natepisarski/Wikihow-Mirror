		<script src="/extensions/wikihow/common/download.jQuery.js"></script>
		<script src="/extensions/wikihow/translationlinkoverride/webtoolkit.aim.js"></script>
		<script type="text/javascript">
		function completeCallback(response) {
		}
		</script>
		<form id="admin-form" method="post" enctype="multipart/form-data" action="/Special:TranslationLinkOverride" onsubmit="return AIM.submit(this,{'onComplete':completeCallback})">
		<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;border:1px dotted;" >
			Add translation links		
		</div>
		<div>
		<div style="font-size: 13px; margin: 20px 0 7px 0;">
		Upload a file of URL(s) in TAB-separated format for the translation links. You can generate this file by saving from Excel in "txt format(tab-delimited)". The first column should be the article translated from, and the second column should be the article translated to.  (I.E. 
		<pre>http://www.wikihow.com/Kiss	http://es.wikihow.com/besar
http://www.wikihow.com/Love	http://es.wikihow.com/amar	
</pre>This means the English articles were translated into the corresponding Spanish articles.)  <br/>Translation links will be added to the database. A nightly, batch script, will use this database to add InterWiki links to the site where they don't already exist. The message <a href="/MediaWiki:addll-editsummary">&quot;<?php echo wfMessage('addll-editsummary') ?>&quot;</a> will be added in the edit summary when it automatically adds interwiki links. The message <a href="/MediaWiki:removell-editsummary">&quot;<?php echo wfMessage("removell-editsummary") ?>&quot;</a> will be added in the edit summary when interwiki links are removed.
		</div>
		<input type="file" id="upload" name="upload" /><br/><br/>
		<input style="width:150x;height:50px;font-size:20pt;text-align:left;" id="create_links_btn" type="submit" value="Create links"/>
		</form><br/><br/><br/><br/>
		<a href="/Special:TranslationLinkOverride?action=search">Search for existing links</a>
		<div id="results">
		</div>
		</div>
		
