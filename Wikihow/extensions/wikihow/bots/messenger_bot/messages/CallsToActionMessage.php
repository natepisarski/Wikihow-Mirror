<?php

use pimax\Messages\MessageButton;
use pimax\Messages\MessageElement;
use pimax\Messages\StructuredMessage;

/**
 * Created by PhpStorm.
 * User: jordan
 * Date: 4/19/16
 * Time: 3:38 PM
 */
class CallsToActionMessage extends StructuredMessage {
	var $type = null;

	public function __construct() {
	}

	protected function buildStructuredMessage() {
		return "";
	}

	public function getData() {
		$result = [
			"setting_type" => "call_to_actions",
			"thread_state" => "new_thread",
			"call_to_actions" => [
				['payload' => MessengerSearchBot::COMMAND_CTA]
			]
		];
		//	echo json_encode($result, JSON_PRETTY_PRINT);exit;

		return $result;
	}
}