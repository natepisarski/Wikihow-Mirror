<div class="createpage_finished">
	<span id="create_top">Thank you for writing an article on wikiHow.  Please check the box below if you'd like us to email you when your article gets updated or reaches other exciting milestones. You can also choose to share your article with others by using the buttons below to email it your friends, post it to Facebook, or post it to Twitter.</span>
	<br><br>

	<div>
		<p><input type='checkbox' id='email_notification' name='email_notification' /> <label for="email_notification">E-mail me updates for this article:</label></p>
		<p><?=$authoremail?></p>
	</div>

	<div>
		<p><input type='checkbox' id='email_friend_cb' name='email_friend_cb' /> <label for="email_friend_cb">E-mail this article to my friends:</label></p>
		<p><textarea rows='4' cols='30' id='email_friends' onkeydown="document.getElementById('email_friend_cb').checked = true;"></textarea></p>
		<p class="minor_text">Enter one or multiple email addresses separated by commas.</p>
	</div>

	<div>
		<a href="#" class="post_to_facebook" onclick="javascript:<?=$share_fb?>;return false;" alt="Post to Facebook"></a>
		<a href="#" class="post_to_twitter" onclick='WH.shareTwitter("aen");gatTrack("Author_engagement","Twitter_post","Publishing_popup");return false;' alt="Post to Twitter"></a>
		<br class="clearall" />
	</div>

	<div>
		<br />
		<p><input type='checkbox' id='dont_show_again' name='dont_show_again' />&nbsp; <label for="dont_show_again">Don't show me this dialog next time</label></p>
		<p><input id='author_email_notification_done' type='button' class='button primary submit_button' value='Done' onclick='cp_finish();' /></p>
	</div>
</div>
