<?php
//use pimax\FbBotApp;
use pimax\Messages\ImageMessage;
use pimax\Messages\Message;
use pimax\Messages\MessageButton;
use pimax\Messages\StructuredMessage;

class MessengerSearchBot extends UnlistedSpecialPage {
	const LOG_GROUP = 'Bot';
	const NUM_SEARCH_RESULTS = 10;
	const NUM_DISPLAY_TITLES = 3;

	var $bot = null;
	var $data = null;
	var $message = null;

	const COMMAND_CTA = 'wh_cta';
	const COMMAND_CTA_ACCEPTED = 'wh_cta_accepted';
	const COMMAND_IMAGE = 'image';
	const COMMAND_STICKER = 'sticker';
	const COMMAND_HOWTO = 'howto';
	const COMMAND_HELP = 'help';
	const COMMAND_UNKNOWN = 'unknown';

	const TYPE_IMAGE = 'image';
	const TYPE_STICKER = 'sticker';

	const COMMAND_SOMETHING_ELSE = "postback_something_else";

	function __construct() {
		parent::__construct('MessengerSearchBot');
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
		// Define an error handler if you need to debug errors
		//error_reporting(E_CORE_ERROR|E_COMPILE_ERROR);
		//$old_error_handler = set_error_handler("MessengerSearchBot::errorHandler");
		//register_shutdown_function("MessengerSearchBot::fatalHandler");

		// Needed for search to work properly
		$_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36";

		$this->getOutput()->setRobotPolicy('noindex,nofollow');
		$this->getOutput()->setArticleBodyOnly(true);

		$verify_token = WH_FACEBOOK_MESSENGER_BOT_WEBOOK_VERIFY_TOKEN; // Verify token
		$pageAccessToken = WH_FACEBOOK_MESSENGER_BOT_PAGE_ACCESS_TOKEN; // Page token

		try {
			wfDebugLog(self::LOG_GROUP, var_export("starting request", true), true);
			// Make Bot Instance
			$this->bot = new WikihowFbBotApp($pageAccessToken);

			if ($this->getRequest()->getVal('a', '') == 'cta' && $this->getUser()->getName() == 'Jordansmall') {
				$this->newCTA();
				return;
			}

			// If we need to redefine the cta for this bot, modify the CallsToAction class and uncomment this
			//$this->newCTA();exit;

			// Receive something
			if (!empty($_REQUEST['hub_mode']) && $_REQUEST['hub_mode'] == 'subscribe' && $_REQUEST['hub_verify_token'] == $verify_token) {
				// Webhook setup request
				wfDebugLog(self::LOG_GROUP, var_export("Webhook setup request", true), true);
				echo $_REQUEST['hub_challenge'];
			} else {
				// Other event
				wfDebugLog(self::LOG_GROUP, var_export("Normal event request", true), true);
				$this->data = json_decode(file_get_contents("php://input"), true);
				$this->processMessages();
			}
		} catch(Error $e) {
			wfDebugLog(self::LOG_GROUP, var_export(MWExceptionHandler::getLogMessage(new MWException($e)), true), true);
			exit(1);
		}
		catch(Exception $e) {
			wfDebugLog(self::LOG_GROUP, var_export(MWExceptionHandler::getLogMessage(new MWException($e)), true), true);
			exit(1);
		}
	}

	/**
	 * @param $data
	 */
	public function processMessages() {
		$data = $this->data;
		if (!empty($data['entry'][0]['messaging'])) {
			foreach ($data['entry'][0]['messaging'] as $message) {
				$this->message = $message;
				wfDebugLog(self::LOG_GROUP, var_export($message, true));

				// Skipping delivery messages
				if (!empty($message['delivery'])) {
					continue;
				}

				// When bot receive message from user
				$command = "";
				$originalCommand = "";
				if (!empty($message['message'])) {
					// Is it a sticker or an image?
					if (isset($message['message']['attachments']['0']['type'])) {
						if (isset($message['message']['sticker_id'])) {
							$command = self::COMMAND_STICKER;
						} elseif ($message['message']['attachments']['0']['type'] == self::TYPE_IMAGE) {
							$command = self::COMMAND_IMAGE;
						} else {
							$command = self::COMMAND_UNKNOWN;
						}
					} else {
						$originalCommand = $message['message']['text'];
						$command = $this->parseCommand($originalCommand);
					}
				// When bot receive button click from user
				} elseif (!empty($message['postback'])) {
					$originalCommand = $message['postback']['payload'];
					$command = $this->parseCommand($originalCommand);
				}


				$this->processCommand($command);
			}
		}
	}

	function parseCommand($command) {
		$command = strtolower($command);
		if (preg_match('@how to|how do|how can|how should@', $command)) {
			$command = self::COMMAND_HOWTO;
		}
		wfDebugLog(self::LOG_GROUP, "Command: $command", true);
		return $command;
	}

	/**
	 * @param $command
	 * @param $rawCommand
	 * @param $message
	 */
	public function processCommand($command) {
		switch ($command) {
			case self::COMMAND_CTA:
				$this->handleCTA();
				break;
			case self::COMMAND_CTA_ACCEPTED:
				$this->handleCtaAccepted();
				break;
			case self::COMMAND_HELP:
				$this->handleHelp();
				break;
			case self::COMMAND_HOWTO:
				$this->handleHowTo();
				break;
			case self::COMMAND_IMAGE:
				$this->handleImage();
				break;
			case self::COMMAND_STICKER:
				$this->handleSticker();
				break;
			case self::COMMAND_UNKNOWN:
				$this->handleUnknownMessage();
				break;
			case self::COMMAND_SOMETHING_ELSE:
				$this->handleSomethingElse();
				break;
			default:
				$this->handleDefault($command);
		}
	}

	protected function handleSomethingElse() {
		$recipientId = $this->message['sender']['id'];
		$somethingElseReponses = [
			"What are you interested in learning today? :)",
			"Ask me how to do something random!",
			"What have you always wanted to learn how to do?",
			"Is there anything you want to learn how to do?",
			"Ask me how to do anything. Even the oddest request won’t surprise me.",
			"Why don’t you see if there’s anything I don’t know how to do? I’m curious too!",
		];

		$message = $this->getRandomElement($somethingElseReponses);
		$this->sendTextResponse($recipientId, $message);
	}

	protected function sendTextResponse($recipientId, $text) {
		$this->bot->send(new Message($recipientId, $text));
	}

	protected function handleUnknownMessage() {
		$recipientId = $this->message['sender']['id'];
		$this->sendTextResponse($recipientId, wfMessage('unknown_message')->text());
	}

	protected function handleImage() {
		$message = $this->message;
		$imageUrl = $message['message']['attachments']['0']['payload']['url'];
		$recipientId = $this->message['sender']['id'];
		$this->bot->send(new ImageMessage($recipientId, $imageUrl));
	}

	protected function handleSticker() {
		$message = $this->message;
		$recipientId = $this->message['sender']['id'];
		$stickerId = $message['message']['sticker_id'];

		$stickerResponses = [
			"126361874215276" => "Yay! Smiling releases endorphins!",
			"126361920881938" => "I’m sorry you feel this way. What’s troubling you?",
			"126361974215266" => "Sorry you’re going through this. What would help?",
			"126362034215260" => "We can work together to make this situation better. Tell me what you are looking for :)",
			"126362117548585" => "Whoo hoo! Go you!",
			"126362044215259" => "You deserve to be happy :)",
			"126361910881939" => "Was this confusing? Let me know how I can help!",
			"126361967548600" => "Congratulations! What’s the next step!",
			"126362187548578" => "Celebrate good times, c’mon!",
			"126362100881920" => "Sharing your bliss <3 <3 <3",
			"126362197548577" => "Work hard, dream big.",
			"126362007548596" => "You are a rockstar.",
			"126362074215256" => "Trust the work you put in.",
		];

		if (isset($stickerResponses[$stickerId])) {
			$text = $stickerResponses[$stickerId];
			$this->sendTextResponse($recipientId, $text);
		} else {
			$this->handleImage();
		}
	}

	protected function handleDefault() {
		$message = $this->message;
		$command = trim(strtolower($message['message']['text']));
		$recipientId = $this->message['sender']['id'];

		$titleResponses = [
			'weather' => 'Predict-the-Weather-Without-a-Forecast',
			'happy' => 'Be-Happy',
			'sad' => 'Cheer-Up'
		];
		$titleResponses = array_change_key_case($titleResponses);

		$textResponses = [
			'lol' => ':)',
			'bye bye' => 'later',
			'thanku' => 'happy to help!',
			'thank u' => 'welcome!',
			'sup' => 'Hey. How can we help?',
			'yo' => 'yo',
			'whassup' => 'Not much. How can we help?',
			'how’s it going?' => 'Pretty good. What can I help you learn how to do?',
			'how\'s it going?' => 'Pretty good. What can I help you learn how to do?',
			'hows it going?' => 'Pretty good. What can I help you learn how to do?',
			'how’s it going' => 'Pretty good. What can I help you learn how to do?',
			'how\'s it going' => 'Pretty good. What can I help you learn how to do?',
			'hows it going' => 'Pretty good. What can I help you learn how to do?',
			'yes' => 'got it',
			'no' => 'ok',
			'ok' => 'cool',
			'k' => 'cool',
			'kk' => 'cool',
			'okk' => 'cool',
			'okay' => 'cool',
			'kay' => 'cool',
			'goodbye' => 'see you later!',
			'good' => "Good, I'm glad.",
			'hello' => "Hi! Nice to see you.",
			'hi' => "Hi! Nice to see you.",
			'hey' => "Hi! Nice to see you.",
			'bye' => "Goodbye",
			'wikihow' => "Yes, wikiHow is pretty awesome!",
			"Hi" => "Hi, thank you for reaching out! How can we help?",
			"Hi!" => "Hi, thank you for reaching out! How can we help?",
			"Hello" => "Hi, thank you for reaching out! How can we help?",
			"Hello!" => "Hi, thank you for reaching out! How can we help?",
			"Hello?" => "Hi, thank you for reaching out! How can we help?",
			"Hello ?" => "Hi, thank you for reaching out! How can we help?",
			"Thanks" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thanks." => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thanks!" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thanks :)" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thanks :-)" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thanks (:" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank you" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank you." => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank you!" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank you! :)" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank you! :-)" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank you! (:" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank you very much" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank you very much." => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank you very much!" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thanks a lot" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thanks a lot." => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thanks a lot!" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank u" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank u." => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"Thank u!" => "I’m so happy to help! Feel free to ask me how to do anything anytime.",
			"How are you" => "I’m good! How can I help you today?",
			"How are you?" => "I’m good! How can I help you today?",
			"How are u" => "I’m good! How can I help you today?",
			"How are u?" => "I’m good! How can I help you today?",
			"How are u ?" => "I’m good! How can I help you today?",
			"How r u" => "I’m good! How can I help you today?",
			"How r u?" => "I’m good! How can I help you today?",
			"How r u?" => "I’m good! How can I help you today?",
			"Bye" => "Talk to you soon! :)",
			"Bye!" => "Talk to you soon! :)",
			"Bye." => "Talk to you soon! :)",
			"Stupid" => "No one is stupid. Believe in yourself: http://www.wikihow.com/Believe-in-Yourself",
			"This is stupid" => "No one is stupid. Believe in yourself: http://www.wikihow.com/Believe-in-Yourself",
			"You are stupid" => "No one is stupid. Believe in yourself: http://www.wikihow.com/Believe-in-Yourself",
			"wikiHow Ads" => "If you’re an advertiser, please email your request to ads@wikiHow.com",
			"wikiHow Advertising" => "If you’re an advertiser, please email your request to ads@wikiHow.com",
			"wikiHow Ad" => "If you’re an advertiser, please email your request to ads@wikiHow.com",
			"wikihow artist" => "Are you a fan? Thank you! :)",
			"wikihow artists" => "Are you a fan? Thank you! :)",
			"wikihow artist?" => "Are you a fan? Thank you! :)",
			"wikihow artists?" => "Are you a fan? Thank you! :)",
			"wikihow art" => "Are you a fan? Thank you! :)",
			"wikihow art?" => "Are you a fan? Thank you! :)",
			"wikiHow Translation" => "Thank you for your inquiry. You can help with our language projects here: http://www.wikihow.com/wikiHow:Language-Projects",
			"wikiHow Translator" => "Thank you for your inquiry. You can help with our language projects here: http://www.wikihow.com/wikiHow:Language-Projects",
			"Translation wikiHow" => "Thank you for your inquiry. You can help with our language projects here: http://www.wikihow.com/wikiHow:Language-Projects",
			"Translator wikiHow" => "Thank you for your inquiry. You can help with our language projects here: http://www.wikihow.com/wikiHow:Language-Projects",
			"wikiHow translating" => "Thank you for your inquiry. You can help with our language projects here: http://www.wikihow.com/wikiHow:Language-Projects",
			"translating wikihow" => "Thank you for your inquiry. You can help with our language projects here: http://www.wikihow.com/wikiHow:Language-Projects",
			"wikiHow Job" => "Hi, thank you for your inquiry. You can take a look at our jobs page for openings here: http://www.wikihow.com/wikiHow:Jobs :)",
			"wikiHow Jobs" => "Hi, thank you for your inquiry. You can take a look at our jobs page for openings here: http://www.wikihow.com/wikiHow:Jobs :)",
			"Work for you" => "Hi, thank you for your inquiry. You can take a look at our jobs page for openings here: http://www.wikihow.com/wikiHow:Jobs :)",
			"Work for u" => "Hi, thank you for your inquiry. You can take a look at our jobs page for openings here: http://www.wikihow.com/wikiHow:Jobs :)",
			"work for wikihow" => "Hi, thank you for your inquiry. You can take a look at our jobs page for openings here: http://www.wikihow.com/wikiHow:Jobs :)",
			"wikihow work" => "Hi, thank you for your inquiry. You can take a look at our jobs page for openings here: http://www.wikihow.com/wikiHow:Jobs :)",
			"Join you" => "That's great! Here's how you can join: https://ssl.wikihow.com/index.php?title=Special:UserLogin&type=signup If you would like to help, there are literally thousands of ways!: http://www.wikihow.com/Special:CommunityDashboard :)",
			"join u" => "That's great! Here's how you can join: https://ssl.wikihow.com/index.php?title=Special:UserLogin&type=signup If you would like to help, there are literally thousands of ways!: http://www.wikihow.com/Special:CommunityDashboard :)",
			"join wikihow" => "That's great! Here's how you can join: https://ssl.wikihow.com/index.php?title=Special:UserLogin&type=signup If you would like to help, there are literally thousands of ways!: http://www.wikihow.com/Special:CommunityDashboard :)",
			"Permission to use wikiHow" => "For permission to use wikiHow articles for commercial projects, please email: support@wikiHow.com",
			"wikiHow Permission" => "For permission to use wikiHow articles for commercial projects, please email: support@wikiHow.com",
			"wikiHow Attribution" => "For permission to use wikiHow articles for commercial projects, please email: support@wikiHow.com",
			"Attribution wikiHow" => "For permission to use wikiHow articles for commercial projects, please email: support@wikiHow.com",
			"Permission wikiHow" => "For permission to use wikiHow articles for commercial projects, please email: support@wikiHow.com",
			"Post wikiHow" => "For permission to use wikiHow articles for commercial projects, please email: support@wikiHow.com",
			"Share wikiHow" => "For permission to use wikiHow articles for commercial projects, please email: support@wikiHow.com",
			"About wikiHow" => "Hi, thank you for your inquiry! To learn more about wikiHow go here: http://www.wikihow.com/wikiHow:About-wikiHow",
			"What is wikiHow?" => "Hi, thank you for your inquiry! To learn more about wikiHow go here: http://www.wikihow.com/wikiHow:About-wikiHow",
			"What is wikiHow" => "Hi, thank you for your inquiry! To learn more about wikiHow go here: http://www.wikihow.com/wikiHow:About-wikiHow",
			"What does wikiHow do" => "Hi, thank you for your inquiry! To learn more about wikiHow go here: http://www.wikihow.com/wikiHow:About-wikiHow",
			"What does wikiHow do?" => "Hi, thank you for your inquiry! To learn more about wikiHow go here: http://www.wikihow.com/wikiHow:About-wikiHow",
			"Person" => "Want to talk to a real human? Sure, just email: support@wikiHow.com",
			"Real person" => "Want to talk to a real human? Sure, just email: support@wikiHow.com",
			"Person?" => "Want to talk to a real human? Sure, just email: support@wikiHow.com",
			"Real person?" => "Want to talk to a real human? Sure, just email: support@wikiHow.com",
			"wikiHow real person" => "Want to talk to a real human? Sure, just email: support@wikiHow.com",
			"wikiHow person" => "Want to talk to a real human? Sure, just email: support@wikiHow.com",
			"wikihow real person?" => "Want to talk to a real human? Sure, just email: support@wikiHow.com",
			"wikihow person?" => "Want to talk to a real human? Sure, just email: support@wikiHow.com",
			"human" => "Want to talk to a real human? Sure, just email: support@wikiHow.com",
			"human?" => "Want to talk to a real human? Sure, just email: support@wikiHow.com",
			"No article" => "Thanks so much for your request! Unfortunately wikiHow doesn’t appear to have a title on that topic yet. You can either help us out by writing it yourself, or you can request the title be written here: http://www.wikihow.com/Request-a-New-Article-Topic-Be-Written-on-wikiHow",
			"Article does not exist" => "Thanks so much for your request! Unfortunately wikiHow doesn’t appear to have a title on that topic yet. You can either help us out by writing it yourself, or you can request the title be written here: http://www.wikihow.com/Request-a-New-Article-Topic-Be-Written-on-wikiHow",
			"Article not exist" => "Thanks so much for your request! Unfortunately wikiHow doesn’t appear to have a title on that topic yet. You can either help us out by writing it yourself, or you can request the title be written here: http://www.wikihow.com/Request-a-New-Article-Topic-Be-Written-on-wikiHow",
			"No article wikiHow" => "Thanks so much for your request! Unfortunately wikiHow doesn’t appear to have a title on that topic yet. You can either help us out by writing it yourself, or you can request the title be written here: http://www.wikihow.com/Request-a-New-Article-Topic-Be-Written-on-wikiHow",
			"Article does not exist wikiHow" => "Thanks so much for your request! Unfortunately wikiHow doesn’t appear to have a title on that topic yet. You can either help us out by writing it yourself, or you can request the title be written here: http://www.wikihow.com/Request-a-New-Article-Topic-Be-Written-on-wikiHow",
			"Article not exist wikiHow" => "Thanks so much for your request! Unfortunately wikiHow doesn’t appear to have a title on that topic yet. You can either help us out by writing it yourself, or you can request the title be written here: http://www.wikihow.com/Request-a-New-Article-Topic-Be-Written-on-wikiHow",
			"No help wikiHow" => "Thanks so much for your request! Unfortunately wikiHow doesn’t appear to have a title on that topic yet. You can either help us out by writing it yourself, or you can request the title be written here: http://www.wikihow.com/Request-a-New-Article-Topic-Be-Written-on-wikiHow",
			":)" => ":)",
			":-)" => ":-)",
			"(:" => "(:",
			"(Y)" => "Thanks, happy to help!",
			"<3" => "<3",
			"8-)" => "8-)",
			"B-)" => "B-)",
			"^_^" => "If you’re happy, we’re happy! ^_^",
			"-_-" => "Oh no, what’s wrong? Tell us how we can help :)",
			"o.O" => "Whoa… so, how can we help?",
			"O.o" => "Whoa… so, how can we help?",
			":3" => "Mmm… tell us how we can help? :)",
			"3:)" => "You know, I’ve never understood the meaning of this emoji",
			":O" => "Surprised? Let us know if we can help!",
			":-O" => "Surprised? Let us know if we can help!",
			":p" => "Questions are always good, never silly! Just ask :)",
			":-p" => "Questions are always good, never silly! Just ask :)",
			":/" => "What’s wrong… you can tell us! :)",
			":-/" => "What’s wrong… you can tell us! :)",
			":\\" => "What’s wrong… you can tell us! :)",
			":-\\" => "What’s wrong… you can tell us! :)",
			"O:)" => "Us too O:)",
			"O:-)" => "Us too O:)",
			";)" => "Is there something in your eye? ;)",
			";-)" => "Is there something in your eye? ;)",
			":v" => "Feeling chatty? Ask us anything!",
			">:(" => "What’s the matter? Let us help?",
			">:-(" => "What’s the matter? Let us help?",
			">:o" => "Don’t worry, your secret is safe with us.",
			">:O" => "Don’t worry, your secret is safe with us.",
			">:-o" => "Don’t worry, your secret is safe with us.",
			">:-O" => "Don’t worry, your secret is safe with us.",
			">.<" => "Don’t worry, your secret is safe with us.",
			":(" => "Turn that frown upside down! Maybe we can help? :)",
			":'(" => "Wipe that tear away… tell us how we can help you move forward :)",
			":’-(" => "Wipe that tear away… tell us how we can help you move forward :)",
			":D" => ":D",
			":-D" => ":-D",
			":*" => "A kiss… for me? You’re too kind <3",
			":-*" => "A kiss… for me? You’re too kind <3",
			"☺" => "Now we’re smiling together :)",
			"☻" => "Now we’re smiling together :)",
			"(^^^)" => "We’ve been prepared for this day… http://www.wikihow.com/Survive-a-Shark-Attack",
			"<(\")" => "*waddle* *waddle*",
			":putnam:" => "Um…",
			":poop:" => "Quick! Check if it's healthy! http://www.wikihow.com/Check-Your-Health-by-Poop-or-Stool-Colors",
			":|]" => "You don’t even know! Check it: http://www.wikihow.com/Do-the-Robot",
		];

		$textResponses = array_change_key_case($textResponses);

		if (isset($titleResponses[$command])) {
			$titles = [Title::newFromText($titleResponses[$command])];
			$message = new WikihowTitlesMessage($titles, $recipientId);
			$response = $this->bot->send($message);
			wfDebugLog(self::LOG_GROUP, var_export($response, true), true);
		} elseif (isset($textResponses[$command])) {
			$this->sendTextResponse($recipientId, $textResponses[$command]);
		} else {
			$this->handleHowTo();
		}
	}

	protected function handleCta() {
		$recipientId = $this->message['sender']['id'];
		$message = new StructuredMessage(
			$recipientId,
			StructuredMessage::TYPE_BUTTON,
			[
				'text' => wfMessage('cta_message')->text(),
				'buttons' => [
					new MessageButton(
						MessageButton::TYPE_POSTBACK,
						wfMessage('cta_button')->text(),
						self::COMMAND_CTA_ACCEPTED
					)
				]
			]
		);
		$this->bot->send($message);
	}

	protected function handleCtaAccepted() {
		$recipientId = $this->message['sender']['id'];


		$this->sendTextResponse($recipientId, wfMessage('cta_accepted_message')->text());

		$button = new MessageButton(
			MessageButton::TYPE_POSTBACK,
			wfMessage('cta_howto_button')->text(),
			self::COMMAND_SOMETHING_ELSE
		);

		$titles = [
			Title::newFromId(2856), // Change a tire
			Title::newFromId(893566), // Revive a goldfish
		];
		$message = new WikihowTitlesMessage($titles, $recipientId, $button);
		$this->bot->send($message);
	}

	protected function getRandomElement($arr) {
		return $arr[mt_rand(0, count($arr) - 1)];
	}

	protected function handleHelp() {
		$recipientId = $this->message['sender']['id'];
		$this->sendTextResponse($recipientId, wfMessage('help_menu')->text());
	}

	protected function handleHowTo() {
		$message = $this->message;
		$query = $message['message']['text'];
		$recipientId = $message['sender']['id'];

		$searchResponses = [
			"Looking for you now…",
			"I’m on it…",
			"Let me see…",
			"Checking across all of our articles…",
			"Looking to see if I can help you…",
			"I'm looking…",
			"You’re one of 100 million people I’ve helped this month, but I think I like you the most…",
			"Consulting the wisdom of thousands of people to help you out…",
			"Looking for the right answer…",
			"Looking…",
			"Quickly getting it for you…",
		];
		$this->sendTextResponse($recipientId, $this->getRandomElement($searchResponses));

		$search = new LSearch();
		$titles = $search->externalSearchResultTitles($query, 0, self::NUM_SEARCH_RESULTS, 0, LSearch::SEARCH_INTERNAL);

		if (empty($titles)) {
			$emptyResponses = [
				"Sorry, couldn't find anything for you. Want to ask something else?",
				"I know a lot of things, but apparently I don't know that.  Can you try rephrasing it?",
				"Sorry, nothing came up! Can you try again?",
				"Hm… Can you please try rephrasing your question?",
				"I know a lot of things, but I don’t know that. Want to try asking me something else?",
				"Gosh, I know a lot of things but you’ve stumped me! Want to ask me something else?",

			];
			$this->sendTextResponse($recipientId, $this->getRandomElement($emptyResponses));
		} else {
			$titles = WikihowTitlesMessage::filterTitlesByCategory($titles);
			$titles = WikihowTitlesMessage::filterTitlesByName($titles, ['Main-Page']);
			if (self::NUM_DISPLAY_TITLES < count($titles)) {
				$titles = array_splice($titles, 0, self::NUM_DISPLAY_TITLES);
			}

			$button = new MessageButton(
				MessageButton::TYPE_POSTBACK,
				wfMessage('cta_howto_button')->text(),
				self::COMMAND_SOMETHING_ELSE
			);
			$message = new WikihowTitlesMessage($titles, $recipientId, $button);
			$response = $this->bot->send($message);
			wfDebugLog(self::LOG_GROUP, var_export($response, true), true);
		}
	}

	/**
	 * Set the CTA for the bot
	 * @return mixed
	 */
	public function newCTA() {
		global $wgIsProduction;
		$pageId = $wgIsProduction ? '91668358574' : '260787177594903';
		$response = $this->bot->cta($pageId, new CallsToActionMessage());
		echo json_encode($response);
		return $response;
	}
}
