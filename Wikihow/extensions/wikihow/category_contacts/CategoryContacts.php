<?php

if ( !defined('MEDIAWIKI') ) die();
    
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Category Contacts',
	'author' => 'Scott Cushman',
	'description' => 'Tool for a keeping track of potential contributor contacts for help with specific categories',
);

$wgSpecialPages['CategoryContacts'] = 'CategoryContacts';
$wgSpecialPages['CategoryContactMailer'] = 'CategoryContactMailer';
$wgAutoloadClasses['CategoryContacts'] = dirname( __FILE__ ) . '/CategoryContacts.body.php';
$wgAutoloadClasses['CategoryContactMailer'] = dirname( __FILE__ ) . '/CategoryContacts.body.php';
$wgExtensionMessagesFiles['CategoryContacts'] = dirname(__FILE__) . '/CategoryContacts.i18n.php';

$wgResourceModules['ext.wikihow.CategoryContacts'] = array(
	'scripts' => array(
		'category_contacts.js'
	),
	'messages' => array(
		'cc_error'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/category_contacts',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile')
);

$wgResourceModules['ext.wikihow.CategoryContactMailer'] = array(
	'scripts' => array(
		'category_contact_mailer.js'
	),
	'messages' => array(
		'ccm_bad_email',
		'ccm_err_cat',
		'ccm_err_mwm',
		'ccm_err_max',
		'ccm_send_confirm',
		'ccm_max_msg',
		'ccm_range_delim'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/category_contacts',
	'position' => 'bottom',
	'targets' => array('desktop', 'mobile'),
	'dependencies' => array(
		'jquery.ui.autocomplete',
	)
);

$wgResourceModules['ext.wikihow.CategoryContacts.styles'] = array(
	'styles' => array(
		'category_contacts.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/category_contacts',
	'position' => 'top',
	'targets' => array('desktop', 'mobile')
);