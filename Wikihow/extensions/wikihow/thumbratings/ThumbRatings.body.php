<?php

/*
 *
 */
class ThumbRatings extends UnlistedSpecialPage {
	var $cookieName = 'wiki_thr';
	const RATING_TIP = 1;
	const RATING_WARNING = 2;

	public function __construct() {
		parent::__construct('ThumbRatings');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();
		$req = $this->getRequest();

		$out->setArticleBodyOnly(true);
		$aid = $req->getInt('aid');
		$dbw = wfGetDB(DB_MASTER);
		$hash = $dbw->strencode($req->getVal('hash'));
		$vote = $req->getVal('vote');
		$type = $req->getInt('type');

		if (!empty($aid) && !empty($hash) && in_array($vote, ['up', 'down']) && !$user->isBlocked() && !$this->hasRated($hash)) {
			$voteField = $vote == 'up' ? 'tr_up' : 'tr_down';

			// Insert/Update thumb_ratings
			$sql = "INSERT INTO thumb_ratings
				(tr_page_id, tr_hash, $voteField, tr_type)
				VALUES ($aid, '$hash', 1, $type)
				ON DUPLICATE KEY UPDATE $voteField = $voteField + 1";
			$dbw->query($sql, __METHOD__);

			$this->addThumbRating($hash);

			$out->addHTML("recv: $vote\n");
		} else {
			$out->addHTML("recv\n");
		}

	}

	// Returns the array of votes (stored in a cookie) .  Used to prevent duplicate ratings for a tip/warning in same session
	private function getThumbRatings() {
		$cookie = $_COOKIE[$this->cookieName];
		return empty($cookie) ? array() : explode(",", $cookie);
	}

	private function hasRated($hash) {
		return in_array($hash, $this->getThumbRatings());
	}

	private function addThumbRating($hash) {
		$ratings = $this->getThumbRatings();
		$ratings[] = $hash;
		setcookie($this->cookieName, implode(",", $ratings));
	}

	public static function addMobileThumbRatingsHtml(&$doc, &$t) {
		if (self::isValidTitle($t)) {
			$tipsWarnings = self::getTipsWarningsMap($t);
			$types = array(self::RATING_TIP => "tips", self::RATING_WARNING => "warnings");
			foreach ($types as $k => $type) {
				foreach(pq("div#{$type} > ul > li") as $node) {
					// IMPORTANT: Hash on raw html - prior to mod by feature. This will
					// allow for the same hash to be created for www tips/warnings when implemented
					$html = pq($node)->html();
					$hash = md5($html);
					$html = "<div>$html<div class='clearall'></div></div>";
					$data = isset($tipsWarnings[$hash]) ? $tipsWarnings[$hash] : '';
					if (!empty($data)) {
						$up = $data['tr_up'];
						$down = $data['tr_down'];
						$nodisplay = "";
					} else {
						$up = $down = 0;
						$nodisplay = "nodisplay";
					}

					$className = '';
					if ($down >= 10000) {
						$className = 'tr_vote tr_vote_vsmall';
					} elseif ($up >= 1000 || $down >= 1000) {
						$className = 'tr_vote tr_vote_small';
					} else {
						$className = 'tr_vote';
					}
					if ($up >= 100000 || $down >= 100000) {
						if ($up >= 100000)
							$up = '9999+';
						if ($down >= 100000)
							$down = '9999+';
					}

					$prompt = "Helpful?";

					$html .= "<div class='trvote_box'>
								<a href='#' class='trvote_up_{$hash}_{$k} vote_up tr_box'><span class='$className $nodisplay'>$up</span><span class='thumb up_$nodisplay' role='button' aria-label='" . wfMessage('aria_thumbs_up')->showIfExists() . "' /></a>
								<span id='tr_prompt_$hash' class='tr_box tr_prompt'>$prompt</span>
								<a href='#' class='trvote_down_{$hash}_{$k} vote_down tr_box'>&nbsp;<span class='thumb down_$nodisplay' role='button' aria-label='" . wfMessage('aria_thumbs_down')->showIfExists() . "' /><span class='$className $nodisplay'>$down</span></a>
							</div>";
					/*
										$upImgSrc = wfGetPad('/skins/WikiHow/images/thr_up.png');
										$downImgSrc = wfGetPad('/skins/WikiHow/images/thr_down.png');

										$html .= "<div>";
										$html .= "<a href='#' class='trvote_up_{$hash}_{$k} vote_up'><span class='tr_box'>&#8204;<img src='$upImgSrc'/><span class='tr_vote $nodisplay'>$up</span></span></a>";
										$html .= "<span id='tr_prompt_$hash' class='tr_box tr_prompt'>$prompt</span>";
										$html .= "<a href='#' class='trvote_down_{$hash}_{$k} vote_down'><span class='tr_box'>&#8204;<span class='tr_vote $nodisplay'>$down</span><img src='$downImgSrc'/></span></a>";
										$html .= "</div>";
					*/
					pq($node)->html($html);
				}
			}
		}
	}

	public static function injectMobileThumbRatingsHtml(&$xpath, &$t) {
		if (self::isValidTitle($t)) {
			$tipsWarnings = self::getTipsWarningsMap($t);
			$types = array(self::RATING_TIP => "tips", self::RATING_WARNING => "warnings");
			foreach ($types as $k => $type) {
				$nodes = $xpath->query('//div[@id="' . $type . '"]/ul/li');
				foreach ($nodes as $node) {
					// IMPORTANT: Hash on raw html - prior to mod by feature. This will
					// allow for the same hash to be created for www tips/warnings when implemented
					$html = $node->innerHTML;
					$hash = md5($html);
					$html = "<div>$html<div class='clearall'></div></div>";
					$data = $tipsWarnings[$hash];
					if (!empty($data)) {
						$up = $data['tr_up'];
						$down = $data['tr_down'];
						$nodisplay = "";
					} else {
						$up = $down = 0;
						$nodisplay = "nodisplay";
					}

					$className = '';
				   	if ($down >= 10000) {
						$className = 'tr_vote_vsmall';
					} elseif ($up >= 1000 || $down >= 1000) {
						$className = 'tr_vote_small';
					} else {
						$className = 'tr_vote';
					}
					if ($up >= 100000 || $down >= 100000) {
						if ($up >= 100000)
					 		$up = '9999+';
						if ($down >= 100000)
							$down = '9999+';
					}


					$prompt = "Helpful?";

					$html .= "<div class='trvote_box'>
								<a href='#' class='trvote_up_{$hash}_{$k} vote_up tr_box'><span class='thumb up_$nodisplay' /><span class='$className $nodisplay'>$up</span></a>
								<span id='tr_prompt_$hash' class='tr_box tr_prompt'>$prompt</span>
								<a href='#' class='trvote_down_{$hash}_{$k} vote_down tr_box'>&nbsp;<span class='thumb down_$nodisplay' /><span class='$className $nodisplay'>$down</span></a>
							</div>";
/*
					$upImgSrc = wfGetPad('/skins/WikiHow/images/thr_up.png');
					$downImgSrc = wfGetPad('/skins/WikiHow/images/thr_down.png');

					$html .= "<div>";
					$html .= "<a href='#' class='trvote_up_{$hash}_{$k} vote_up'><span class='tr_box'>&#8204;<img src='$upImgSrc'/><span class='tr_vote $nodisplay'>$up</span></span></a>";
					$html .= "<span id='tr_prompt_$hash' class='tr_box tr_prompt'>$prompt</span>";
					$html .= "<a href='#' class='trvote_down_{$hash}_{$k} vote_down'><span class='tr_box'>&#8204;<span class='tr_vote $nodisplay'>$down</span><img src='$downImgSrc'/></span></a>";
					$html .= "</div>";
*/
					$node->innerHTML = $html;
				}
			}
		}
	}

	public static function isValidTitle(&$t) {
		return $t && $t->exists() && $t->inNamespace(NS_MAIN);
	}

	public static function getTipsWarningsMap(&$t) {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('thumb_ratings', '*', array('tr_page_id' => $t->getArticleId()), __METHOD__);
		$tipsWarnings = array();
		foreach ($res as $row) {
			$tipsWarnings[$row->tr_hash] = get_object_vars($row);
		}
		return $tipsWarnings;
	}

	public function isMobileCapable() {
		return true;
	}
}
