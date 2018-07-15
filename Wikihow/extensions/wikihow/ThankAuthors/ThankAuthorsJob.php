<?php
/**
 * Job Queue class for sending user kudos
 * @file
 * @ingroup JobQueue
 *
 * @author Lojjik Braughler
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
class ThankAuthorsJob extends Job {

	public function __construct(Title $targetArticle, $params, $id = 0) {
		parent::__construct('thankAuthors', $targetArticle, $params, $id);
	}

	public function run() {
		$status = $this->sendKudos();

		if ( $status !== true ) {
			$this->setLastError($status);

			return false;
		}

		return true;
	}

	function sendKudos() {
		$title = $this->title;
		$kudos = $this->params['kudos'];
		$submitter = $this->params['source'];

		$authors = array_keys(ArticleAuthors::getAuthors($title->getArticleID()));
		wfDebugLog( 'ThankAuthors', "job received to send kudos to: " . implode( ',', $authors) );

		foreach ($authors as $author) {
			$user = User::newFromName($author);

			// Send Echo notification

			if (class_exists( 'EchoEvent' )) {
				EchoEvent::create(
					array(
						'type' => 'kudos',
						'title' => $title,
						'extra' => array(
							'kudoed-user-id' => $user->getId()
						),
						'agent' => $submitter
					) );
			}

			// Place message on kudos page

			$comment = TalkPageFormatter::createComment($submitter, $kudos, true, $title);
			$kudosPage = $user->getKudosPage();
			$text = '';

			if ($kudosPage->exists()) {
				$revision = Revision::newFromTitle($kudosPage);
				$content = $revision->getContent(Revision::RAW);
				$text = ContentHandler::getContentText($content);
			}

			$text .= $comment;
			$page = WikiPage::factory($kudosPage);
			$content = ContentHandler::makeContent($text, $kudosPage);

			try {
				$page->doEditContent($content, '', EDIT_SUPPRESS_RC, false, $submitter);
				wfDebugLog( 'ThankAuthors', "sent kudos: " . $kudosPage->getCanonicalURL() );
			} catch (MWException $e) {
				wfDebugLog( 'ThankAuthors', 'exception in ' . __METHOD__ . ':' . $e->getText() );
				return $e->getText();
			}
		}
		wfDebugLog( 'ThankAuthors', "kudos sent total: " . count($authors) );

		return true;
	}
}
