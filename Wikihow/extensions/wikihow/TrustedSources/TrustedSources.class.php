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

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);

		$m = new Mustache_Engine($options);

		//first grab all the links in the article
		foreach(pq(".reference") as $reference) {
			$vars = [];
			$link = pq("a", $reference);
			$note = $link->attr("href");
			if(pq($note)->parents('#references_second')->length > 0) {
				$vars['expand'] = 1;
			}
			$num = substr($link->text(), 1, -1);
			$bottomReference = pq($note);
			$url = pq(".reference-text a", $bottomReference)->attr("href");

			$vars['num'] = $num;
			if($isAmp) {
				//remove the href tag
				$link->attr("href", "#");
				$vars['isAmp'] = 1;
				$vars['url'] = $note;

				pq($reference)->attr("on", 'tap:ts_popup_' . $num . '.toggleVisibility')->attr("role", "button")->attr("tabindex", "0");
				pq($reference)->append(pq($link)->text());
				pq($link)->remove();
			}
			if($tr = self::getTrustedReferenceOnTheFly($url)) {
				pq($reference)->addClass("trusted");
				$vars['trustedclass'] = 'trustedsource';
				$vars['ts_name'] = $tr['ts_name'];
				$vars['ts_description'] = $tr['ts_description'];
			}

			$html = $m->render('source_popup.mustache', $vars);
			pq($reference)->append($html);

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
