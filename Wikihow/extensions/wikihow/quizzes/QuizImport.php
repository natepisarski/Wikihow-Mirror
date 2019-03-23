<?php
/**
 * Usage: php QuizImport.php
 *
 * Imports our quiz spreadsheet and inserts it into the database *
 * THIS CODE NO LONGER WORKS
 * it is still here as a reference or in case someone wants to fix it
 * but for now it is broken since logging into google as a client no longer works
 * instead you have to use a dservice account.  shouldn't be too hard to fix but
 * not going to do it now
 */

global $IP;
require_once("../../../maintenance/commandLine.inc");
require_once("$IP/extensions/wikihow/titus/GoogleSpreadsheet.class.php");

define('WH_QUIZZES_GOOGLE_DOC','0At1RsX7vnmiVdDJoNmhvWl9JM3dFUWYwbW1wQngxZWc/od6');

class QuizImport {

	//we'll use these as our keys
	private static $quiz_name, $quiz_icon, $quiz_question, $quiz_answer, $quiz_reason;
	private static $import_array = array();


	// moved this over from the GoogleSpreadsheet class but have not tested it yet
	public function getHeaders( $gs, $worksheet ) {
		$url = "https://spreadsheets.google.com/feeds/cells/" . $worksheet . "/private/full";
		$url .= "?access_token=" . $gs->getToken();

		$res = $gs->doAtomXmlRequest($url,array("max-row"=>1));
		$n = 0;
		$xml = simplexml_load_string( $res );
		$cols = array();
		foreach( $xml->entry as $e ) {
			$cols[] = trim( ( string )$e->content );
		}
		return( $cols );
	}

	// moved this over from the GoogleSpreadsheet class but have not tested it yet
	public function getColsWithSpaces( $gs, $worksheet, $startCol, $endCol, $startRow = 1 ) {
		$url="https://spreadsheets.google.com/feeds/cells/" . $worksheet . "/private/full";
		$url .= "?access_token=" . $gs->getToken();
		$res = $gs->doAtomXmlRequest($url,array("min-row"=>$startRow,"min-col"=>$startCol,"max-col"=>$endCol));
		$xml = simplexml_load_string($res);
		$row = array();
		$cols = array();
		$last_pos = 'A';
		foreach($xml->entry as $e) {
			$pos = strtoupper((string)$e->title);
			//new row?
			if (substr($pos,-1,1) !== substr($last_pos,-1,1) && $pos != 'A2') {
				$cols[] = $row;
				$row = array();
			}
			else {
				//did we skip an empty cell?
				$diff = (ord(substr($pos,0,1)) - ord(substr($last_pos,0,1)));
				for ($i=1; $i < $diff; $i++) {
					$row[] = '';
				}
			}

			$row[] = (string)$e->content;
			$last_pos = $pos;
		}
		$cols[] = $row;
		return($cols);
	}

	public function main() {
		print "Getting quizzes\n";
		try {
			$gs = new GoogleSpreadsheet();
			$gs->login(WH_TREBEK_GOOGLE_LOGIN, WH_TREBEK_GOOGLE_PW);

			// TODO in order for this to work the quizzes google doc must be
			// shared with our new google service account that we use for auth.

			$headers = $this->getHeaders( $gs, WH_QUIZZES_GOOGLE_DOC );
			self::parseHeaders($headers);

			$quiz_data = array();

			$cols = $gs->getColsWithSpaces(WH_QUIZZES_GOOGLE_DOC,1,count($headers),2);

			if (!empty($cols)) {
				foreach($cols as $row) {
					$question_array = self::makeQuestionArray($row);

					if (($row[self::$quiz_name] != $last_quiz_name) && ($last_quiz_name != '')) {
						//new quiz!
						//let's stash the old one
						self::saveQuizAsBlob($last_quiz_name,$last_quiz_icon,$quiz_data);
						$quiz_data = array();
					}

					//add in the question
					$quiz_data[] = $question_array;

					//store last quiz name to check on next time
					$last_quiz_name = $row[self::$quiz_name];
					$last_quiz_icon = $row[self::$quiz_icon];
				}
				//save the final quiz
				self::saveQuizAsBlob($last_quiz_name,$last_quiz_icon,$quiz_data);
			}
		}
		catch(Exception $e) {
		}

		//send completion mail
		$to = new MailAddress('elizabeth@wikihow.com, allie@wikihow.com, scott@wikihow.com');
		$from = new MailAddress(WH_TREBEK_GOOGLE_LOGIN);
		$subject = 'Quizzes processed';
		$quizzes = implode(self::$import_array,"\n");
		$body = "Your quizzes have completed processing. Get back to work!\n\n".
				"Oh, and here they are:\n\n$quizzes\n";
		UserMailer::send($to, $from, $subject, $body);

		print "Done.\n";
	}

	/*
	 * Save this full quiz array as a blob in the db
	 */
	private function saveQuizAsBlob($name, $icon, $quiz) {
		if (empty($name) || empty($quiz)) return;

		$quiz_array = array(
					'quiz_name' => $name,
					'quiz_icon' => $icon,
					'quiz_data' => json_encode($quiz),
					'quiz_stamp' => wfTimestampNow()
					);

		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		//is that quiz already in there?
		$count = $dbr->selectField('quizzes', 'count(*) as count', array('quiz_name' => $name), __METHOD);
		if ($count > 0) {
			//it's there. update it...
			$res = $dbw->update('quizzes', $quiz_array, array('quiz_name' => $name), __METHOD__);
			self::$import_array[] = 'UPDATED: http://www.wikihow.com/Quiz/'.$name;
		}
		else {
			//brand spanking new (so to speak)
			$res = $dbw->insert('quizzes', $quiz_array, __METHOD__);
			self::$import_array[] = 'NEW: http://www.wikihow.com/Quiz/'.$name;
		}

		//delete any cached version of these exists just to be safe
		global $wgMemc;
		$memkey = wfMemcKey('quiz',$name);
		$wgMemc->delete($memkey);


		print 'QUIZ: '.$name."\n";
	}

	/*
	 * Assigns the keys based on the header names
	 * (so we can adjust header names,# of answers, etc. in the future)
	 */
	private function parseHeaders($headers) {
		foreach($headers as $key=>$head) {
			$head = trim(strtoupper($head));
			switch ($head) {
				case 'QUIZ NAME': 		self::$quiz_name = $key; break;
				case 'QUIZ VISUAL': 	self::$quiz_icon = $key; break;
				case 'QUESTION': 		self::$quiz_question = $key; break;
				case 'CORRECT ANSWER': 	self::$quiz_answer = $key; break;
				case 'EXPLANATION': 	self::$quiz_reason = $key; break;
			}
		}
	}

	/*
	 * Cycle through the row and format it into our standardized question array
	 */
	private function makeQuestionArray($row) {
		$question = array();

		foreach ($row as $key=>$val) {
			switch ($key) {
				case self::$quiz_name:
				case self::$quiz_icon:
				case self::$quiz_question: 	$question['question'] = $val; break;
				case self::$quiz_answer: 	$question['correct'] = $val; break;
				case self::$quiz_reason: 	$question['reason'] = $val; break;
				default:
					if (!empty($val)) {
						$question['answers'][] = $val;
					}
			}
		}
		return $question;
	}
}

QuizImport::main();
