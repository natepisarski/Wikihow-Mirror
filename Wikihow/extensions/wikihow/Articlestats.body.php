<?php

class ArticleStats extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ArticleStats' );
	}

	public function execute($par) {
		global $wgParser;

		$req = $this->getRequest();
		$out = $this->getOutput();

		$this->setHeaders();

		$target = $par != '' ? $par : $req->getVal('target');

		if (!$target) {
			$out->addHTML(wfMessage('articlestats_notitle'));
			return;
		}

		$t = Title::newFromText($target);
		$id = $t->getArticleID();
		if ($id == 0) {
			$out->addHTML(wfMessage("checkquality_titlenonexistant"));
			return;
		}

		$dbr = wfGetDB(DB_REPLICA);

		$related = $dbr->selectField(
					"pagelinks",
					"count(*)",
					array ('pl_from' => $id),
					__METHOD__);

		$inbound = self::getInboundLinkCount($t);

		$sources = $dbr->selectField(
						"externallinks",
						"count(*)",
						array( 'el_from' => $t->getArticleID() ),
						__METHOD__);

		$langlinks = $dbr->selectField(
						"langlinks",
						"count(*)",
						array( 'll_from' => $t->getArticleID() ),
						__METHOD__);

		// talk page
		$f = Title::newFromText("Featured", NS_TEMPLATE);

		$tp = $t->getTalkPage();
		$featured = $dbr->selectField(
						"templatelinks",
						"count(*)",
						array( 'tl_from' => $tp->getArticleID(),
							   'tl_namespace' => 10,
							   'tl_title' => 'Featured' ),
						__METHOD__);

		$fadate = "";
		if ($featured > 0) {
			$rev = Revision::newFromTitle($tp);
			$text = ContentHandler::getContentText( $rev->getContent() );
			$matches = array();
			preg_match('/{{(Featured|fa)[^a-z}]*}}/i', $text, $matches);
			$fadate = $matches[0];
			$fadate = preg_replace("@{{(Featured|Fa)\|@i", "", $fadate);
			$fadate = str_replace("}}", "", $fadate);
			if ($fadate) $fadate = "($fadate)";
			$featured = wfMessage('articlestats_yes');
		} else {
			$featured = wfMessage('articlestats_no');
		}

		$rev = Revision::newFromTitle($t);
		$wikitext = ContentHandler::getContentText( $rev->getContent() );
		$section = $wgParser->getSection($wikitext, 0);
		$intro_photo = preg_match('/\[\[Image:/', $section) == 1 ? wfMessage('articlestats_yes') : wfMessage('articlestats_no');

		$section = $wgParser->getSection($wikitext, 1);
		preg_match("/==[ ]*" . wfMessage('steps') . "/", $section, $matches, PREG_OFFSET_CAPTURE);
		if (sizeof($matches) == 0 || $matches[0][1] != 0) {
			$section = $wgParser->getSection($wikitext, 2);
		}

		$num_steps = preg_match_all('/^#/im', $section, $matches);
		$num_step_photos = preg_match_all('/\[\[Image:/', $section, $matches);
		$has_stepbystep_photos = wfMessage('articlestats_no');
		if ($num_steps > 0) {
			$has_stepbystep_photos = ($num_step_photos / $num_steps) > 0.5 ? wfMessage('articlestats_yes') : wfMessage('articlestats_no');
		}


		$linkshere = Title::newFromText("Whatlinkshere", NS_SPECIAL);
		$linksherelink = Linker::link( $linkshere, $inbound, array(), array('target' => urldecode($t->getPrefixedURL()) ) );
		$articlelink = Linker::link( $t, wfMessage('howto', $t->getFullText())->text() );

		$numvotes = $dbr->selectField("rating",
						"count(*)",
						array('rat_page' => $t->getArticleID(), "rat_isdeleted=0"),
						__METHOD__);
		$rating = $dbr->selectField("rating",
						"avg(rat_rating)",
						array('rat_page' => $t->getArticleID(), 'rat_isdeleted' => 0),
						__METHOD__);
		$unique = $dbr->selectField("rating",
						"count(distinct(rat_user_text))",
						array('rat_page' => $t->getArticleID(), "rat_isdeleted=0"),
						__METHOD__);
		$rating = number_format($rating * 100, 0, "", "");


		$a = new Article($t);
		$count = $a->getCount();
		$pageviews = number_format($count, 0, "", ",");


		$accuracy = '<img src="/skins/WikiHow/images/grey_ball.png">&nbsp; &nbsp;' . wfMessage('articlestats_notenoughvotes');
		if ($numvotes >= 5) {
			if ($rating > 70) {
				$ball_color = 'green';
			} elseif ($rating > 40) {
				$ball_color = 'yellow';
			} else {
				$ball_color = 'red';
			}
			$accuracy = '<img src="/skins/WikiHow/images/' . $ball_color . '_ball.png">';
			$accuracy .= "&nbsp; &nbsp;" . wfMessage('articlestats_rating', $rating, $numvotes, $unique);
		}
		if ($index > 10 || $index == 0) {
			$index = wfMessage('articlestats_notintopten', wfMessage('howto', urlencode($t->getText())));
			$index .= "<br/>" . wfMessage('articlestats_lastchecked', substr($max, 0, 10) );
		} elseif ($index < 0) {
			$index = wfMessage('articlestats_notcheckedyet', wfMessage('howto', urlencode($t->getText())));
		} else {
			$index = wfMessage('articlestats_indexrank', wfMessage('howto', urlencode($t->getText())), $index);
			$index .= wfMessage('articlestats_lastchecked', substr($max, 0, 10));
		}

		$cl = SpecialPage::getTitleFor( 'ClearRatings', $t->getText() );

		$out->addHTML("

		<p> $articlelink<br/>
		<table border=0 cellpadding=5>
				<tr><td width='350px;' valign='middle' >
						" . wfMessage('articlestats_accuracy', $cl->getFullText())->parse() . " </td><td valign='middle'> $accuracy<br/> </td></tr>
				<tr><td>" . wfMessage('articlestats_hasphotoinintro')->parse() . "</td><td>$intro_photo </td></tr>
				<tr><td>" . wfMessage('articlestats_stepbystepphotos')->parse() ."</td><td> $has_stepbystep_photos </td></tr>
				<tr><td>" . wfMessage('articlestats_isfeatured')->parse() . "</td><td> $featured $fadate </td></tr>
				<tr><td>" . wfMessage('articlestats_numinboundlinks')->parse() . "</td><td>  $linksherelink</td></tr>
				<tr><td>" . wfMessage('articlestats_outboundlinks')->parse() . "</td><td> $related </td></tr>
				<tr><td>" . wfMessage('articlestats_sources')->parse() . "</td><td> $sources</td></tr>
				<tr><td>" . wfMessage('articlestats_langlinks')->parse() . "</td><td> $langlinks</td></tr>
		</table>
		</p> " . wfMessage('articlestats_footer')->parse() . "
				");

	}

	public static function getInboundLinkCount(Title $title): int {
		$dbr = wfGetDB(DB_REPLICA);
		$tables = ['pagelinks', 'page'];
		$fields = 'count(*)';
		$where = [
			'pl_namespace' => $title->getNamespace(),
			'pl_title' => $title->getDBKey(),
			'page_id = pl_from',
			'page_namespace' => NS_MAIN,
			'page_is_redirect' => 0
		];
		return (int)$dbr->selectField($tables, $fields, $where);
	}

}
