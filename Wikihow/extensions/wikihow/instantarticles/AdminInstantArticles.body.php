<?php

class AdminInstantArticles extends UnlistedSpecialPage {

    public function __construct() {
        $this->specialpage = $this->getContext()->getTitle()->getPartialUrl();
        parent::__construct($this->specialpage);
    }

    public function execute( $subPage ) {
		global $wgDebugToolbar, $IP;

		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userGroups = $user->getGroups();

		if ( $user->isBlocked() || !in_array( 'staff', $userGroups ) ) {
			$out->setRobotpolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( !$request->wasPosted() ) {
			$this->outputAdminPageHtml(); 
			return;
		}

		$result = array();

		ini_set('memory_limit', '512M');
		set_time_limit(0);

		$out->setArticleBodyOnly(true);

		$context = $this->getContext();

		if ( $request->getVal( 'action' ) == "ed_create" ) {
			$result['data'] = $this->importArticles( $context );
		}

		if ($wgDebugToolbar) {
			WikihowSkinHelper::maybeAddDebugToolbar($out);
			$info =  MWDebug::getDebugInfo($this->getContext());
			$result['debug']['log'] = $info['log'];
			$result['debug']['queries'] = $info['queries'];
		}

		echo json_encode($result);
    }

    public function getTemplateHtml( $templateName, $vars = array() ) {
        global $IP;
        $path = "$IP/extensions/wikihow/instantarticles";
        EasyTemplate::set_path( $path );
        return EasyTemplate::html( $templateName, $vars );
    }

    function outputAdminPageHtml() {
		$out = $this->getOutput();

        $out->setPageTitle( "Instant Articles Creator" );
		$out->addModules( 'ext.wikihow.admininstantarticles' );
        $out->addHtml( $this->getTemplateHtml( 'AdminInstantArticles.tmpl.php' ) );
    }


	public static function getSubmittedQuestions( $title, $approved, $limit ) {
		$dbr = wfGetDB(DB_SLAVE);
		$table =  QADB::TABLE_SUBMITTED_QUESTIONS;
		$vars = array('qs_question');
		$conds = [
			'qs_article_id' => $title->getArticleID(),
			'qs_ignore' => 0,
			'qs_curated' => 0,
			'qs_proposed' => 0,
			'qs_approved' => $approved ? 1 : 0
		];

		$options = [ 'ORDER BY' => 'qs_submitted_timestamp', 'LIMIT' => $limit ];
		$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options );
		return $res;
	}

	private static function getQAHtml( $title ) {
		$doc = phpQuery::newDocument();

		$qadb = QADB::newInstance();
		// get approved first
		$approvedResults = array();
		$approved = true;
		$limit = 15;
		$res = self::getSubmittedQuestions( $title, $approved, $limit );
		foreach ( $res as $row ) {
			$approvedResults[] = $row->qs_question;
		}

		// if we got fewer than 15 results, fill them with unapproved submitted questions
		$n = count( $approvedResults );
		if ( $n < 15 ) {
			$approved = false;
			$limit = 15 - $n;
			$res = self::getSubmittedQuestions( $title, $approved, $limit );
			foreach ( $res as $row ) {
				$approvedResults[] = $row->qs_question;
			}
		}

		pq('')->prepend('<div id="results"></div>');

		// give it a nice h2 header
		pq('#results')->html('<h2><span class="mw-headline">Unanswered Questions</span></h2>');
		pq('#results')->append('<ul id="approved"></ul>');
		foreach ( $approvedResults as $txt ) {
			pq('#approved')->append("<li>".$txt."</li>");
		}

		$html = $doc->htmlOuter();

		return $html;
	}

	private function processHTML( $body, $title = null ) {

		// first get the QA section html (which is optional for IA)
		$qa = "";
		if ( $title ) {
			$qa = self::getQAHtml( $title );
		}

		// this is required so php query knows about the document
		$doc = phpQuery::newDocument( $body );
		pq('.section.steps:last')->after($qa);

		// optionally put all our resulting html into this instant div
		// and take it out later
		//pq('')->prepend('<div id="instant"></div>');

		// loop through all the main images and change their size and source
		foreach (pq('.mwimg') as $node) {
			$pqNode = pq($node);
			$src = $pqNode->find('img')->attr('src');
			if ( $pqNode->nextAll('.step')->find('.whvid_gif')->length > 0 ) {
				$src = $pqNode->nextAll('.step')->find('.whvid_gif')->attr('data-src');
			}
			$pqNode->find('img')->attr('src', "http://pad1.whstatic.com".$src );
		}

		// TODO just remove and change things as needed

		pq('.mwimg')->after('<br>');

		pq('.m-video')->remove();
		pq('.relatedwikihows')->remove();
		pq('.altblock')->remove();
		pq('.step_num')->remove();
		pq('.section.video')->remove();
		pq('.section.testyourknowledge')->remove();
		pq('.section.sample')->remove();
		pq('.anchor')->remove();
		pq('.clearall')->remove();
		pq('.showsources')->remove();
		pq('#intro')->contentsUnwrap();
		pq('.section_text')->contentsUnwrap();
		pq('.step')->contentsUnwrap();
		pq('.section.steps')->contentsUnwrap();

		$html = $doc->htmlOuter();
		return $html;
	}

	// get the content to put in the google doc
	private function getArticleContent( $title, $article, $output ) {
		// now add the html of the article

		// TODO get the latest good revision
		$revision = Revision::newFromTitle( $title );

		$popts = $output->parserOptions();
		$popts->setTidy( true );
		$popts->setEditSection( false );
		$parserOutput = $output->parse( $revision->getText(), $title, $popts );

		// process the html
		$magic = WikihowArticleHTML::grabTheMagic($revision->getText());

		// gets the desktop version of the html
		$parserOutput = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));

		// now modify that html so it's valid for instant articles
		$parserOutput = $this->processHTML( $parserOutput, $title );

		$result .= $parserOutput;

		return $result;
	}

	public function importArticle( $article, $context ) {
		$title = Misc::getTitleFromText( $article );
		if ( !$title ) {
			return null;
		}

		$data = $this->getArticleContent( $title, $article, $context->getOutput() );
		
		echo $data."\n";

		// figure out how to use facebook api
	}

	public function importArticles( $context ) {
		$request = $context->getRequest();

		$articles = $request->getArray( 'articles' );
		$articles = array_filter( $articles );


		$files = array();
		foreach ( $articles as $article ) {
			$file = $this->importArticle( $article, $context );
			if ( !$file ) {
				$files[] = array(
					"title" => $article,
					"error"=>"Error: cannot make title from ".$article);
			} else {
				$files[] = $file;
			}
		}

		return $files;
	}

}
