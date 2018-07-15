<script type='text/javascript'>
	(function($) {
	    $(document).on('click', '#if_submit', function (e) { 
			if ($('#if_urls').val().length) {
				e.preventDefault(); 
				var data = {'if_urls' : $('#if_urls').val(), 'a' : 'reset_urls'}; 
				$.post("/" + wgPageName, data, function(res) { 
					$('#if_result').html(res); 
				});
			}
    	});
	})(jQuery);
</script>
<h3>Image Feedback Report</h3>
<p>
<a href='/x/files/image_feedback.xls?$ts'>Download Images Feedback Report</a> (Updates nightly)
</p>
<h3>Reset Image Urls</h3>
<textarea class="urls" rows="25" name="urls" id="if_urls"></textarea>
<button id="if_submit" style="padding: 5px;">Reset Urls</button>
<div id='if_result' style="margin-top: 10px"></div>


