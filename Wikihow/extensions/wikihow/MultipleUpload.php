<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'MultipleUpload',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Allows users to upload several files at once.',
);
$wgMaxUploadFiles = 5;

$wgExtensionMessagesFiles['MultipleUpload'] = __DIR__ . '/MultipleUpload.i18n.php';

$wgSpecialPages['MultipleUpload'] = 'MultipleUpload';

class MultipleUpload extends SpecialPage {

	public function __construct() {
		parent::__construct('MultipleUpload');
		$this->setListed(false);
	}

	public function execute($par) {
		$out = $this->getOutput();
		$out->setHTMLTitle('MultiUpload Retired');
		$out->addHTML('We have retired MultiUpload because it was out of date with the latest version of Mediawiki. We are considering adding the <a href="http://www.mediawiki.org/wiki/Extension:UploadWizard">UploadWizard</a> plugin to help with uploading multiple images instead. Please <a href="mailto:support@wikihow.com">let us know</a> if you feel strongly that we should add this plugin!');
	}

}
