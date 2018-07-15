<script type="text/javascript">
function Delete(fromLang, fromID, toLang, toId) {
	if(confirm("Are you sure you want to delete the link (" + fromLang + "," + fromID + "," + toLang + "," + toId	+ ")")) {
		$.ajax({url:"/Special:TranslationLinkOverride",data:{'action':'dodelete','fromLang':fromLang,'fromId':fromID,'toLang':toLang,'toId':toId},dataType:'json',success:function(data){
				if(data.success == true) {
					alert("Successfully deleted link");	
				}
				else {
					alert("Link already deleted");	
				}
		}
		});
	}
}
$(document).ready(function(){
$("#searchBtn").click(function(){
	var lang = $("#lang").val();
	var aid = $("#article_id").val();
	$.ajax({url:'/Special:TranslationLinkOverride',data:{'action':'fetchlinks','id':$("#article_id").val(),'lang':$("#lang").val() }, dataType:'json',success:function(data){
		var txt="";
		for(var i in data) {
			txt += "<tr><td>" + data[i].fromLang + "</td><td>" + data[i].fromID + "</td><td>" + data[i].fromURL + "</td><td>" + data[i].toLang + "</td><td>" + data[i].toURL + "</td><td>" + data[i].toID + "</td><td><a href=\"#\" onclick=\"Delete('" + data[i].fromLang + "'," + data[i].fromID + ",'" + data[i].toLang + "'," + data[i].toID + ")\">Delete</a></td></tr>\n";
		}
		$("#langTable tbody").html(txt);
	}});
});
});
</script>
<div style="font-size: 13px; margin: 20px 0 7px 0;">

</div>
<label for="lang">Language</label><select id="lang"><?php foreach($langs as $lang) { ?>
	<option value="<?php print $lang; ?>"><?php print $lang; ?></option>
<?php } ?> </select>
<label for="id">Article ID</label><input type="text" id="article_id"><button id="searchBtn">Search</button>
<br/><br/><br/>
<hr/>
<table id="langTable">
<thead><tr><td>From Language</td><td>From Article Id</td><td>From URL</td><td>To Lang</td><td>To URL</td><td>To Article Id</td></tr></thead>
<tbody>
</tbody>
</table>
