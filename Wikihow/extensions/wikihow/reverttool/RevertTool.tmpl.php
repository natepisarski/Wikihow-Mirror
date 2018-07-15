		<script src="/extensions/wikihow/common/download.jQuery.js"></script>
		<form id="admin-form" method="post" action="/Special:RevertTool">
		<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;border:1px dotted;">
			Revert edits		
		</div>
		<div>
		Revert the last edits by 
			<select id="revert-user" name="revert-user">
			<option value="InterwikiBot" >InterwikiBot</option>
			<option value="Gersh">Gersh</option>
			<option value="Vidbot">Vidbot</option>
			<option value="AlfredoBot">AlfredoBot</option>
			<option value="MiscBot">MiscBot</option>
		</select>.<br/><br/> 	
		<div style="font-size: 13px; margin: 20px 0 7px 0;"> 
		<i>Please note, this may be slow for a lot of URLs. </i><br/>
List URL(s), one per line (If this is run on non-English domains, English URLs will revert URLs connected by translation links):
		<textarea id="page-list" cols="70" rows="10" type="text" name="page-list"></textarea>
		Message of "<a href="/MediaWiki:<?php print $msgName ?>"><?php print $msg; ?></a>" will be added to revert.<br/>
		<input style="width:100px;height:30px;" id="revert_pages_btn" type="button" value="Revert Pages"/>
		<div id="results">
		</div>
		</div>
		</div>
		<script type="text/javascript">
		(function($) {
			$("#revert_pages_btn").click(function(){
				$("#results").html("");
				$("#revert_pages_btn").attr("disabled","disabled");
				$.ajax({url:"/Special:RevertTool",type:"POST",data:{'page-list':$("#page-list").val(),'revert-user':$("#revert-user").val()},dataType:'json', success:function(links) {
					$("#page-list").val("");
					$("#revert_pages_btn").removeAttr("disabled");
					var msg="";
					for(var l in links) {
						if(links[l] != null) {
							msg += links[l]['url'];
							if(links[l]['success'] == 1) {
								msg += "<span style=\"color:green\">Reverted</span>";
							}
							else {
								msg += "<span style=\"color:red\">" + links[l]['msg'] + "</span>";
							}
							msg += "<br/>";
						}
					}
					$("#results").html(msg);
				}, error:function() {
					alert("Failed to revert links");
					$("#revert_pages_btn").removeAttr("disabled");

				}
				});
				return false;	
			});
		})(jQuery);
		</script>
