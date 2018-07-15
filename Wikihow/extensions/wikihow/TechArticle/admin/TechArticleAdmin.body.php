<?php

namespace TechArticle;

use OutputPage;
use UnlistedSpecialPage;
use WebRequest;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

if (!defined('MEDIAWIKI'))
	die();

/**
 * /Special:TechArticleAdmin, which staff members can use to modify the Tech Article Widget data.
 */
class TechArticleAdmin extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('TechArticleAdmin');
	}

	public function execute($par) {

		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		// Restrict page access
		$groups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $groups)) {
			$out->setRobotpolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($req->wasPosted()) {
			$errors = $this->processAction($req);
			$out->disable();
			echo $errors ?: $this->getPageHtml($out);
		} else {
			$out->setPageTitle(wfMessage('twa_page_title')->text());
			$out->addModules('ext.wikihow.TechArticle.admin');
			$html = $this->getPageHtml($out);
			$out->addHTML("<div id='tech_widget_admin'>$html</div>");
		}
	}

	/**
	 * Process a request from the admin panel (e.g. update a product name)
	 */
	private function processAction(WebRequest $req) {
		$id = $req->getInt('id');
		$name = $req->getText('name');
		$enabled = $req->getText('enabled') === 'true';
		$type = $req->getText('type');
		$action = $req->getText('action');

		if (!self::isValidAction($action) || !self::isValidType($type)) {
			$values = var_export($req->getValues(), true);
			return "Invalid request:<br><pre>$values</pre>";
		}

		if (in_array($action, ['update', 'insert'])) {
			if ($type == 'product') {
				TechProduct::newFromValues($id, $name, $enabled)->save();
			} elseif ($type == 'platform') {
				TechPlatform::newFromValues($id, $name, $enabled)->save();
			}
		}

	}

	private function getPageHtml(OutputPage $out) {
		$vars = [
			[
				'type'  => 'product',
				'title' => wfMessage('twa_products_title'),
				'items' => TechProduct::getAll(),
			],
			[
				'type'  => 'platform',
				'title' => wfMessage('twa_platforms_title'),
				'items' => TechPlatform::getAll(),
			]
		];

		$engine = new Mustache_Engine([
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/resources' )
		]);
		$html = '';
		foreach ($vars as $v) {
			$html .= $engine->render('tech_article_admin.mustache', $v);
		}
		return $html;
	}

	private static function isValidAction(string $action): bool {
		return in_array($action, ['insert', 'update', 'reset']);
	}

	private static function isValidType(string $type): bool {
		return in_array($type, ['platform', 'product']);
	}

}

