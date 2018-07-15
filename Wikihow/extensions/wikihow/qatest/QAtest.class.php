<?php

/*
CREATE TABLE `qa_test` (
  `qat_title` varbinary(255) NOT NULL DEFAULT '',
  `qat_question` varbinary(255) NOT NULL DEFAULT '',
  `qat_answer` text NOT NULL,
  KEY `qat_title_index` (`qat_title`)
);
*/

class QAtest {

	static $qaArticles = array(
		'Fall-Asleep',
		'Remove-Verrucas',
		'Write-a-Letter',
		'Soften-Hard-Water',
		'Become-a-Magician',
		'Tell-if-a-Girl-Is-Flirting-With-You',
		'Use-Alcohol-to-Treat-a-Cold',
		'Make-Your-Face-Look-Thinner',
		'Remove-Double-Sided-Tape'
	);
	
	/*
	 * check to see if this article is on our QA list
	*/
	function isQAarticle($title) {		
		$this_article = urldecode($title->getDBKey());
		$res = in_array($this_article, self::$qaArticles);
		return $res;
	}
	
	function getQAs($title) {
		$data = self::getQAdata($title);
		$html = count($data) > 0 ? self::getQAhtml($data) : '';
		return $html;
	}
	
	function getQAdata($title) {
		$this_article = urldecode($title->getDBKey());
		
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('qa_test', '*', array('qat_title' => $this_article), __METHOD__);
		
		foreach ($res as $qa) {
			$qa_array[] = array('q' => $qa->qat_question, 'a' => $qa->qat_answer);
		}
		
		return $qa_array;
	}
	
	function getQAhtml($data) {		
		//randomized tests (0 is control)
		// $rand = mt_rand(0,2);
		// $temp = ($rand == 1) ? 'QAtest' : 'QAtest_accordian';
		// $temp = dirname(__FILE__).'/qatest/'.$temp;
		// $temp = dirname(__FILE__).'/QAtest_sidebox';
		$temp = dirname(__FILE__).'/QAtest';
		
		EasyTemplate::set_path('');
		
		$vars = array(
			'qas' => $data,
		);
		$qa_html = EasyTemplate::html($temp, $vars);
		return $qa_html;
	}
	
	//this uses the phpQuery object already started in WikihowArticleHTML::processArticleHTML()
	function onProcessArticleHTMLAfter($out) {
		global $wgLanguageCode;
		
		if ($wgLanguageCode == 'en') {
			$title = $out->getTitle();
			$user = $out->getUser();
			
			if ($user->isAnon() && QAtest::isQAarticle($title)) {		
				//grab Q&A data
				$qa_html = QAtest::getQAs($title);
				if ($qa_html)  {
					$out->addModules('ext.wikihow.q_and_a');
					//set the div that shows we're running this test
					pq("div.steps:last")->after('<div id="qanda_test_on"></div>');
					pq("#qanda_test_on")->after($qa_html);
				}
			}
		}
	}
	
}
