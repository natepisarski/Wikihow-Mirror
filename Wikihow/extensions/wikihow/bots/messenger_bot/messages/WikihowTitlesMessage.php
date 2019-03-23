<?php
use pimax\Messages\Message;
use pimax\Messages\MessageButton;
use pimax\Messages\MessageElement;
use pimax\Messages\StructuredMessage;

/**
 * Created by PhpStorm.
 * User: jordan
 * Date: 4/19/16
 * Time: 2:55 PM
 */
class WikihowTitlesMessage extends Message {

	var $titles = null;
	var $recipientId = null;
	var $additionalButton = null;

	public function  __construct($titles, $recipientId, $additionalButton = null) {
		$this->titles = $titles;
		$this->recipientId = $recipientId;
		$this->additionalButton = $additionalButton;
	}

	protected function buildMessage() {
		$titles = $this->titles;
		$recipientId = $this->recipientId;
		$results = $this->getMetaInfo($titles);
		return $this->buildStructuredMessage($recipientId, $results);
	}

	public static function updateRedirects($titles) {
		$filtered = [];
		foreach ($titles as $t) {
			wfDebugLog('ReadArticleBot', var_export("updateRedirects: " . $t->getText(), true), true);
			if ($t->isRedirect()) {
				wfDebugLog('ReadArticleBot', var_export("Is Redirect: " . $t->getText(), true), true);
				$wikiPage = WikiPage::factory( $t );
				if ($wikiPage) {
					$target = $wikiPage->getRedirectTarget();
					if ($target && $target->exists()) {
						wfDebugLog('ReadArticleBot', var_export("redirect target: " . $target->getText(), true), true);
						$filtered []= $target;
					}
				}
			}  else {
				$filtered [] = $t;
			}
		}
		return $filtered;
	}

	public static function filterTitlesByCategory($titles, $namespacesAllowed = [NS_MAIN, NS_CATEGORY]) {
		return array_filter($titles, function($t) use ($namespacesAllowed) {
			return $t->inNamespaces($namespacesAllowed);
		});
	}

	public static function filterTitlesByName($titles, $dbKeysToRemove = []) {
		return array_filter($titles, function($t) use ($dbKeysToRemove) {
			return !in_array($t->getDBKey(), $dbKeysToRemove);
		});
	}

	public static function filterTitlesByAid($titles, $aidsToRemove = []) {
		return array_filter($titles, function($t) use ($aidsToRemove) {
			//wfDebugLog('ReadArticleBot', var_export(!in_array($t->getArticleId(), $aidsToRemove), true), true);
			//wfDebugLog('ReadArticleBot', var_export("aid: " . $t->getArticleId(), true), true);
			return !in_array($t->getArticleId(), $aidsToRemove);
		});
	}

	protected function getMetaInfo($titles) {
		$info = [];
		foreach ($titles as $t) {
			$ami = new ArticleMetaInfo($t);
			$displayTitle = $t->inNamespace(NS_MAIN) ? wfMessage('howto', $t->getText())->text() : $t->getText();
			$info []= [
				'id' => $t->getArticleId(),
				'display_title' => $displayTitle,
				'url' => $t->getLocalUrl(),
//				'desc' => $ami->getFacebookDescription(),
				'img_url' => $this->getImageUrl($ami->getImage()),
				'namespace' => $t->getNamespace(),
			];
		}

		return $info;
	}

	protected function buildStructuredMessage($recipientId, $titlesInfo) {
		$elements = [];
		foreach ($titlesInfo as $t) {
			$label = $t['namespace'] == NS_MAIN ?
				wfMessage('view_article_button_label')->text() :
				wfMessage('view_category_button_label')->text();
			$buttons = [new MessageButton(MessageButton::TYPE_WEB, $label, $this->getFullUrl($t['url']))];
			if (!is_null($this->additionalButton)) {
				$buttons[] = $this->additionalButton;
			}
			$elements []= new MessageElement(
				$t['display_title'],
				$t['desc'],
				$t['img_url'],
				$buttons
			);
		}

		$response = new StructuredMessage($recipientId,
			StructuredMessage::TYPE_GENERIC,
			['elements' => $elements]
		);

		return $response;
	}


	protected function getImageUrl($imageFile) {
		global $wgLangCode;
		return Misc::getLangBaseURL($wgLangCode) . $imageFile;
	}

	protected function getRandomDefaultImagePath() {
		return mt_rand(0, 1) % 2 == 0 ? self::NO_IMG_GREEN : self::NO_IMG_BLUE;
	}

	protected function getFullUrl($partialUrl) {
		global $wgLangCode;

		return Misc::getLangBaseURL($wgLangCode) . '/' . $partialUrl;
	}

	public function getData() {
		$data = $this->buildMessage()->getData();
		//wfDebugLog(MessengerSearchBot::LOG_GROUP, var_export($data, true), true);
		return $data;
	}
}
