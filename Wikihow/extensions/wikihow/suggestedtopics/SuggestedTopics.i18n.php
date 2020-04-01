<?php
	$messages['en'] = array(
		'suggest_header'		=> 'Suggest a Topic for an Article',
		'suggest_sub_header'	=> '<h2 class="subheader">Request an article using the form below</h2>',
		'suggestedarticles_header' => "How about writing how to...",
		'suggest_input_form'	=>
			"<table width='100%' class='request_table'>
				<tr><td class='label' style='vertical-align: middle;'>How to</td>
					<td style='vertical-align: middle;'><span class='input_border'><input class='input_med' type='text' name='suggest_topic' id='entry_howto' value=''></div></td>
				</tr>
				<tr>
					<td class='label'>Category:</td>
					<td><select class='input_med' name='suggest_category'>
						<OPTION value=''>(Please select a category...)</OPTION>
						$1
						</select>
					</td>
				</tr>
				<tr>
				    <td class='label'>Notification:</td>
				    <td><input type='checkbox' class='input_med' name='suggest_email_me_check' CHECKED> E-mail me when my how-to is written:<br/><br/>
					<span class='input_borderx' style='margin-top:10px;'><input type='text' class='input_med' name='suggest_email' value='$4' style='width:230px;' class='fancy_entry'></div>
				    </td>
				</tr>
				<tr>
					<td></td>
					<td>$2 $3</td>
				</tr>
				<tr>
				    <td></td>
				    <td>
					<a href='/Special:ListRequestedTopics' style='line-height:27px; margin-left:10px;'>View our current list of suggested topics</a>
					<input class='button primary' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' type='submit' value='Submit' style='float:left;'>
				    </td>
				</tr>
			</table>",
		'suggest_captcha_failed' => "Sorry, the words you entered did not match.",
		'suggest_please_select_cat'	=> "Please select a category to submit your request",
		'suggest_please_enter_title'	=> "Please enter a title to submit your request",
		'suggest_please_enter_email'	=> "Please enter your email to receive a notification when the article has been written",
		'suggest_notifications_form'	=> "
			<h3>Notifications</h3>
			<table class='request_table'><tr>
            	<td>
				<input type='checkbox' name='suggest_email_me_check' CHECKED> E-mail me when my how-to is written:
				<input type='text' name='suggest_email' value='$1' style='width:230px;'>
			</td></tr></table><br/><br/>",
				'suggest_submit_buttons'	=> "<input class='button white_button_100 submit_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' type='submit' value='Submit' style='float:right;'>
            <a href='/Special:ListRequestedTopics' style='line-height:27px;'>View our current list of suggested topics</a>",
		'suggest_confirmation'	=> "Thank You for Suggesting a Topic
				<br /><br />
				If you specified your email, we will notify you when the article is written.
				<br/><br/>
			If you'd like to check back to see if you article has been started, you can check here:<br />
			<a href='$1'>How to $2</a>.
				<br/><br/>
			Now would you like to:
			<ul>
			    <li><a href='/Special:ListRequestedTopics'>View other suggested topics</a></li>
			    <li><a href='/Special:RequestTopic'>Submit an other suggestion</a></li>
			    <li><a href='/Special:CommunityDashboard'>Visit our Community Dashboard</a></li>
			    <li><a href='/Main-Page'>Return to the homepage</a></li>
			</ul>
			",
		'suggested_show_by_category'	=> "<center>Show Suggested Topics By Category:
				<select id='suggest_cat' onchange='WH.SuggestedTopics.changeCat();'><OPTION value=''>All</OPTION>$1</select></center>",
		'suggested_list_topics_title'	=> "Find a Suggested Topic to Write About",
		'suggested_list_button_surprise' => "Surprise Me",
		'suggested_list_cat_all' => 'All',
		'suggested_list_cat_other' => 'Other',
		'suggested_topic_search_form' 	=> "<center><div style='text-align: center; margin-top: 25px; border: 0px solid #ccc;'><form action='/Special:$1'><table><tr><td>
			Or, search for a suggestion:  <input type'text' value='$2' name='st_search' style='width:290px;'>
			</td><td>
			 <input type='submit' id='st_search_btn' class='button white_button_100 submit_button' onmouseout='button_unswap(this);' onmouseover='button_swap(this);' type='button' value='Search'/>
			</td></tr></table>
				</form></div></center>",
		'suggested_notify_email_subject' => 'The how to you requested on wikiHow has been answered.',
		'suggested_notify_email_from' => 'wikiHow Team <support@wikihow.com>',
		'suggested_notify_email_plain' => 'Congratulations! The how to you requested on wikiHow has been answered.

Your request was: "How to $1".

Here is a link to the article you requested:

$2

If you like what you see, please take a moment to thank the authors who wrote this article for you.

Follow this link to thank the authors:

http://www.wikihow.com/index.php?title=Special:ThankAuthors&target=$3

We hope you enjoy the article!

Sincerely,

The wikiHow Team',


		'suggested_notify_email_html' => '<html>
<body style="background: #d7cfb8;">
<table width="100%" border="0" cellspacing="0" cellpadding="0">

   <tr>

      <td align="center">


         <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff;">
          <tr>
               <td align="center">

                  <table width="580" border="0" cellspacing="5" cellpadding="0" style="background: #ffffff;">
                     <tr>
                        <td align="center" class="permission">

                        </td>

                     </tr>
                     <tr>
                        <td class="header" align="left">
                           <table width="100%" height="90" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td><img src="$1/skins/WikiHow/images/wikihow.png" ></td>
                              </tr>
                           </table>
                        </td>

                     </tr>
                     <tr>
                        <td>

                           <table width="100%" border="0" cellspacing="0" cellpadding="0">
                              <tr>
                                 <td width="330" class="mainbar" align="left" valign="top">


                                    <h2 style="font-family: Arial; font-size: 22px; font-weight: normal; color: #000000; margin: 0; padding: 0;">Congratulations! The how to you requested on wikiHow has been answered.</h2>
                                    <p style="font-family: Arial; font-size: 16px; font-weight: normal; color: #4c4c4c;  margin: 10px 0 0 0;  padding: 0;"><br>Here is a link to the article you requested:<br><br><a style="font-family: Arial; font-size: 16px; font-weight: normal; color: #088A4B;" href="$3?utm_source=requested_topics_email&utm_medium=email&utm_term=article_link&utm_campaign=requested_topics_email">How to $2</a><br><br>If you like what you see, please take a moment to <a style="font-family: Arial; font-size: 16px; font-weight: normal; color: #088A4B;" href="http://www.wikihow.com/index.php?title=Special:ThankAuthors&target=$3&utm_source=requested_topics_email&utm_medium=email&utm_term=thank_authors&utm_campaign=requested_topics_email">thank the authors</a> who wrote this article.<br><br>You can also leave feedback or questions about the how to on the article\'s <a style="font-family: Arial; font-size: 16px; font-weight: normal; color: #088A4B;" href="$4?utm_source=requested_topics_email&utm_medium=email&utm_term=discussion_page&utm_campaign=requested_topics_email
">discussion page</a>.





                                 </td>


                                 <td width="30">&nbsp;</td>

                                 <td class="sidebar" align="left" width="192" valign="top">
                                    <h3 style="font-family: Arial; font-size: 16px; font-weight: normal; color: #1a1a1a; margin: 0; padding: 0;">Other Helpful Links:</h3>

                                    <ul style="margin: 0 0 0 24px; padding: 0;">
                                       <li><a style="font-family: Arial; font-size: 16px; font-weight: normal; color: #088A4B; text-decoration: none;" href="http://www.wikihow.com?utm_source=requested_topics_email&utm_medium=email&utm_term=wikihow_home&utm_campaign=requested_topics_email
">wikiHow Home Page</a></li>
                                       <li><a style="font-family: Arial; font-size: 16px; font-weight: normal; color: #088A4B; text-decoration: none;" href="http://www.wikihow.com/Special:CreatePage?utm_source=requested_topics_email&utm_medium=email&utm_term=start_new_article&utm_campaign=requested_topics_email
">Write an Article</a></li>
										<li><a style="font-family: Arial; font-size: 16px; font-weight: normal; color: #088A4B; text-decoration: none;" href="http://www.wikihow.com/Special:ListRequestedTopics?utm_source=requested_topics_email&utm_medium=email&utm_term=answer_requested_topic&utm_campaign=requested_topics_email
">Answer Requested Topic</a></li>

                                         <li><a style="font-family: Arial; font-size: 16px; font-weight: normal; color: #088A4B; text-decoration: none;" href="http://www.wikihow.com/Special:CommunityDashboard?utm_source=requested_topics_email&utm_medium=email&utm_term=wikihow_community&utm_campaign=requested_topics_email
">Community Dashboard</a></li>

<li><a style="font-family: Arial; font-size: 16px; font-weight: normal; color: #088A4B; text-decoration: none;" href="http://www.wikihow.com/Categories?utm_source=requested_topics_email&utm_medium=email&utm_term=browse_category&utm_campaign=requested_topics_email">Browse Categories</a></li>

                                    </ul>

                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                    </table>

                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">

                                    </table>



                                 </td>

                              </tr>
                           </table>

                        </td>
                     </tr>
                     <tr>
                        <td height="80" class="footer" align="center">
                           <p style="font-family: Arial; font-size: 13px; font-weight: normal; color: #333333;">wikiHow, 1010 El Camino Real #325, Menlo Park, CA 94025</p>
<p><span style="font-size: 70%; color: #BDBDBD;">You are receiving this email because you asked to be notified when your how-to request was written.<br> If you are receiving this email in error, please feel free to contact us at support@wikihow.com.</span></p>
                        </td>

                     </tr>

                  </table>

               </td>
            </tr>
         </table>

      </td>
   </tr>
</table>

</body>

</html>',
	'managesuggestedtopics'	=> "Manage suggested topics",
	'your_articles_header' => "Articles You Have Authored",
	'yourarticles_none' => "You currently have not written any articles based on suggestions. If you have just recently completed an article, there will be a delay before it appears on this page",
	'suggest_start_article' => 'Yes, I would like to start "How to $1"',
	'suggest_continue_searching' => "No, I'd like to continue searching..."
	);
