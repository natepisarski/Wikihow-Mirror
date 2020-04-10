<?php

class TipsAndWarnings extends UnlistedSpecialPage {

	const EDIT_COMMENT = "edited tip from [[Special:TipsPatrol|Tips Patrol]]";

	private static $is_valid_new_tip_title = null;
	private static $excludedNewTipCategories = [
		'Cars-&-Other-Vehicles',
		'Education',
		'Family-Life',
		'Finance-and-Business',
		'Health',
		'Pets',
		'Work-World'
	];

	public function __construct() {
		parent::__construct('TipsAndWarnings');
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$articleId = intval($req->getVal('aid'));
		$tip = $req->getVal('tip');
		if ($articleId != 0 && $tip != "") {
			$out->setArticleBodyOnly(true);
			if ($tip != "") {
				//$result['success'] = $this->addTip($articleId, $tip);
				$tipId = $this->addTip($articleId, $tip);
				$tp = new TipsPatrol;
				$result['success'] = $tp->addToQG($tipId, $articleId, $tip);
				print(json_encode($result));
				return;
			}
		}

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups))
		{
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$llr = new NewTipsAndWarnings();
		$llr->getList();
	}

	private function addTip($articleId, $tip) {
		global $wgParser;

		$title = Title::newFromID($articleId);
		if ($title) {

			$user = $this->getUser();
			$dbw = wfGetDB(DB_MASTER);

			$dbw->insert('tipsandwarnings', array('tw_page' => $articleId, 'tw_tip' => $tip, 'tw_user' => $user->getID(), 'tw_timestamp' => wfTimestampNow()),__METHOD__);

			//return true;
			$tipId = $dbw->selectField('tipsandwarnings', array('tw_id'), array('tw_page' => $articleId, 'tw_tip' => $tip, 'tw_user' => $user->getID()),__METHOD__);

			$logPage = new LogPage('addedtip', false);
			$logData = array($tipId);
			$logMsg = wfMessage('addedtip-added-logentry', $title->getFullText(), $tip)->text();
			$logS = $logPage->addEntry("Added", $title, $logMsg, $logData);

			return $tipId;
		}

		//return false;
		return '';
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if (self::isValidNewTipTitle( $out->getTitle() )) {
			$out->addModuleStyles('ext.wikihow.submit_a_tip.styles');
			$out->addModules('ext.wikihow.submit_a_tip');
		}
	}

	public static function onMobileProcessArticleHTMLAfter( OutputPage $out ) {
		if (self::isValidNewTipTitle( $out->getTitle() )) {

			$showDivider = true;

			if (!pq('.section.tips')->length) {
				$emptyTips = self::emptyTipsSection();
				$showDivider = false;

				if (pq('.section.warnings')->length) {
					pq('.section.warnings')->before($emptyTips);
				}
				elseif (pq('.section.video')->length) {
					pq('.section.video')->after($emptyTips);
				}
				elseif (pq('.section.qa')->length) {
					pq('.section.qa')->after($emptyTips);
				}
				elseif (pq('.steps:last')->length) {
					pq('.steps:last')->after($emptyTips);
				}
			}

			$newNode = self::submitTipHtml( $showDivider );
			pq("#tips")->append( $newNode );
		}
	}

	private static function submitTipHtml( bool $showDivider ): string {
		$vars = [
			'add_tip_aria' => wfMessage('aria_add_tip')->showIfExists(),
			'submit' => wfMessage('submit')->text(),
			'newtip_ph' => wfMessage('submit_a_tip_ph')->text(),
			'header' => wfMessage('submit_a_tip_header')->text(),
			'subheader' => wfMessage('submit_a_tip_subheader')->text(),
			'thanks' => wfMessage('submit_a_tip_thanks')->text(),
			'divider' => $showDivider
		];

		return self::renderTemplate('submit_a_tip.mustache', $vars);
	}

	private static function emptyTipsSection(): string {
		$vars = [
			'tips' => wfMessage('tips')->text()
		];

		return self::renderTemplate('empty_tips_section.mustache', $vars);
	}

	private static function renderTemplate( string $template, array $vars = [] ): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		return $m->render( $template, $vars );
	}

	public static function isValidNewTipTitle($t): bool {
		if (!is_null(self::$is_valid_new_tip_title)) return self::$is_valid_new_tip_title;

		$android_app = class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest();

		self::$is_valid_new_tip_title = $t &&
			$t->exists() &&
			$t->inNamespace(NS_MAIN) &&
			!$t->isProtected() &&
			$t->getPageLanguage()->getCode() == 'en' &&
			!GoogleAmp::isAmpMode( RequestContext::getMain()->getOutput() ) &&
			!Misc::isAltDomain() &&
			!$android_app &&
			!in_array(CategoryHelper::getTopCategory($t), self::$excludedNewTipCategories) &&
			!VerifyData::isExpertVerified( $t->getArticleId() ) &&
			!Ads::isExcluded($t);

		return self::$is_valid_new_tip_title;
	}

	function getSQL() {
		return "SELECT *, rc_timestamp as value from recentchanges WHERE rc_comment = '" . TipsAndWarnings::EDIT_COMMENT . "'";
	}

	public function isAnonAvailable() {
		return true;
	}
}

class NewTipsAndWarnings extends QueryPage {
	function __construct() {
		parent::__construct('TipsAndWarnings');
	}

	function getName() {
		return "NewTipsAndWarnings";
	}

	function isExpensive() {
		return false;
	}
	function isSyndicated() { return false; }

	function getSQL() {
		return TipsAndWarnings::getSql();
	}

	function formatResult( $skin, $result ) {
		$title = Title::makeTitle( $result->rc_namespace, $result->rc_title );
		$diffLink = $title->escapeFullUrl(
					'diff=' . $result->rc_this_oldid .
					'&oldid=' . $result->rc_last_oldid );
		$diffText = '<a href="' .
					$diffLink .
					'">(diff)</a>';

		$date = date('m-d-y', wfTimestamp(TS_UNIX, $result->rc_timestamp));

		return $title->getText() . " $diffText on $date";
	}

	function getPageHeader( ) {
		RequestContext::getMain()->getOutput()->setPageTitle("New Tips/Warnings");
	}

	function getList() {
		list( $limit, $offset ) = RequestContext::getMain()->getRequest()->getLimitOffset(50, 'rclimit');
		$this->limit = $limit;
		$this->offset = $offset;

		parent::execute('');
	}
}
