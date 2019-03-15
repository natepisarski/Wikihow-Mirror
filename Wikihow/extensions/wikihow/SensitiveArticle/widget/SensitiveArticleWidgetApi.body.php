<?php

namespace SensitiveArticle;

/**
 * REST endpoint for the Sensitive Article Tagging widget on the staff-only section
 */
class SensitiveArticleWidgetApi extends \UnlistedSpecialPage
{
	public function __construct()
	{
		parent::__construct('SensitiveArticleWidgetApi');
	}

	function execute($par)
	{
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$groups = $user->getGroups();

		if (!$req->wasPosted() || $user->isBlocked() || !in_array('staff', $groups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		switch ($req->getText('action')) {
			case 'edit':
				$this->apiEdit($req);
				break;

			default:
				$this->apiError("Unrecognized action");
		}
	}

	private function apiEdit(\WebRequest $req)
	{
		$pageId = $req->getInt('page_id');
		if (!$pageId) {
			$this->apiError("Missing 'page_id' parameter");
			return;
		}

		$revId = (\WikiPage::newFromID($pageId))->getLatest();
		$userId = $this->getUser()->getId();
		$reasonIds = $req->getIntArray('reasons', []);

		$widget = new SensitiveArticleWidget($pageId);
		$widget->editArticle($revId, $userId, $reasonIds);

		\Misc::jsonResponse(['body' => $widget->getHTML()]);
	}

	private function apiError($msg = 'The API call resulted in an error.')
	{
		\Misc::jsonResponse(['error' => $msg], 400);
	}

}
