<?php

use ApiAi\Model\Context;
use ApiAi\Model\Query;

class APIAIWikihowAgentWebHook extends UnlistedSpecialPage {
	const LOG_GROUP = 'APIAIWikihowAgentWebHook';
	const USAGE_LOGS_EVENT_TYPE = 'google_home_read_article';
	const LENGTH_MAX_DISPLAY_TEXT = 640;

	const ATTR_ARTICLE = 'article_data';
	/**
	 * @var Query
	 */
	var $apiAIRequest = null;

	/**
	 * @var ReadArticleBot
	 */
	var $bot = null;

	const INTENT_FALLBACK = 'FallbackIntent';
	const INTENT_FIRST_STEP = 'FirstStep';
	const INTENT_GOTO_STEP = 'GoToStepIntent';
	const INTENT_HELP = 'HelpIntent';
	const INTENT_HOWTO = 'HowToIntent';
	const INTENT_NEXT = 'NextIntent';
	const INTENT_NO = 'NoIntent';
	const INTENT_PAUSE = 'PauseIntent';
	const INTENT_PREVIOUS = 'PreviousIntent';
	const INTENT_REPEAT = 'RepeatIntent';
	const INTENT_RESUME = 'ResumeIntent';
	const INTENT_START = 'StartIntent';
	const INTENT_STOP = 'StopIntent';
	const INTENT_YES = 'YesIntent';

	function __construct() {
		parent::__construct('APIAIWikihowAgentWebHook');
	}

	public static function fatalHandler() {
		wfDebugLog(self::LOG_GROUP, var_export('Last error on line following', true), true);
		$error = error_get_last();
		if ($error !== NULL) {
			$errno   = $error["type"];
			$errfile = $error["file"];
			$errline = $error["line"];
			$errstr  = $error["message"];

			self::errorHandler($errno, $errstr, $errfile, $errline);
		}
	}

	public static function errorHandler($errno, $errstr, $errfile, $errline) {
		/* Don't execute PHP internal error handler */
		$str = "PHP Error #$errno: '$errstr' in file $errfile on line $errline";
		wfDebugLog(self::LOG_GROUP, var_export($str, true), true);

		return true;
	}

	function execute($par) {
		//Define an error handler if you need to debug errors
		error_reporting(E_ERROR|E_CORE_ERROR|E_COMPILE_ERROR);
		//register_shutdown_function("APIAIWikihowAgentWebHook::fatalHandler");
		$old_error_handler = set_error_handler("APIAIWikihowAgentWebHook::errorHandler");

		$this->getOutput()->setRobotPolicy('noindex,nofollow');
		$this->getOutput()->setArticleBodyOnly(true);

		try {
			$incomingRequest = file_get_contents("php://input");
			$this->apiAIRequest = new Query(json_decode($incomingRequest, true));

			wfDebugLog(self::LOG_GROUP, var_export("Incoming request", true), true);
			wfDebugLog(self::LOG_GROUP, var_export($incomingRequest, true), true);

			$this->initBot();
			$this->processRequest();
		}
		catch(Error $e) {
			wfDebugLog(self::LOG_GROUP, var_export(MWExceptionHandler::getLogMessage(new MWException($e)), true), true);
			exit(1);
		}
		catch(Exception $e) {
			wfDebugLog(self::LOG_GROUP, var_export(MWExceptionHandler::getLogMessage(new MWException($e)), true), true);
			exit(1);
		}
	}


	protected function initBot() {
		$articleContext = $this->getArticleContext();
		$data = null;
		if (!is_null($articleContext)) {
			$data = $articleContext->getParameters()['data'];
		}
		wfDebugLog(self::LOG_GROUP, var_export("Article data: ", true), true);
		wfDebugLog(self::LOG_GROUP, var_export($data, true), true);

		$this->bot = ReadArticleBot::newFromArticleState($data, self::USAGE_LOGS_EVENT_TYPE);
	}

	/**
	 * @return Context|null
	 */
	protected function getArticleContext() {
		$contexts = $this->apiAIRequest->getResult()->getContexts();
		$context = null;
		foreach ($contexts as $ctx) {
			if ($ctx->getName() == "article") $context = $ctx;
		}

		return $context;
	}

	public function processRequest() {
		$intentName = $this->getIntent();
		$bot = $this->getBot();
		wfDebugLog(self::LOG_GROUP, var_export("Intent name: " . $intentName, true), true);
		switch ($intentName) {
			case self::INTENT_HELP:
				$responseText = $bot->onIntentHelp();
				$response = $this->getResponseWithContext($responseText);
				break;
			case self::INTENT_START:
				$responseText = $bot->onIntentStart();
				$response = $this->getResponseWithContext($responseText);
				break;
			case self::INTENT_NEXT:
				$responseText = $bot->onIntentNext();
				$response = $this->getResponseWithContext($responseText);
				break;
			case self::INTENT_PREVIOUS:
				$responseText = $bot->onIntentPrevious();
				$response = $this->getResponseWithContext($responseText);
				break;
			case self::INTENT_PAUSE:
				$responseText = $bot->onIntentPause();
				$response= $this->getResponseWithContext($responseText);
				break;
			case self::INTENT_STOP:
				$responseText = $bot->onIntentCancel();
				$response = $this->getResponseWithoutContext($responseText);
				break;
			case self::INTENT_REPEAT:
			case self::INTENT_RESUME:
				$responseText = $bot->onIntentRepeat();
				$response = $this->getResponseWithContext($responseText);
				break;
			case self::INTENT_FIRST_STEP:
				$responseText = $bot->onIntentStartOver();
				$response = $this->getResponseWithContext($responseText);
				break;
			case self::INTENT_HOWTO:
				$query = $this->apiAIRequest->getResult()->getResolvedQuery();
				$responseText = $bot->onIntentHowTo($query);
				$response = $this->getResponseWithContext($responseText);
				break;
			case self::INTENT_GOTO_STEP:
				$stepNum = intVal($this->apiAIRequest->getResult()->getParameters()['number']);
				wfDebugLog(self::LOG_GROUP, var_export("step number: " . $stepNum, true), true);
				$responseText = $bot->onIntentGoToStep($stepNum);
				$response = $this->getResponseWithContext($responseText);
				break;
			case self::INTENT_FALLBACK:
				$responseText = $bot->onIntentFallback();
				$response = $this->getResponseWithContext($responseText);
				break;
			case self::INTENT_NO:
				$responseText = $bot->onIntentNo();
				$response = $this->getResponseWithContext($responseText);
				// If we're in an article and the user says no, that signals they want to exit the action.
				// Send the ad response in this instance with the response text. Ads show at the ending intent
				// (eg when the session should be ended)
				if ($bot->getArticleData()) {
					$response = $this->injectAdIntoResponse($response, false);
					$response = $this->setEndSession($response);
					$this->sendResponse($response);
					return;
				} else {
					$response = $this->getResponseWithContext($responseText);
				}
				break;
			case self::INTENT_YES:
				$responseText = $bot->onIntentYes();
				$response = $this->getResponseWithContext($responseText);
				break;
			default:
				$response = $this->getResponseWithContext(wfMessage('reading_article_unkown_command')->text());
		}

		$response = $this->setReprompt($response);
		$this->sendResponse($response);
	}

	protected function injectAdIntoResponse($response, $isConversationStart) {
		$conversationState = $isConversationStart ? 'CONVERSATION_START' : 'CONVERSATION_END';

		$response['data']['google']['systemIntent'] = [
			'intent' => 'actions.intent.SHOW_AD',
			'data' => [
				'@type' => 'type.googleapis.com/google.actions.v2.ShowAdValueSpec',
				'conversationState' => $conversationState
			]
		];

		wfDebugLog(self::LOG_GROUP, var_export(__METHOD__ . ": $conversationState Ad Response", true), true);
		wfDebugLog(self::LOG_GROUP, var_export($response, true), true);

		return $response;
	}

	/**
	 * Certain intents relating to article navigation should have a custom reprompt
	 * @param $response
	 * @return mixed
	 */
	protected function setReprompt($response) {
		$intentName = $this->getIntent();
		switch ($intentName) {
			case self::INTENT_NEXT:
			case self::INTENT_PREVIOUS:
			case self::INTENT_REPEAT:
			case self::INTENT_RESUME:
			case self::INTENT_FIRST_STEP:
			case self::INTENT_HOWTO:
			case self::INTENT_GOTO_STEP:
				wfDebugLog(self::LOG_GROUP, var_export(__METHOD__ . ": setting custom reprompt", true), true);
				$sayNextPrompt =  wfMessage('reading_article_instructions')->text();
				$noResponsePrompt = wfMessage('reading_article_no_response_prompt')->text();

				// Don't prompt for the next step on the last step
				$prompts = [];
				$a = $this->getBot()->getArticleData();
				// Case where no results are found in the INTENT_HOWTO search
				if (!$a) {
					return $response;
				}

				$isLastStep = $a && $a->isLastStepInMethod();
				if ($isLastStep) {
					$prompts []=
						[
							"textToSpeech" => " ",
							"ssml" => "<speak><break time=\"3333ms\" /></speak>"
						];
				} else {
					$prompts []=
						[
							"textToSpeech" => $sayNextPrompt,
							"ssml" => $this->convertToSSML($sayNextPrompt)
						];
				}

				// Always end with our goodbye message when navigating and article
				$prompts []=
					[
						"textToSpeech" => $noResponsePrompt,
						"ssml" => $this->convertToSSML($noResponsePrompt)
					];

				$response['data']['google']['noInputPrompts'] = $prompts;
		}
		return $response;
	}

	/**
	 * @return string
	 */
	protected function getIntent() {
		return $this->apiAIRequest->getResult()->getMetadata()->getIntentName();
	}


	/**
	 * @param $responseText
	 * @return array
	 */
	protected function getResponseWithoutContext($responseText) {
		$response = $this->getResponseObject($responseText);
		return $response;
	}

	/**
	 * @param $responseText
	 * @return array
	 */
	protected function getResponseWithContext($responseText) {
		$response = $this->getResponseObject($responseText);
		$response = $this->setArticleContextOnResponse($response);
		return $response;
	}

	/**
	 * @param $responseText
	 * @return array
	 * @internal param bool $expectUserResponse
	 */
	protected function getResponseObject($responseText) {

		$responseText = StringUtil::convertCurlyQuotes($responseText);
		// Convert emdash to dash
		$responseText = str_replace(["-", "–", '—'], '-', $responseText);
		// Handle any other weird characters that may cause json_encode to barf later on
		$responseText = utf8_encode($responseText);

		wfDebugLog(self::LOG_GROUP, var_export(__METHOD__, true), true);
		wfDebugLog(self::LOG_GROUP, var_export("Response text:\n$responseText", true), true);

		$ssml = $this->convertToSSML($responseText);


		if (strlen($responseText) > self::LENGTH_MAX_DISPLAY_TEXT) {
			$responseText = substr($responseText, 0, self::LENGTH_MAX_DISPLAY_TEXT - 3) . '...';
		}

		return [
			"speech" => $ssml,
			"displayText" => $responseText,
			"data" => [
				"google" => [
					"expectUserResponse" => true,
					"noInputPrompts" => [
						[
							"textToSpeech" => " ",
							"ssml" => "<speak><break time=\"3333ms\" /></speak>"
						]
					],
				]
			],
			"contextOut" => [["name" => "article", "lifespan" => "999", "parameters" => ["data" => ""]]],
			"source" => "APIAIWikihowAgentWebHook"
		];
	}

	protected function setEndSession($response) {
		$response['data']['google']['expectUserResponse'] = false;

		return $response;
	}

	/**
	 * @param $response
	 * @return mixed
	 */
	protected function setArticleContextOnResponse($response) {
		$bot = $this->getBot();
		if (!is_null($bot)) {
			$response['contextOut'][0]['parameters']['data'] = $bot->getState();
		}

		return $response;
	}

	/**
	 * @return ReadArticleBot
	 */
	protected function getBot() {
		return $this->bot;
	}


	/**
	 * @param Response $response
	 * @return mixed
	 */
	protected function setSessionAttributes($response) {
		foreach ($this->apiAIRequest->session->attributes as $key => $val) {
//			$response->addSessionAttribute($key, utf8_encode($val));
			$response->addSessionAttribute($key, $val);
		}

		return $response;
	}

	/**
	 * @return string
	 */
	protected function sendResponse($response) {
		global $wgMimeType;
		$wgMimeType = 'application/json';

		$response = json_encode($response);
		wfDebugLog(self::LOG_GROUP, var_export("Response json after json_encode:", true), true);
		wfDebugLog(self::LOG_GROUP, var_export($response, true), true);

		if (!$response) {
			wfDebugLog(self::LOG_GROUP, var_export("Problem with reponse! Reverting to default message", true), true);
			wfDebugLog(self::LOG_GROUP, var_export("json last error: " . json_last_error(), true), true);
			wfDebugLog(self::LOG_GROUP, var_export(json_last_error_msg(), true), true);
			$response = json_encode($this->getResponseWithContext(wfMessage('reading_article_unkown_command')->text()));
		}

		echo $response;
	}

	protected function convertToSSML($text) {
		// Remove unsupported SSML characters
		$text = str_replace("&", "and", $text);
		$text = str_replace("\n", "<break time=\"300ms\" />", $text);
		$text = "<speak>$text</speak>";
		return $text;
	}
}
