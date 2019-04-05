<?php
if (!defined('MEDIAWIKI')) die();

class SuggestedTopicsHooks {

	public static function requestedTopicsTabs(&$tabsArray) {
		// note: i don't know what this should be set to?
		$section = '';

		$listTab = new StdClass;
		$listTab->href = "/Special:ListRequestedTopics";
		$listTab->text = wfMessage('st_find_topic')->text();
		$listTab->active = $section == 'Topic';
		$tabsArray[] = $listTab;

		$recommendTab = new StdClass;
		$recommendTab->href = "/Special:RecommendedArticles";
		$recommendTab->text = wfMessage('st_recommended')->text();
		$recommendTab->active = $section == 'Recommended';
		$tabsArray[] = $recommendTab;

		$yourTab = new StdClass;
		$yourTab->href = "/Special:YourArticles";
		$yourTab->text = wfMessage('st_articles')->text();
		$yourTab->active = $section == 'Articles';
		$tabsArray[] = $yourTab;

		return true;
	}

	public static function notifyRequesterOnNab($article_id) {
		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select(array('suggested_notify', 'page'),
				array('sn_notify', 'page_title', 'page_namespace'),
				array('sn_page=page_id', 'page_id' => $article_id)
			);

		// Only send an email if the article doesn't suck (bug 557)
		$templateRegExp = "@{{(Copyvio|Copyviobot|accuracy|nfd|stub){1}@im";
		$r = Revision::loadFromPageId($dbw, $article_id);
		if (!is_null($r) && preg_match($templateRegExp, ContentHandler::getContentText( $r->getContent() )) === 0) {
			$emails = array();
			foreach ($res as $row) {
				$title = Title::makeTitle($row->page_namespace, $row->page_title);
				$arr = explode(',', $row->sn_notify);
				foreach($arr as $e) {
					$emails[trim($e)] = $title;
				}
			}

			if (sizeof($emails) > 0) {
				self::sendRequestNotificationEmail($emails);
			}
		}

		$dbw->delete('suggested_notify', array('sn_page' => $article_id), __METHOD__);
		return true;
	}

	public static function sendRequestNotificationEmail($emails) {
		$from = new MailAddress( wfMessage('suggested_notify_email_from')->text() );
		$semi_rand = md5(time());
		$mime_boundary = "==MULTIPART_BOUNDARY_$semi_rand";
		$mime_boundary_header = chr(34) . $mime_boundary . chr(34);
		foreach ($emails as $email=>$title) {
			$html_text = wfMessage('suggested_notify_email_html',
						wfGetPad(''),
						$title->getText(),
						$title->getFullURL('', false, PROTO_HTTPS),
						$title->getDBKey(),
						$title->getTalkPage()->getFullURL('', false, PROTO_HTTPS))
				->text();
			$plain_text = wfMessage('suggested_notify_email_plain',
						$title->getText(),
						$title->getFullURL('', false, PROTO_HTTPS),
						$title->getDBKey(),
						$title->getTalkPage()->getFullURL('', false, PROTO_HTTPS))
				->text();
			$body = "This is a multi-part message in MIME format.

--$mime_boundary
Content-Type: text/plain; charset=us-ascii
Content-Transfer-Encoding: 7bit

$plain_text

--$mime_boundary
Content-Type: text/html; charset=us-ascii
Content-Transfer-Encoding: 7bit

$html_text";

			$subject = wfMessage( 'suggested_notify_email_subject', $title->getText() )->text();
			if (!$title) continue;
			$to = new MailAddress($email);
			UserMailer::send($to, $from, $subject, $body, null, "multipart/alternative;\n" .
							"     boundary=" . $mime_boundary_header) ;
		}

		return true;

	}

	/*************
	 * st_isrequest is the parameter that differentiates between user generated topics
 * and topic generated through other means. This extension shows ALL topics, regardless
 * of how it was generated.
 */
}
