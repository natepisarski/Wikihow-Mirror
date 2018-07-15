<?
class CheckMarks {
	static $stepsMsgs = array(
		"Another step bites the dust.",
		"Crossing things off lists feels good, doesn't it?",
		"Do it again!",
		"Don't stop now, you are on a roll.",
		"Getting close...",
		"Getting closer...",
		"Getting there!",
		"Go you!",
		"Good job. ",
		"Great!",
		"He (or she) shoots, he (or she) scores!",
		"Hurry. Another step awaits!",
		"I can't imagine anyone doing this better.",
		"I feel a completed task in your future.",
		"I forsee many completed steps in your future.",
		"I like the way you check.",
		"I've never seen a better completion of a step.",
		"Keep going!",
		"Keep it up!",
		"Keep on checkin!",
		"Keep on keepin' on!",
		"Keep up the good work.",
		"Movin' right along.",
		"Nailed it!",
		"Nice going!",
		"Nice job!",
		"Nothing succeeds like success.",
		"Now you're cooking!",
		"Oh yeah!",
		"One less thing to do today.",
		"One step closer...",
		"Ready for the next step!",
		"Rock on.",
		"Score.",
		"Hopefully that was easy.",
		"Showing how it's done!",
		"Slow down, you are making the rest of us slackers look bad.",
		"Step by step, you'll make it.",
		"Success.",
		"That step never stood a chance.",
		"That's the sweet smell of progress.",
		"The end is in sight.",
		"Way to stay focused!",
		"I'm very impressed with your progress.",
		"Wasn't that fun?",
		"Woot!",
		"Wow,  nice work!",
		"Wow, that was impressive!",
		"Wow. That was awesome.",
		"Wowsas!",
		"Yippee-kay-yay!",
		"You can do it!",
		"You got this.",
		"You owned that step.",
		"You rock!",
		"You are doing a great job.",
		"You'll get through this in no time.",
		"You're fast!",
		"You're on your way!",
		"Yowza!",
		"I like.",
		"Nice.",
		"Congratulations! ",
		"Congratulations. Can't wait for you to check another! :)",
		"Easy, right? ",
		"Good form!",
		"wikiHow to do it!",
	);

	static $lastStepMsgs = array(
		"Annnnd.... that's a wrap. Good job!",
		"Nice. Now are you ready for another?",
		"Awesome work!",
		"Awesome! You're done.",
		"Good job. Completed with flair and gusto.",
		"Congrats!",
		"Congratulations! You made it to the end.",
		"Couldn't have done it better ourselves.",
		"Fantastic!",
		"Finished! Congrats.",
		"Give yourself a pat on the back!",
		"Congrats. Go forth and show off.",
		"Good job completing this how-to.",
		"You are done. High five!",
		"You are done. Hip, hip, hooray!",
		"How to = DONE.",
		"How to? Not anymore. Now you have know how.",
		"I think you've earned some bragging rights.",
		"Done. And just like a rockstar!",
		"Done. Accomplishment feels good.",
		"Not everyone can how to like you do.  Nice work!",
		"You did it! Tell all your friends. ",
		"Yay, you're done!",
		"Success!",
		"Success! Now that deserves a fist bump.",
		"That's how it's done!",
		"That's it... You're done!",
		"We hereby grant you the title of How to master.",
		"We hope you enjoyed the article!",
		"Congrats. wikiDone well.",
		"wikiDone. Great job.",
		"wikiHow-WOW!",
		"Done. With this new knowledge you will be the envy of your friends.",
		"We knew you could do it. Congrats.",
		"You did it. Great job.",
		"You made that look easy.",
		"You really know how to how to.",
		"You rocked this wikiHow.",
		"You showed that task who's boss!",
		"Success. Good work.",
		"Good job.",
		"You are done. We're very proud of you.",
		"Congratulations! Job well done.",
	);
	
	/*
	* Injects checkmark html into $sections variable returned from MobileBasicArticleBuilder::parseNonMobileArticle
	*/
	public static function injectCheckMarksIntoSteps(&$sections) {
		// Add checkbox, and move the step_num div as a child
		$checkbox = '<div class="step_checkbox"><div class="step_num">\2</div></div>';
		$sections['steps']['html'] = preg_replace('@<li([^<]*)><div class="step_content"><div class="step_num">([^<]+)</div>@', 
			'<li\1>' . $checkbox . '<div class="step_content">', $sections['steps']['html']);
	}
	
	public static function injectCheckMarksIntoSteps_redesign(&$sections) {
		// Add checkbox, and move the step_num div as a child
		$checkbox = '<div class="step_checkbox"><div class="step_num">\2</div><div class="checkwhite"></div></div>';
		$sections['steps']['html'] = preg_replace('@<li([^<]*)><div class="step_content"><div class="step_num">([^<]+)</div>@', 
			'<li\1>' . $checkbox . '<div class="step_content">', $sections['steps']['html']);
	}

	public static function getCheckMarksHtml() {
		EasyTemplate::set_path(dirname(__FILE__));
		$vars['json'] = self::getJSON();
		return EasyTemplate::html('checkmarks.tmpl.php', $vars);
	}

	public static function getStepsMsgs($num = 20) {
		return self::getRandomMsgs(self::$stepsMsgs, $num);
	}

	public static function getLastStepMsg() {
		return self::getRandomMsgs(self::$lastStepMsgs, 1);
	}

	public static function getRandomMsgs(&$msgs, $num = 20) {
		shuffle($msgs);
		$randMsgs = array();
		for ($i = 0; $i < $num; $i++) {
			$randMsgs[] = $msgs[$i];
		}
		return $randMsgs;
	}

	public static function getJSON() {
		return json_encode(array( 
			'msgs' => self::getStepsMsgs(), 
			'last' => self::getLastStepMsg()));
	}
}

