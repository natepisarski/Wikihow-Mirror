<?php

/****************
 * Class TrustedSources
 * Source = domain that we have deemed trusted
 * Reference = link on our site
 ****************/

class TrustedSources {
	const TRUSTED_TABLE = "trusted_sources";

	public static $trusted_sources = null;

	public function __construct() {

	}

	public static function getTotalTrustedSources() {
		$dbr = wfGetDB(DB_REPLICA);

		$count = $dbr->selectField(self::TRUSTED_TABLE, 'count(*)', '', __METHOD__);
		return $count;
	}

	public static function getAllSources() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(self::TRUSTED_TABLE, ['*'], [], __METHOD__);

		$results = [];
		while( $row = $dbr->fetchRow($res) ) {
			$results[] = $row;
		}

		return $results;
	}

	public static function addSources($sources) {
		$dbw = wfGetDB(DB_MASTER);

		$dbw->insert(self::TRUSTED_TABLE, $sources, __METHOD__);
	}

	public static function setTrustedSources() {
		self::$trusted_sources = [];

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(WH_DATABASE_NAME_EN . '.' . self::TRUSTED_TABLE, '*', [], __METHOD__);
		while($row = $dbr->fetchRow($res)) {
			self::$trusted_sources[$row['ts_source']] = $row;
		}
	}

	public static function getTrustedReferenceOnTheFly($reference) {
		if(is_null(self::$trusted_sources)) {
			self::setTrustedSources();
		}

		$host = parse_url($reference, PHP_URL_HOST);
		$host = str_replace("www.", "", $host);

		$source = self::$trusted_sources[$host];
		if(is_null($source)) {
			return false;
		} else {
			return $source;
		}
	}

	public static function markTrustedSources($articleId) {
		$out = RequestContext::getMain()->getOutput();
		$isAmp = GoogleAmp::isAmpMode($out);
		$isIntl = RequestContext::getMain()->getLanguage()->getCode() != "en";

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);

		$m = new Mustache_Engine($options);

		//first grab all the links in the article
		foreach(pq(".reference") as $reference) {
			$vars = [];
			unset($url);
			$hideBlurb = false;
			$link = pq("a", $reference);
			$note = $link->attr("href");
			if(pq($note)->parents('#references_second')->length > 0) {
				$vars['expand'] = 1;
			}
			$num = substr($link->text(), 1, -1);
			$bottomReference = pq($note);
			$referenceTextObject =  pq(".reference-text", $bottomReference);

			if(pq("a", $referenceTextObject)->length > 0) {
				//has a link, so use it.
				$url = pq("a", $referenceTextObject)->attr("href");

			} else {
				$referenceText = $referenceTextObject->html();
				//now check the text to see if it's trusted
				$count = preg_match_all('@\[(v[0-9]*_b[0-9]*)\].*@', $referenceText, $matches);
				if($count !== false && $count > 0) {
					$blurbId = $matches[1][0];
					$verifierId = substr($blurbId, 1, strpos($blurbId, "_")-1);
					$vdata = VerifyData::getInfobyBlurbId($blurbId);
					if(is_null($vdata) && $isIntl) {
						$data = VerifyData::getVerifierInfoById($verifierId);
						if(is_array($data)) {
							$vdata = null;
						} else {
							$vdata = $data;
							$hideBlurb = true;

						}
					}
					if(!is_null($vdata)) {
						//we've found info!
						$vars['trustedclass'] = 'trustedsource';
						pq($reference)->addClass("trusted");

						//now replace the text with the actual name
						$interview = wfMessage("ts_interview")->text();
						$hoverText = str_replace("[{$blurbId}].", "{$interview}. ", $referenceText);
						$vars['ts_description'] = $hoverText;
						$vars['showDescription'] = true; //show this description (usually we only show descriptions on EN

						if($hideBlurb) {
							$referenceText = str_replace("[{$blurbId}]", "{$vdata->name}. {$interview}", $referenceText);
						} else {
							$referenceText = str_replace("[{$blurbId}]", "{$vdata->name}. {$vdata->blurb}. {$interview}", $referenceText);
						}
						$referenceTextObject->html($referenceText);

						$vars['hasImage'] = 1;
						if ($isAmp) {
							$image = GoogleAmp::makeAmpImgElement($vdata->imagePath, 30, 30);
						} else {
							$imgAttributes = array(
								'data-src' => $vdata->imagePath,
								'class' => 'ts_expert_image'
							);

							$image = Html::element('img', $imgAttributes);
						}

						$vars['ts_expert_image'] = $image;
						if($hideBlurb) {
							$vars['expert_name'] = $vdata->name;
						} else {
							$vars['expert_name'] = $vdata->name . ". " . $vdata->blurb;
						}
						$vars['expert_url'] = ArticleReviewers::getLinkToCoauthor($vdata);
						$vars['ts_label'] = wfMessage('ts_label_expert')->text();

						$vars['hideLink'] = 1;
					} else {
						//not actually an expert interview, and no link, so now format it
						$vars['ts_description'] = $referenceText;
						$vars['hideLink'] = 1;
					}

				} else {
					//not a expert interview, and no link, so now format it
					$vars['ts_description'] = $referenceText;
					$vars['hideLink'] = 1;
				}
			}

			$vars['num'] = $num;
			if($isAmp) {
				//remove the href tag
				$link->attr("href", "#");
				$vars['isAmp'] = 1;
				$vars['url'] = $note;
				$vars['references'] = wfMessage('references')->text();

				pq($reference)->attr("on", 'tap:ts_popup_' . $num . '.toggleVisibility')->attr("role", "button")->attr("tabindex", "0");
				pq($reference)->append(pq($link)->text());
				pq($link)->remove();
			}
			if(isset($url) && $tr = self::getTrustedReferenceOnTheFly($url)) {
				pq($reference)->addClass("trusted");
				$vars['trustedclass'] = 'trustedsource';
				$vars['ts_name'] = $tr['ts_name'];
				$vars['ts_description'] = $tr['ts_description'];
				$vars['ts_label'] = wfMessage('ts_label')->text();
			}

			if(!$isIntl) {
				$vars['showDescription'] = true;
			}
			$vars['ts_goto'] = wfMessage('ts_goto')->text();
			$vars['ts_research'] = wfMessage('ts_research')->text();

			$html = $m->render('source_popup.mustache', $vars);
			pq($reference)->append($html);

		}
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin) {
		if (!(class_exists('KaiosHelper') && KaiosHelper::isKaiosRequest())) {
			$out->addModules(['ext.wikihow.trusted_sources.scripts']);
			$out->addModuleStyles(['ext.wikihow.trusted_sources.styles']);
		}
	}

	/********
	 * @param $ids
	 * Deletes all sources with the given id, both from the sources table
	 * as well as matches to any articles
	 */
	public static function deleteSources($ids) {
		//now delete from the sources table
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete(self::TRUSTED_TABLE, ['ts_id IN (' . $dbw->makeList($ids) . ')'], __METHOD__);
	}

}
