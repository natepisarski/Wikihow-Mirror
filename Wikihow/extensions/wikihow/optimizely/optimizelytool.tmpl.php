<link rel="stylesheet" type="text/css" href="/extensions/wikihow/titus/jquery.sqlbuilder.css" />
<style type="text/css">
.urls {
	margin-top: 5px;
	height: 300px;
	width: 600px;
}
</style>

<script type="text/javascript">

$(document).ready(function() { 

    $('.fetch').click(function(){
		var data = {
			'urls': $('.urls').val(),
		};
		$.download('/' + wgPageName, data);           
    
		return false;
    }); 


    
});


</script>
<p> Check whether given URL(s) are optimizely enabled </p>
<textarea class="urls" rows="500" name="urls" id="urls"></textarea><br/>
<button class="fetch" style="padding: 5px;" value="CSV">Check</button>
</div>
