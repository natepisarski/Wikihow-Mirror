<?php

use Alexa\Request\CustomSkillRequestTypes;
use Alexa\Request\ElementSelectedRequest;
use Doctrine\Common\Annotations\AnnotationRegistry;

class AlexaSkillReadArticleWebHook extends UnlistedSpecialPage {
	const LOG_GROUP = 'AlexaSkillReadArticleWebHook';
	const USAGE_LOGS_EVENT_TYPE = 'alexa_skill_read_article';

	const INTERFACE_DISPLAY_TEMPLATE = 'Display';
	const INTERFACE_VIDEO_APP = 'VideoApp';

	const ATTR_ARTICLE = 'article_data';

	const MAX_RESPONSE_BYTE_SIZE = 24576;

	/**
	 * @var ReadArticleBotV2
	 */
	var $bot = null;

	/**
	 * @var ReadArticleModelV2
	 */
	var $articleModel = null;

	/**
	 * @var \Alexa\Request\RequestInterface
	 */
	var $alexaRequest = null;

	function __construct() {
		parent::__construct('AlexaSkillReadArticleWebHook');
	}

	public static function fatalHandler() {
		wfDebugLog(self::LOG_GROUP, var_export('Last error on line following', true), true);
		$error = error_get_last();
		if ( $error !== NULL) {
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
		$this->getOutput()->setArticleBodyOnly(true);

		//Define an error handler if you need to debug errors
		//error_reporting(E_CORE_ERROR|E_COMPILE_ERROR);
		//register_shutdown_function("AlexaSkillReadArticleWebHook::fatalHandler");
		set_error_handler("AlexaSkillReadArticleWebHook::errorHandler");

		$this->getOutput()->setRobotPolicy('noindex,nofollow');
		$this->getOutput()->setArticleBodyOnly(true);

		// Needed for search to work properly
		$_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36";

		try {
			$appId = WH_ALEXA_SKILL_READ_ARTICLE_APP_ID; // See developer.amazon.com and your Application. Will start with "amzn1.echo-sdk-ams.app."
			$devAppId = WH_ALEXA_SKILL_READ_ARTICLE_APP_ID_DEV;
			$rawRequest = file_get_contents("php://input"); // This is how you would retrieve this with Laravel or Symfony 2.
			wfDebugLog(self::LOG_GROUP, var_export("Incoming raw request:", true), true);
			wfDebugLog(self::LOG_GROUP, var_export($rawRequest, true), true);

			$alexaRequestFactory = new \Alexa\Request\RequestFactory();
			$this->initAnnotationsLoader();

			// Add a new Request type since the current API doesn't support.  In the future, we should consider
			// forking and extending since updates aren't keeping up with new Alexa features
			CustomSkillRequestTypes::$validTypes['Display.ElementSelected'] = ElementSelectedRequest::class;

			$this->alexaRequest = $alexaRequestFactory->fromRawData($rawRequest, [$appId, $devAppId]);

			$this->processRequest();
		}
		catch(Error $e) {
			wfDebugLog(self::LOG_GROUP, var_export(MWExceptionHandler::getLogMessage(new MWException($e)), true), true);
			exit(1);
		}
		catch(Exception $e) {
			wfDebugLog(self::LOG_GROUP, var_export(MWExceptionHandler::getLogMessage(new MWException($e)), true), true);

			if ($e instanceof InvalidArgumentException) {
				http_response_code(400);
			}
			exit(1);
		}
	}

	/**
	 * @param $data
	 */
	public function processRequest() {
		wfDebugLog(self::LOG_GROUP, var_export("Incoming request", true), true);
		wfDebugLog(self::LOG_GROUP, var_export($this->alexaRequest->getData(), true), true);

		if ($this->alexaRequest instanceof \Alexa\Request\SessionEndedRequest) {
			$this->onSessionEndedRequest();
		} else {
			$this->initBot();
			$this->processCommand($this->getIntent());
		}
	}

	protected function getIntent() {
		$request = $this->alexaRequest;
		$intent = ReadArticleSkillIntents::INTENT_FALLBACK;

		if ($request instanceof \Alexa\Request\LaunchRequest) {
			$intent = ReadArticleSkillIntents::INTENT_START;
		}

		if ($request instanceof \Alexa\Request\IntentRequest) {
			$intent = $request->getIntentName();
		}

		// Currently we define all action values as intent names
		if ($request instanceof \Alexa\Request\ElementSelectedRequest) {
			$intent = $request->getToken();
		}

		return $intent;
	}

	protected function getSlots() {
		$request = $this->getAlexaRequest();

		$slots = null;
		if ($request instanceof \Alexa\Request\IntentRequest) {
			$slots = $request->getSlots();
		}

		return $slots;
	}

	/**
	 * @return \Alexa\Request\RequestInterface
	 */
	public function getAlexaRequest(): \Alexa\Request\RequestInterface {
		return $this->alexaRequest;
	}

	public function processCommand($intentName) {
		wfDebugLog(self::LOG_GROUP, var_export(__METHOD__ . " - intent name: " .
			$intentName, true), true);

		$factory = OutputSpeechFactory::newInstance(
			$intentName,
			$this->getArticleModel(),
			$this->getSlots(),
			self::USAGE_LOGS_EVENT_TYPE
		);

		$responseText = $factory->getComponent();
		$this->setArticleModel($factory->getBot()->getArticleModel());
		wfDebugLog(self::LOG_GROUP, var_export(__METHOD__ . " - output speech: " .
			$responseText, true), true);
		$response = $this->getTextResponseWithSession($responseText);

		$response = $this->supplementResponse($intentName, $response);

		$this->sendResponse($response);
	}

	/**
	 * @return null|ReadArticleModelV2
	 */
	public function getArticleModel() {
		return $this->articleModel;
	}

	/**
	 * @param ReadArticleModelV2 $articleModel
	 */
	public function setArticleModel($articleModel) {
		$this->articleModel = $articleModel;
	}

	/**
	 * @param $intentName
	 * @param $response WikihowAlexaResponse
	 */
	protected function supplementResponse($intentName, $response) {
		$articleModel = $this->getArticleModel();
		wfDebugLog(self::LOG_GROUP, var_export(__METHOD__, true), true);
		wfDebugLog(self::LOG_GROUP, var_export($articleModel, true), true);

		$factory = EndSessionFactory::newInstance($intentName, $articleModel);
		$shouldEndSession = $factory->getComponent();
		if ($shouldEndSession) {
			$response->endSession();
		}

		$factory = RepromptFactory::newInstance($intentName, $articleModel);
		$repromptText = $factory->getComponent();
		if (!empty($repromptText)) {
			$response->reprompt($repromptText);
		}

		if ($this->deviceSupportsInterface(self::INTERFACE_DISPLAY_TEMPLATE)) {
			$factory = DisplayTemplateFactory::newInstance($intentName, $articleModel,
				$this->deviceSupportsInterface(self::INTERFACE_VIDEO_APP));
			$displayTemplate = $factory->getComponent();
			if (!is_null($displayTemplate)) {
				$response->setDisplayTemplate($displayTemplate);
			}
		}

		if ($this->deviceSupportsInterface(self::INTERFACE_VIDEO_APP)) {
			$factory = VideoAppFactory::newInstance($intentName, $articleModel);
			$videoApp = $factory->getComponent();
			if (!is_null($videoApp)) {
				$response->setVideoApp($videoApp);
				// Don't read step text after playing the video
				$response->respond("");
			}
		}

		$factory = HintFactory::newInstance($intentName, $articleModel);
		$hintText = $factory->getComponent();
		if (!empty($hintText)) {
			$response->setHint($hintText);
		}

		$factory = StandardCardFactory::newInstance($intentName, $articleModel);
		$standardCard = $factory->getComponent();
		if (!is_null($standardCard)) {
			$response->withStandardCard(
				$standardCard->getTitle(),
				$standardCard->getText(),
				$standardCard->getLargeImageUrl(),
				$standardCard->getSmallImageUrl()
			);
		}

		return $response;
	}

	protected function deviceSupportsInterface($interface) {
		wfDebugLog(self::LOG_GROUP, var_export(__METHOD__, true), true);
		$data = $this->alexaRequest->getData();
		$interfaces = array_keys($data['context']['System']['device']['supportedInterfaces']);
		wfDebugLog(self::LOG_GROUP, var_export($interfaces, true), true);
		return in_array($interface, $interfaces);
	}

	protected function sendResponse($response) {
		if (!$this->isValidResponse($response)) {
			$response = $this->getTextResponseNoSession("Sorry, couldn't find anything for you. Try asking me how to do something else.");
			$response->shouldEndSession(false);
		}

		echo json_encode($response->render());

	}

	/**
	 * @param $response
	 * @return bool
	 */
	protected function isValidResponse($response) {
		$renderedResponse = $response->render();
		$jsonResponse = json_encode($renderedResponse);
		return strlen($jsonResponse) <= self::MAX_RESPONSE_BYTE_SIZE;
	}

	protected function getTextResponseNoSession($text) {
		$response = new WikihowAlexaResponse();
		$response->respond($text);
		return $response;
	}

	/**
	 * @param WikihowAlexaResponse $response
	 * @return mixed
	 */
	protected function setSessionAttributes($response) {
		foreach ($this->alexaRequest->getSession()->getAttributes() as $key => $val) {
			$response->addSessionAttribute($key, $val);
		}

		$articleModel = $this->getArticleModel();
		if (!is_null($articleModel)) {
			$response->addSessionAttribute(self::ATTR_ARTICLE, serialize($articleModel));
		}

		return $response;
	}

	/**
	 * @param $responseText
	 * @return WikihowAlexaResponse|mixed
	 */
	protected function getTextResponseWithSession($responseText) {
		$response = $this->getTextResponseNoSession($responseText);
		$response = $this->setSessionAttributes($response);
		return $response;
	}


	protected function onSessionEndedRequest() {
		$response = $this->getTextResponseNoSession("");
		$response->endSession();
		$this->sendResponse($response);
	}

	protected function initBot() {
		$articleData = $this->alexaRequest->getSession()->getAttributes()[self::ATTR_ARTICLE];

		wfDebugLog(self::LOG_GROUP, var_export("Article data: ", true), true);
		wfDebugLog(self::LOG_GROUP, var_export($articleData, true), true);

		if (!empty($articleData)) {
			$this->articleModel = unserialize($articleData);
		}
	}

	/**
	 * @return ReadArticleBotV2
	 */
	protected function getBot() {
		return $this->bot;
	}

	/**
	 * This loads annotations needed for validating the request in the RequestFactory.  Tried using the
	 * AnnotationRegistry::registerAutoloadNamespace with no luck so creating our own annotation loader
	 */
	protected function initAnnotationsLoader() {
		AnnotationRegistry::registerLoader(function ($class) {
			$namespace = "Symfony\Component\Validator\Constraints";
			if (strpos($class, $namespace) === 0) {
				$file = str_replace("\\", DIRECTORY_SEPARATOR, $class);
				$file = explode(DIRECTORY_SEPARATOR, $file);
				$file = array_pop($file) . ".php";
				wfDebugLog("AlexaSkillReadArticleWebHook", var_export("loader file:", true), true);

				global $IP;
				$basePath = "$IP/extensions/wikihow/common/composer/vendor/symfony/validator/Constraints";
				$filePath = $basePath . DIRECTORY_SEPARATOR . $file;
				wfDebugLog("AlexaSkillReadArticleWebHook", var_export("autoloader require:", true), true);

				wfDebugLog("AlexaSkillReadArticleWebHook", var_export($filePath, true), true);
				if (file_exists($filePath)) {
					wfDebugLog("AlexaSkillReadArticleWebHook", var_export("requiring: $filePath", true), true);
					// file exists makes sure that the loader fails silently
					require_once $filePath;
					return true;
				}
			}
		});
	}
}
