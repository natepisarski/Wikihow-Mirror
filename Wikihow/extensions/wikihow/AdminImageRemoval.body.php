<?php

global $IP;
require_once "$IP/extensions/wikihow/titus/TitusQueryTool.php";

class AdminImageRemoval extends UnlistedSpecialPage {

	static $imagesRemoved; // array to keep the name of each image that we remove

	public function __construct() {
		parent::__construct( 'AdminImageRemoval' );
	}

	public function execute($par) {
		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);

			self::$imagesRemoved = array();

			$urlList = $req->getVal("urls");
			$pages = TitusQueryTool::getIdsFromUrls($urlList);

			$errors = array();

			foreach ($pages as $page) {
				if ($page['language'] == "en" && $page['page_id'] > 0) {
					$this->removeImagesFromArticle($page['page_id']);
				} else {
					$errors[] = $page['url'];
				}
			}

			if (count($errors) > 0) {
				$result['success'] = false;
				$result['errors'] = $errors;
			} else {
				$result['success'] = true;
			}

			print json_encode($result);

			// Write to temp file to keep track of what we removed
			$filePath = "/tmp/images-removal-" . date('Ymd') . ".txt";
			$fo = fopen($filePath, 'a');
			foreach (self::$imagesRemoved as $fileName) {
				fwrite($fo, "Image:{$fileName}\n");
			}
			fclose($fo);

			return;
		}

		$out->addModules('ext.wikihow.image_removal');

		$s = Html::openElement( 'form', array( 'action' => '', 'id' => 'imageremoval' ) ) . "\n";
		$s .= Html::element('p', array(''), 'Input full URLs (e.g. https://www.wikihow.com/Kiss) for articles that should have images removed from them.');
		$s .= Html::element('br');
		$s .= Html::element( 'textarea', array('id' => 'urls', 'cols' => 55, 'rows' => 5) ) . "\n";
		$s .= Html::element('br');
		$s .= Html::element( 'input',
				array( 'type' => 'submit', 'class' => "button primary", 'value' => 'Process articles' )
			) . "\n";
		$s .= Html::closeElement( 'form' );
		$s .= Html::element('div', array('id' => 'imageremoval_results'));

		$out->addHTML($s);
	}

	private function removeImagesFromArticle($articleId) {
		$title = Title::newFromID($articleId);

		if ($title) {
			$revision = Revision::newFromTitle($title);

			$text = ContentHandler::getContentText( $revision->getContent() );

			//regular expressions copied out of maintenance/wikiphotoProcessImages.php
			//but modified to remove the leading BR tags if they exist
			//In the callback we keep track of each image name that we remove
			$text = preg_replace_callback(
				'@(<\s*br\s*[\/]?>)*\s*\[\[Image:([^\]]*)\]\]@im',
				function($matches) {
					$image = $matches[2];
					$pipeLoc = strpos($image, "|");
					if ($pipeLoc !== false) {
						$image = substr($image, 0, $pipeLoc);
					}
					self::$imagesRemoved[] = $image;
					return '';
				},
				$text
			);
			$text = preg_replace_callback(
				'@(<\s*br\s*[\/]?>)*\s*\{\{largeimage\|([^\}]*)\}\}@im',
				function($matches) {
					$image = $matches[2];
					self::$imagesRemoved[] = $image;
					return '';
				},
				$text
			);
			$text = preg_replace('@(<\s*br\s*[\/]?>)*\s*\{\{largeimage\|[^\}]*\}\}@im', '', $text);

			$wikiPage = WikiPage::factory($title);
			$content = ContentHandler::makeContent($text, $title);
			$wikiPage->doEditContent($content, 'Removing all images from article');
		}
	}
}
