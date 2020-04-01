<?php

if (!defined('MEDIAWIKI')) die();

/**
 * This special page is for Team Chris so that he can send URLs to contractors
 * so that they can view deindexed pages as an anon temporarily.
 */

global $IP;
require_once "$IP/extensions/wikihow/titus/TitusQueryTool.php";

class SpecialValidatron extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('Validatron');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$req = $this->getRequest();

		if (!$this->userAllowed()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$out->setHTMLTitle('Admin - Article Validatronator - wikiHow');
		$out->setPageTitle('Create validated URLs');

		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$pagesList = $req->getVal('pages-list');
			$lines = $this->processList( $pagesList, $err );
			if ( !$err && $lines ) {
				self::httpDownloadHeaders();
				print "input\tresult\n";
				foreach ($lines as $line) {
					print join("\t", $line) . "\n";
				}
			} else {
				if (!$err) $err = 'Nothing to process';
				print "ERROR: $err";
			}
		} else {
			$loader = new Mustache_Loader_CascadingLoader( [
				new Mustache_Loader_FilesystemLoader(__DIR__),
			] );
			$options = [ 'loader' => $loader ];
			$m = new Mustache_Engine($options);

			$html = $m->render('url_input_form.mustache', []);
			$out->addHTML( $html );
		}
	}

	private function userAllowed() {
		global $wgLanguageCode;

		$user = $this->getUser();
		$userGroups = $user->getGroups();
		$hasRights = in_array('staff', $userGroups);

		if ($wgLanguageCode != 'en') $hasRights = $hasRights || in_array('sysop', $userGroups);

		if ($user->isBlocked() || !$hasRights) {
			return false;
		}

		return true;
	}

	private function processList($list, &$errors) {
		$errors = '';
		$lines = [];
		$results = TitusQueryTool::getIdsFromUrls( urldecode( $list ) );
		foreach ($results as $result) {
			if ( isset($result['redirect_target']) && $result['redirect_target'] ) {
				$err = "Input `{$result['url']}' is a redirect to: `{$result['redirect_target']}'";
				$line = [$result['url'], $err];
				$errors .= "$err\n";
			} elseif (!$result['page_id']) {
				$err = "Input `{$result['url']}' was not found";
				$line = [$result['url'], $err];
				$errors .= "$err\n";
			} else {
				$pageid = (int)$result['page_id'];
				$tokenUrl = PagePolicy::generateTokenUrl($result['url'], $pageid);
				$line = [$result['url'], $tokenUrl];
			}
			$lines[] = $line;
		}

		return $lines;
	}

	private static function httpDownloadHeaders() {
		$date = date('Y-m-d');
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="valid_urls_' . $date . '.xls"');
	}
}
