<?php

require_once __DIR__ . '/../../TranslationLink.php';

class RetranslateEditorHooks {
	/**
	 * Called when invoking the page editor.
	 * Renders a "retranslate" button on the edit page, but only for relevant users.
	 */
	public static function onCustomEdit(Page $page, User $user): bool
	{
		global $wgOut, $wgUser;

		if (!$page || !$page->exists() || !RetranslateEditor::isUserAllowed()) {
			return true; // use the normal editor
		}

		$wgOut->addModules('ext.wikihow.retranslateeditor');

		$mustache = new Mustache_Engine([ 'loader' => new Mustache_Loader_FilesystemLoader(__DIR__) ]);
		$vars = [
			'intlAid' => $page->getId(),
			'token' => $wgUser->getEditToken(),
		];

		$html = $mustache->render('RetranslateEditor.mustache', $vars);
		$wgOut->addHTML($html);

		// Next 3 lines copied from includes/actions/EditAction.php
		$editor = new EditPage($page);
		$editor->setContextTitle($page->getTitle());
		$editor->edit();

		return false;
	}
}

class RetranslateEditor extends UnlistedSpecialPage {

	private $lang;
	private $out;
	private $req;
	private $user;

	function __construct() {
		parent::__construct('RetranslateEditor');
		$this->lang = $this->getLanguage()->getCode();
		$this->out = $this->getOutput();
		$this->req = $this->getRequest();
		$this->user = $this->getUser();
	}

	public static function isUserAllowed(): bool {
		global $wgUser;
		return Misc::isIntl()
			&& !$wgUser->isAnon()
			&& !$wgUser->isBlocked()
			&& Misc::isUserInGroups($wgUser, ['sysop', 'staff', 'translator']);
	}

	public function execute($par)
	{
		global $wgIsDevServer;

		$token = $this->req->getVal('token');
		$intlAid = (int) $this->req->getVal('intlAid');

		$isValidReq = self::isUserAllowed()
			&& $this->req->wasPosted()
			// disable CSRF protection on dev to facilitate testing:
			&& ( $wgIsDevServer || $this->user->matchEditToken($token) )
			&& $intlAid > 0;

		if ($isValidReq) {
			$result = $this->getWikiText($intlAid);
			$httpCode = $result['error'] ? 400 : 200;
			Misc::jsonResponse($result, $httpCode);
		} else {
			$this->out->setRobotPolicy('noindex,nofollow');
			$this->out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
		}
	}

	private function getWikiText(int $intlAid): array
	{
		$res = [ 'wikiText' => '', 'error' => '' ];

		// Find the English article

		$links = TranslationLink::getLinksTo($this->lang, $intlAid, true);
		$enAid = null;
		foreach ($links as $link) {
			if ($link->fromLang == 'en') {
				$enAid = (int)$link->fromAID;
				break;
			}
		}
		if ( !$enAid ) {
			$res['error'] = 'English article not found';
			return $res;
		}

		// Make sure the EN article is indexable

		$dbr = wfGetDB(DB_REPLICA);
		$iiPolicy = $dbr->selectField(
			Misc::getLangDB('en') . '.index_info',
			'ii_policy',
			[ 'ii_namespace' => NS_MAIN, 'ii_page' => $enAid ],
			__METHOD__
		);
		$isIndexable = ($iiPolicy !== false) && RobotPolicy::isIndexablePolicy($iiPolicy);
		if ( !$isIndexable ) {
			$res['error'] = "Retranslation is not available for this article due to quality concerns";
			return $res;
		}

		// Fetch the English article wikiText from the wikihow.com API

		$json = EditorUtil::getArticleInfoById($enAid);
		$data = json_decode($json, true) ?? [];
		$enPageInfo = $data['query']['pages'][(string)$enAid] ?? null;
		if ( !$enPageInfo ) {
			$res['error'] = "Failed to retrieve information about the English article";
			return $res;
		}

		### Process the EN article wikiText

		$wikiText = $enPageInfo['revisions'][0]['*'];
		$wikiText = EditorUtil::replaceInternalLinks( $wikiText );

		// Replace the EN Quick Summary with its translation

		$enDbKey = $dbr->selectField( 'wikidb_112.page', 'page_title', ['page_id'=>$enAid] );
		$enSummary = EditorUtil::getSummary( $wikiText, $enDbKey );
		if ( $enSummary ) {
			$intlPage = WikiPage::newFromID($intlAid);
			$intlWikiText = ContentHandler::getContentText( $intlPage->getContent() );
			$intlSummary = EditorUtil::getSummary( $intlWikiText, $intlPage->getTitle()->getDBkey() );
			$wikiText = str_replace($enSummary, $intlSummary, $wikiText);
		}

		// Remove unwanted templates

		$templatesToRemove = [
			'{{fa}}',
			'{{nointroimg}}',
			'===? [^=]+ ===? \ *\n ([^\[=]*)? \[\[Doc:[^\]]+\]\]\s*', // Docs with their own section (most)
			'\[\[Doc:[^\]]+\]\]\s*', // Loose docs (rare exceptions)
		];
		foreach ($templatesToRemove as $tmpl) {
			$wikiText = preg_replace("/$tmpl/xi", '', $wikiText);
		}

		// Remove unwanted sections

		$sectionsToRemove = [
			// IMPORTANT: please use lower case when adding new sections here
			'related wikihows',
			'video'
		];

		$sectionRegex = '/^\s*==([^=]+)==\s*$/m';

		$sectionTexts = preg_split($sectionRegex, $wikiText);

		$sectionNames = [];
		$sectionCount = preg_match_all($sectionRegex, $wikiText, $sectionNames);
		$sectionNames = $sectionNames[1];
		array_unshift($sectionNames, 'intro'); // prepend a 'fake' section name for the intro

		if ( !$sectionCount || count($sectionNames) != count($sectionTexts) ) {
			$sectionCount['error'] = 'Failed to parse the English article wikiText';
			return $sectionCount;
		}

		$sections = array_combine($sectionNames, $sectionTexts);
		$wikiText = '';
		foreach ($sections as $name => $text) {
			$name = trim($name);
			if ( in_array(strtolower($name), $sectionsToRemove) ) {
				continue;
			}
			$name = ('intro' == $name) ? '' : "\n== $name ==";
			$wikiText .= $name . $text;
		}

		// Remove extra new line from quick summary section
		$wikiText = preg_replace('/-->.*== ?Quick Summary ?==/is', "-->\n== Quick Summary ==", $wikiText);

		// Localize section headers

		$sectionTranslations = EditorUtil::getSectionTranslations();
		foreach ($sectionTranslations as $trn) {
			$from = $trn['from'];
			$wikiText = preg_replace("/$from/i", $trn['to'], $wikiText);
		}

		$res['wikiText'] = $wikiText;
		return $res;
	}

}
