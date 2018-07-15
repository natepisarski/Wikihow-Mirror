<?php

use pimax\FbBotApp;

class WikihowFbBotApp extends FbBotApp {

	public function __construct($token) {
		parent::__construct($token);
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