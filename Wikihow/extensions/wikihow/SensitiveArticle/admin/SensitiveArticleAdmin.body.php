<?php

namespace SensitiveArticle;

/**
 * /Special:SensitiveArticleAdmin, used to edit the Sensitive Article reasons.
 */
class SensitiveArticleAdmin extends \UnlistedSpecialPage
{
	public function __construct()
	{
		parent::__construct('SensitiveArticleAdmin');
	}

	public function execute($par)
	{
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$groups = $user->getGroups();

		if ($user->isBlocked() || !in_array('staff', $groups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($req->wasPosted()) {
			global $wgMimeType;
			$wgMimeType = 'application/json';
			$out->setArticleBodyOnly(true);
			$result = $this->processAction($req);
			print json_encode($result);
		} else {
			$out->setPageTitle('Sensitive Article Admin');
			$out->addModules('ext.wikihow.SensitiveArticle.admin');
			$html = $this->getHTML($out);
			$out->addHTML("<div id='sensitive_article_admin'>$html</div>");
		}
	}

	/**
	 * Process an AJAX request triggered from the UI
	 */
	private function processAction(\WebRequest $req): array
	{
		$result = [];
		$action = $req->getText('action', '');
		$id = $req->getInt('id', 0);

		if ($action == 'upsert') {
			$name = $req->getText('name', '');
			$enabled = $req->getText('enabled') === 'true';

			$sr = SensitiveReason::newFromValues($id, $name, $enabled);

			if (!$sr->save()) {
				$values = var_export($req->getValues(), true);
				$result['error'] = "<b>Error</b>. Unable to process request:<br><br><pre>$values</pre>";
			}
			else {
				if ($req->getInt('from_btt',0)) {
					$result['tag_id'] = $sr->id;
				}
			}

		}
		elseif ($action == 'delete_verify') {
			if ($id) {
				$count = SensitiveArticle::getSensitiveArticleCountByReasonId($id);
				$result['confirm_message'] = wfMessage('saa_delete_confirm', $count)->text();
			}
		}
		elseif ($action == 'delete') {
			if (!SensitiveReason::newFromDB($id)->delete()) {
				$result['error'] = 'Something went wrong';
			}
		}
		else {
			 $result['error'] = "Action not recognized: '$action'";
		}

		return $result;
	}

	private function getHTML(\OutputPage $out): string
	{
		$mustacheEngine = new \Mustache_Engine([
			'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/resources' )
		]);

		$reasons = $this->prepareReasons(SensitiveReason::getAll());
		return $mustacheEngine->render('sensitive_article_admin.mustache', ['reasons' => $reasons]);
	}

	private function prepareReasons(array $reasons): array
	{
		$permanent_reasons = [9,10];

		foreach ($reasons as $reason) {
			$reason->can_delete = !in_array($reason->id, $permanent_reasons);
		}

		return $reasons;
	}

}

