<?php

class QAUtil {
	const CURATED_QUESTION_ALLOWABLE_TAGS = "<b><i><a>";

	/**
	 * @param $str
	 * @return string
	 */
	public static function stripTags($str) {
		return strip_tags($str, "<b><i><a>");
	}

	/**
	 * Sanitizes _curated_ questions or answers input before going into the database.
	 * Submitted questions have diffferent sanitization - see QAUtil::sanitizeSubmittedInput.
	 * For now, this mainly replaces the "<" character that isn't part of one of the allowed tags.
	 * We do this because PHPQuery barfs on "<" characters and causes bugs like
	 * https://wikihow.lighthouseapp.com/projects/97771/tickets/1934-staffhelpfulness-data-appearing-at-bottom-of-article
	 *
	 * @param $str question or answer text
	 * @return string
	 */
	public static function sanitizeCuratedInput($str, $allowableTags = self::CURATED_QUESTION_ALLOWABLE_TAGS) {
		$str = strip_tags($str, $allowableTags);
		return preg_replace('@(<)(?!/?(a|b|i)[^>]*>)@mi', '&lt;', $str);
	}

	/**
	 * Sanitizes _non-curated questions_ or answers such as submitted questions, proposed answers, or QA Patrol items.
	 * Curated questions and answers have diffferent sanitization. See QAUtil::sanitizeCuratedInput
	 * For now, this mainly replaces the "<" character and strips all tags.
	 * We do this because PHPQuery barfs on "<" characters and causes bugs like
	 * https://wikihow.lighthouseapp.com/projects/97771/tickets/1934-staffhelpfulness-data-appearing-at-bottom-of-article
	 *
	 * @param $str question or answer text
	 * @return string
	 */
	public static function sanitizeSubmittedInput($str) {
		$str = self::stripTags($str);
		return str_replace('<', '&lt;', $str);
	}


	public static function makeEmptyNull(array $arr) {
		return array_map(function($el){
			return empty($el) ? null : $el;
		}, $arr);
	}

	public static function onUnitTestsList(&$files) {
		global $IP;
		$files = array_merge( $files, glob( "$IP/extensions/wikihow/qa/tests/*Test.php" ) );
		return true;
	}

	public static function hasBadWord($content) {
		return BadWordFilter::hasBadWord($content);
	}

	public static function onInsertArticleQuestion($aid, $aqid, $isNew) {
		if ($isNew) {
			//send email to submitter
			$jobTitle = Title::newFromId($aid);
			$jobParams = [
				'qa_id' => $aqid
			];
			$job = Job::factory('QAAnswerEmailJob', $jobTitle, $jobParams);
			JobQueueGroup::singleton()->push($job);
		}
		return true;
	}

	/**
	 * onQAHelpfulnessVote()
	 */
	public static function onQAHelpfulnessVote($aid, $aqid) {
		$milestone = QAHelpfulnessEmailJob::getMilestone($aqid);

		if (!empty($milestone)) {
			$jobTitle = Title::newFromId($aid);
			$jobParams = [
				'qa_id' => $aqid,
				'milestone' => $milestone
			];
			$job = Job::factory('QAHelpfulnessEmailJob', $jobTitle, $jobParams);
			JobQueueGroup::singleton()->push($job);
		}
		return true;
	}

}

