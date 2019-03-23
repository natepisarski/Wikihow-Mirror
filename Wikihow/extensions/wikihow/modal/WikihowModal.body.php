<?php

class BuildWikihowModal extends UnlistedSpecialPage {

    public function __construct() {
        parent::__construct('BuildWikihowModal');
    }

    public function execute($par) {
		global $wgSquidMaxage;
		$ctx = RequestContext::getMain();
		$request = $ctx->getRequest();
		$out = $ctx->getOutput();

		EasyTemplate::set_path(__DIR__);

		$modal_type = $request->getVal('modal');
		if ($modal_type == 'firstedit') {
			$modal = self::getFirstEditModal();
		}
		elseif ($modal_type == 'expertise') {
			$modal = self::getExpertiseModal();
		}
		elseif ($modal_type == 'expertise2') {
			$modal = self::getExpertiseModal2($request->getVal('cat'));
		}
		elseif ($modal_type == 'helpfulness') {
			$out->setSquidMaxage($wgSquidMaxage); //make sure this caches
			$modal = self::getHelpfulnessModal();
		}
		elseif ($modal_type == 'helpfulness2') {
			$modal = self::getHelpfulnessModal2($request->getVal('aid'));
		}
		elseif ($modal_type == 'printview') {
			$modal = self::getPrintViewModal();
		}
		elseif ($modal_type == 'login') {
			$modal = PagePolicy::getLoginModal($request->getVal('returnto'));
		}
		elseif ($modal_type == 'flagasdetails') {
			$modal = self::getFlagAsDetailsModal();
		}
		elseif ($modal_type == 'discusstab') {
			$modal = self::getDiscussTabModal($request->getVal('aid'), $request->getVal('already_rated'));
		}

		$out->setArticleBodyOnly(true);
		$out->addHTML($modal);
		return;
	}

	private static function getFirstEditModal() {
		$nt = array(
			array('/Special:Spellchecker', wfMessage('first_edit_btn_spelling')->text()),
			array('/Special:CategoryGuardian', wfMessage('first_edit_btn_category')->text()),
			array('/Special:EditFinder/Topic', wfMessage('first_edit_btn_topic')->text())
		);

		$rand = mt_rand(0,2);

		$vars['next_tool_link'] = $nt[$rand][0];
		$vars['next_tool_text'] = $nt[$rand][1];
		return EasyTemplate::html('firstEdit.tmpl.php', $vars);
	}

	private static function getExpertiseModal() {
		return EasyTemplate::html('expertise.tmpl.php');
	}

	private static function getExpertiseModal2($cat) {
		//Not showing suggested articles any more
		// $dbr = wfGetDB(DB_REPLICA);

		// $sql = "SELECT cl_sortkey, page_id, page_title, page_namespace, page_is_featured
			// FROM (page, categorylinks )
			// LEFT JOIN newarticlepatrol
				// ON nap_page = page_id
			// WHERE
				// cl_from = page_id
				// AND cl_to = " . $dbr->addQuotes($cat) . "
				// AND page_namespace != " . NS_CATEGORY . "
				// AND (nap_demote = 0 OR nap_demote IS NULL)
				// AND page_is_featured = 0
			// GROUP BY page_id
			// ORDER BY page_is_featured DESC, cl_sortkey
			// LIMIT 3";

		// $res = $dbr->query($sql, __METHOD__);
		// foreach ($res as $row) {
			// $article = str_replace('-',' ',$row->page_title);
			// $t = Title::newFromText($article);
			// if ($t && $t->exists()) {
				// $data = FeaturedArticles::featuredArticlesAttrs($t, $t->getText(), 100, 100);
				// if (strlen($data['text']) > 35) $data['text'] = substr($data['text'],0,32) . '...';
				// $boxes[] = $data;
			// }
		// }
		// $vars['arts'] = $boxes;

		$vars['cat'] = str_replace('-',' ',$cat);
		return EasyTemplate::html('expertise_2.tmpl.php',$vars);
	}

	private static function getHelpfulnessModal() {
		return EasyTemplate::html('helpfulness_followup.tmpl.php');
	}

	private static function getHelpfulnessModal2($pageid) {
		if (!$pageid) return;

		$rev = Revision::newFromPageId( $pageid );
		if (!$rev) return;

		$content = $rev->getContent( Revision::RAW );
		$text = ContentHandler::getContentText( $content );
		if (!$text) return;

		$title_name = wfMessage('howto',$rev->getTitle());

		//get methods
		preg_match_all('/===([^=]+)===/', $text, $m);

		$html = '';
		foreach($m[1] as $key => $meth) {
			$html .= '<li><input type="checkbox" id="hfm_'.$key.'" class="hfu_checkbox" /> <label for="hfm_'.$key.'">'.$meth.'</label></li>';
		}

		return json_encode(array('html' => $html, 'title' => wfMessage('helpfulness_followup_txt2',$title_name)->text()));
	}

	private static function getPrintViewModal() {
		return EasyTemplate::html('printview.tmpl.php');
	}

	private static function getFlagAsDetailsModal() {
		return EasyTemplate::html('flag_as_details.tmpl.php');
	}

	private static function getDiscussTabModal($aid, $already_rated) {
		if ($already_rated)
			$content = wfMessage('ratearticle_notrated_details_headline')->text();
		else
			$content = RatingArticle::getDesktopModalForm( $aid );

		$vars = [
			'header' => wfMessage('discuss_tab_hdr')->text(),
			'content' => $content,
			'modal_close' => wfMessage('modal_close')->text()
		];

		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/discuss_tab' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);
		$html = $m->render('discuss_tab.mustache', $vars);
		return $html;
	}

}
