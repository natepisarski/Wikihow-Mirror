<?php

use pimax\FbBotApp;

class WikihowFbBotApp extends FbBotApp {

	public function __construct($token) {
		parent::__construct($token);

		// Set the new API version to 3.2 in our subclass of FbBotApp since it looks like
		// this project is no longer being updated.  For the few API calls this bot makes
		// there should be no further updates required to the fb-messenger-php project
		$this->apiUrl = 'https://graph.facebook.com/v3.2/';
	}

	/**
	 * Set CTA for bot
	 *
	 * @param $pageId the unique page id
	 * @param Message $message
	 * @return mixed
	 */
	public function cta($pageId, $message)
	{
		//"https://graph.facebook.com/v2.6/<PAGE_ID>/thread_settings?access_token=<PAGE_ACCESS_TOKEN>";
		return $this->call("$pageId/thread_settings", $message->getData());
	}

	public function call($url, $data, $type = self::TYPE_POST) {
		wfDebugLog(MessengerSearchBot::LOG_GROUP, __METHOD__ . ": call data - " . http_build_query($data));
		return parent::call($url, $data, $type);
	}
}
