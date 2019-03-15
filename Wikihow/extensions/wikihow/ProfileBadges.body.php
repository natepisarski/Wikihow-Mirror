<?php

class ProfileBadges extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ProfileBadges' );
	}

	public function execute($par) {
		$out = $this->getOutput();

		$out->addHTML(HtmlSnips::makeUrlTag('/extensions/wikihow/ProfileBadges.css'));

		$out->setPageTitle(wfMessage('ab-title'));

		$out->addHTML("<div class='undoArticleInner'>");
		$out->addHTML($this->getBadge('admin'));
		$out->addHTML($this->getBadge('nab'));
		$out->addHTML($this->getBadge('fa'));
		$out->addHTML($this->getBadge('welcome'));
		$out->addHTML("</div>");
	}

	private function getBadge($badgeName) {
		$html = "<div class='ab-box'>";
		$html .= "<div class='ab-badge ab-" . $badgeName . "'></div>";
		$html .= "<h3>" . wfMessage("ab-" . $badgeName . "-title") . "</h3>";
		$html .= wfMessage("ab-" . $badgeName . "-description")->parseAsBlock();
		$html .= "</div>";

		return $html;
	}

}
