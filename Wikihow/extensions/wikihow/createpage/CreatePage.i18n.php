<?
$messages = array();

$messages['en'] = 
        array(
			'createpage_congratulations' => 'Congratulations - Your Article is Published',
			'createpage' => 'Create a Page',
			'createpage_instructions' => 'Enter the title of wikiHow you wish to create and hit submit:',
			'createpage_new_head' => 'I know what to write about',
			'createpage_topic_sugg_head' => 'I want topic suggestions',
			'createpage_other_head' => 'I have other writing I want to share',
			'createpage_new_details' => "Enter your article's title below and click on Submit. <br/>Be sure to phrase it in the form of a \"how-to\" (e.g. How to <b>Walk a Dog</b>)",
			'createpage_topic_sugg_details' => "Enter any keyword(s) and we will suggest some unwritten topics for you to write. <br/>",
			'createpage_other_details' => "Do you want to publish an article you already wrote on your computer or another website? Just email us the file or URLs and we'll post it to wikiHow for you:<br /><br />Email <a id='gatPubAssist' href='mailto:publish@wikihow.com'>publish@wikiHow.com</a>",
			'createpage_related_head' => 'Hey! We think we might already have an article with a duplicate title as "<i>$1</i>". If one of the titles listed below means the <b>exact same thing,</b> please select it. <u>Do not select one of the titles just because it is on a similar topic or a related topic.</u> Instead select "<i>None of these are duplicates. I am ready to create the article</i>".',
			'createpage_related_none' => 'None of these are duplicates. I am ready to create the article!',
			'createpage_related_nomatches' => 'We did not find any potential related articles, it seems like a good topic to write about!',
			'createpage_nomatches' => 'Sorry, we had no matches for those keywords. Please try again.',
			'createpage_matches' => 'Your search returned the following matches:',
			'createpage_tryagain' => "Didn't find what you were looking for? Try another search here:",
			'createpage_next' => 'Next',
			'createpage_cancel' => 'Cancel',
			'createpage_search_again' => 'Search Again',
			'createpage_enter_title' => 'Enter Article Title',
			'managesuggestions' => "Manage suggestions", 
			'managesuggestions_boxes' => "<div class='cpbox'>
			<h3>Search for existing suggestions to delete</h3>
			<form method='POST' onsubmit='return checkform()' name='createform_topics'>
			<input type='text' name='q' size='50'>
			<br/>
			<input type='submit' value='Submit'>
			</form>
			</div>
			<div class='cpbox'>
			<h3>Add new suggestions</h3>
			<form method='POST' name='managesuggestions_add'>
			<textarea name='new_suggestions' style='width:500px; height:100px;'></textarea><br/>
			<input type='checkbox' name='formatted'> My suggestions are already formatted<br/>
			<input type='submit' value='Add'>
			</form>
			</div>
			<div class='cpbox'>
			<h3>Delete suggestions</h3>
			<form method='POST' name='managesuggestions_delete'>
			<textarea name='remove_suggestions' style='width:500px; height:100px;'></textarea><br/>
			<input type='submit' value='Delete'>
			</form>
			</div>
	",
			'managesuggestions_log_add' => '$1 added a suggestion for "$2"',
			'managesuggestions_log_remove' => '$1 removed the suggestion for "$2"',
			'createpage_fromsuggestions' => "<div class='cpbox'>
			<h3>Create a page</h3>
					<form action='$2' method='GET'>
					<input type='hidden' name='action' value='edit'/>
					<input type='hidden' name='suggestion' value='1'/>
					<input type='text' style='width: 300px;' name='title' value=\"$1\">
					<input type='submit' value='Create page'>
					</form>
				</div>
			",
			'cp_loading' => 'Loading...',
			'createpage_review_options' => "<div><center>
					<a onclick='closeModal();' class='button'>Continue Editing</a> 
					<input type='button' value='Save & Publish' onclick='saveandpublish(); return false;' class='button primary' />
					</center></div>",
			'cp_title_head' => 'I know what to write about',
			'cp_title_ph' => 'Your Article Title',
			'cp_title_ph2' => 'enter another title here...',
			'cp_topic_ph' => 'Type any keywords here...',
			'cp_title_submit' => 'Get Started',
			'cp_title_submit2' => 'Write Article',
			'cp_topic_submit' => 'Get Suggestions',
			'cp_file_submit' => 'Send File',
			'cp_title_exists' => 'That article already exists!',
			'cp_title_exists_top' => 'Looks like we already have an article on <a href="$2" target="new">How to $1</a>. You can either:',
			'cp_title_exists_details' => "Choose a specific title that shows how your article is different from other articles about similar topics. Please don't create a new article if we already have one about the exact same topic. Duplicate articles will be <a href='/wikiHow:Merge-Policy' target='new'>merged</a> or <a href='/wikiHow:Deletion-Policy' target='new'>deleted</a>.",
			'cp_existing_btn' => 'Edit Existing Article',
			'cp_title_new' => 'Almost there! Are any of these topics the same as yours?',
			'cp_title_new_details' => 'Do any of these titles mean exactly the same thing as <b>How to $1</b>?',
			'cp_title_new_option' => 'We already have an article on this. Please add to it instead of creating a duplicate.',
			'cp_contribute_btn' => 'Contribute',
			'cpr_add_to' => 'Add to this article',
			'cpr_write_something' => 'Or, write about something else',
			'cp_left_btn' => 'Write about something else',
			'cp_right_btn' => 'Add to the existing article',
			'cp_write_it' => 'Write My Article',
			'cp_ready_write' => 'Great! Your article How to $1 is ready to be written!',
			'cp_related_none' => 'None of these are exactly the same as $1.',
			'cp_topic_matches_hdr' => "You're in luck! wikiHow is still missing some articles on &quot;$1.&quot;",
			'cp_topic_matches_text' => "Click your preferred title below to start a new article.",
			'cp_topic_tryagain' => "Didn't find what you were looking for? Try another search above.",
			'cp_no_topics' => 'No suggestions found, please try another topic.',
			'cp_other_details' => "Do you want to publish an article you already wrote? Just email it to us at <a id='gatPubAssist' href='mailto:publish@wikihow.com'>publish@wikiHow.com</a> and we'll post it to wikiHow for you.",
			'usertalk_first_article_message' => "Congrats on starting your first wikiHow article!
			
I just wanted to touch base in case you were wondering what happens next :) All new articles go through a quality review process to ensure they follow our community guidelines. If your article meets those guidelines, you'll get a note letting you know that it's been promoted.

In the meantime you can continue editing to improve your article, or try visiting our [[Special:CommunityDashboard|Community Dashboard]] for other ways to help. You might get a kick out of flipping through the [[Special:EditFinder/Topic|Topic Greenhouse]] to find articles in your area of expertise so you can add your knowledge there, too!",
		);
