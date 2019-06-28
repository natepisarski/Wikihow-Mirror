<?php

class TechRating {

	public static function insertTechRating($articleId) {
		if(AlternateDomain::getAlternateDomainForCurrentPage() != "wikihow.tech") return;

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__)
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		//find out if we've helped someone recently
		$weekAgo = wfTimestamp(TS_DB, strtotime("1 week ago"));
		$monthAgo = wfTimestamp(TS_DB, strtotime("1 month ago"));

		$articleRating = new RatingArticle();
		$weekCount = $articleRating->getRatingCountForPeriod($articleId, $weekAgo);
		$monthCount = $articleRating->getRatingCountForPeriod($articleId, $monthAgo);
		if($weekCount > 0) {
			$data['helped_count'] = $weekCount;
			$readers = $weekCount == 1 ? "reader" : "readers";
			$data['helped_text'] = "<br /><span class='tech_reader'>{$readers}</span> helped<br/>this week!";
		} elseif($monthCount > 0) {
			$data['helped_count'] = $monthCount;
			$readers = $monthCount == 1 ? "reader" : "readers";
			$data['helped_text'] = "<br /><span class='tech_reader'>{$readers}</span> helped<br/>this month!";
		} else {
			$data['helped_text'] = "Did this article<br />help you?";
			$data['tech_class'] = "none";
		}

		$html = $m->render('techrating', $data);
		pq('.firstHeading')->before($html);
	}
}
