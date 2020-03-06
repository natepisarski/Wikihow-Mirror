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
		$res = $dbr->select(self::TRUSTED_TABLE, '*', [], __METHOD__);
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
		if($isAmp) return;
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);

		$m = new Mustache_Engine($options);

		//first grab all the links in the article
		foreach(pq(".reference") as $reference) {
			$note = pq("a", $reference)->attr("href");
			$bottomReference = pq($note);
			$url = pq(".reference-text a", $bottomReference)->attr("href");

			if($tr = self::getTrustedReferenceOnTheFly($url)) {
				pq($reference)->addClass("trusted");
				//pq($bottomReference)->addClass("trusted");
				$tr['reference'] = $url;
				$tr['trustedclass'] = 'trustedsource';
				$html = $m->render('source_popup.mustache', $tr);
				pq($reference)->append($html);
			} else {
				//not trusted, so show something different
				$tr['url'] = $url;
				$html = $m->render('source_popup.mustache', $tr);
				pq($reference)->append($html);
			}

		}

		//now check any remaining references not from the text
		foreach(pq("#references li") as $reference) {
			if(is_null(pq($reference)->attr("id"))) {
				$url = pq(".reference-text a", $reference)->attr("href");

				if($tr = self::getTrustedReferenceOnTheFly($url)) {
					pq($reference)->addClass("trusted");

					$tr['reference'] = $url;
					$html = $m->render('source_popup.mustache', $tr);
					pq($html)->insertAfter($reference);
				}
			}
		}
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin) {
		$out->addModules(['ext.wikihow.trusted_sources.scripts']);
		$out->addModuleStyles(['ext.wikihow.trusted_sources.styles']);
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
