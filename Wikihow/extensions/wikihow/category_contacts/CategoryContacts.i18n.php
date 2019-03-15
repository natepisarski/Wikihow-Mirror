<?
$messages = array();

$messages['en'] =
	array(
		//CategoryContacts page
		'cc_add_hdr' => 'Add Email Addresses or Categories',
		'cc_add_inst' => 'First, enter email addresses and categories in the Add tab <a href="$1" target="_blank">here</a>.<br /><br />Then, press the Add button:',
		'cc_stop_hdr' => 'Turn off Canned Messages',
		'cc_stop_inst' => 'First, enter email addresses to be turned off in the Stop tab <a href="$1" target="_blank">here</a>.<br /><br />Then, press the Stop button:',
		'cc_add_result_good' => '$1 {{PLURAL:$1|row|rows}} successfully added.',
		'cc_add_result_bad' => '<p>$1 {{PLURAL:$1|row|rows}} had {{PLURAL:$1|an|}} invalid {{PLURAL:$1|category|categories}}. Please correct {{PLURAL:$1|it|them}} and try again:</p>$2',
		'cc_stop_result_good' => 'Emails will no longer be sent to $1 {{PLURAL:$1|address|addresses}}.',
		'cc_stop_result_bad' => '<p>$1 {{PLURAL:$1|row|rows}} had {{PLURAL:$1|an|}} invalid email {{PLURAL:$1|address|addresses}}. Please correct {{PLURAL:$1|it|them}} and try again:</p>$2',
		'cc_error' => 'An error occurred while processing your request.',
		'cc_bad_cat_exists' => ': already exists for $1',
		'cc_bad_cat_invalid' => ': not a valid category for $1',
		'cc_bad_cat_none' => 'no category for $1',
		'cc_err_already_flagged' => ': sendflag already turned off',
		//CategoryContactMailer page
		'ccm_inst' => 'This page sends bulk emails to addresses linked to the chosen category in the category_contacts table.<br /><br />USE WITH CAUTION! This tool sends real emails to real people and cannot be undone.',
		'ccm_cat_ph' => 'Start typing to choose a category',
		'ccm_mwm_hdr' => 'Link to mediawiki message containing content of email:',
		'ccm_subject_hdr' => 'Email subject line:',
		'ccm_max_num' => 'Maximum number of addresses to email:',
		'ccm_test_hdr' => 'Address to send a test message to:',
		'ccm_send_btn' => 'Send Emails',
		'ccm_test_btn' => 'Send test now',
		'ccm_bad_email' => 'Invalid test email',
		'ccm_err_cat' => 'no category',
		'ccm_err_mwm' => 'no mediawiki message',
		'ccm_err_sub' => 'no subject line',
		'ccm_err_max' => 'invalid max number',
		'ccm_send_confirm' => 'Are you sure you want to email $1 {{PLURAL:$1|user|users}}?',
		'ccm_sent' => 'Done! Sent {{PLURAL:$1|an email|emails}} to $1 {{PLURAL:$1|address|addresses}} for the category $2.',
		'ccm_test_failed' => 'Test sending email failed.',
		'ccm_unsub_reason' => 'Contact unsubscribed to wikiHow emails.',
		'ccm_unsub_success' => "You've been unsubscribed from receiving these emails.",
		'ccm_unsub_fail' => 'Uh oh, something went wrong. Contact krystle@wikihow.com to make sure you get fully unsubscribed.',
		'ccm_max_msg' => '<span id="num_users">$1</span> contacts in this category',
		'ccm_max_sent' => 'Sent maximum amount of emails to this contact.',
		'ccm_source' => 'Source:',
		'ccm_num_contacted' => 'Previously contacted:',
		'ccm_any' => 'Any',
		'ccm_range' => 'Range',
		'ccm_range_delim' => ' to ',
	);
