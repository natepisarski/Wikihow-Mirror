<?php
/**
 * Turn a well formatted wikiHow Article into a facebook instant article
 *
 */

require_once __DIR__ . '/../Maintenance.php';

class InstantArticles extends Maintenance {
	public function __construct() {
		parent::__construct();
        $this->mDescription = "instant articles";
		$required = false;
		$withArg = true;
		$shortName = 't';
		$this->addOption( 'title', 'page title to act on', $required, $withArg, $shortName );
		$shortName = 'i';
		$this->addOption( 'id', 'page title to act on', $required, $withArg, $shortName );
		$shortName = 'p';
		$this->addOption( 'production', 'post article to productio', false, false, $shortName );
		$shortName = 'l';
		$this->addOption( 'live', 'publish article, needs production tag', false, false, $shortName );
	}

	public function execute() {
		$id = $this->getOption( 'title' );
		$title = null;
		if(strlen($this->getOption('id')) > 0){
			$title = Title::newFromID($this->getOption('id'));
		} else{
			$title = Misc::getTitleFromText($id);
		}
		$production = $this->getOption( 'production' );
		$published = $this->getOption( 'live' );


		if ( !$title ) {
			return null;
		}

		$context = RequestContext::getMain();http://hardforcard.com/
		$context->setTitle($title);
        $out = $context->getOutput();
		$data = $this->getArticleContent( $title,$out);
		$this->postArticles($data,$production,$published);
	}

	private function postArticles($fbHtml,$production,$published) {
		$fbApi = new FacebookApiClient();
		$url = $fbApi->buildGraphUrl("/91668358574/instant_articles");
		$access_token=WH_FACEBOOK_INSTANT_ARTICLE_ACCESS_TOKEN;
		$prod = true;
		$publish = false;
		if($production === 1){
			$prod = false;
		}
		if($published === 1){
			$prod = true;
		}
		$post = [
		    'access_token' => $access_token,
		    'html_source' => $fbHtml,
		    'published'   => $publish,
		    'development_mode' => $prod,
		];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec($ch);
		$server_output;
	}

	private function processHTML( $body, $title = null,$revision ) {
		$doc = phpQuery::newDocument( $body );
		pq('script')->remove();
		//put all our resulting html into this instant div
		pq('')->prepend('<div id="instant"></div>');
		$result = pq('#instant');

		$this->formatSteps($result);
		$this->setIntroText($result);
		$this->buildHeader($title,$result,$revision);
		$this->setTips($result);
		$this->setWarnings($result);
		$this->setThingsYoullNeed($result);
		$this->removeExtraTags();
		$this->setReferences($result);
		$this->addAnalytics($result);

		$obj = $result->html();
		$doc = phpQuery::newDocument($this->getTemplateHtml('instantArticle.tmpl.php'));
		pq('article')->append($obj)->html();

		//set link article
		pq('link:first')->attr('href','http://www.wikihow.com/' . $title->getPartialUrl());

		return pq('');
	}

	//embeds google anaylytics code
	private function addAnalytics($result){
		$analytics = "<figure class=\"op-tracker\"><iframe>(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

		// Do the main GA ping
		ga('create', UA-2375655-1, 'auto', { 'allowLinker': true });
		ga('linker:autoLink', [/^.*wikihow\.(com|jp|vn)$/]);
		ga('send', 'pageview');</iframe></figure>";

		$result->append($analytics);
	}

	//removes sup tags and unwraps <a> tags
	private function removeExtraTags(){
		pq('sup')->remove();
		foreach (pq('a') as $bullets) {
			pq($bullets)->contentsUnwrap();
		}
		pq('i')->contentsUnwrap();
		pq('span')->contentsUnwrap();
	}

	//adds Warnings section
	private function setWarnings($result){
		if(pq('#warnings')->length){
			$result->append('<p><strong>Warnings</strong></p>');
			$result->append(pq('#warnings')->html());
			pq('.clearall')->remove();
		}
	}

	//adds tips Section
	private function setTips($result){
		$tips = pq('#tips');
		if($tips->length){
			$result->append('<p><strong>Tips</strong></p>');

			$result->append($this->formmatBullets($tips)->html());
			pq('.clearall')->remove();
		}
	}

	//adds Things You'll Need Section
	private function setThingsYoullNeed($result){
		$things = pq('#thingsyoullneed');
		if($things->length){
			$result = pq('#instant');

			$result->append('<p><strong>Things You\'ll Need</strong></p>');
			//remove Check boxes next to Things
			$things->find('input')->remove();
			$things->find('label')->remove();

			$things->find('div')->contentsUnwrap();
			$things->find('span')->contentsUnwrap();
			$things->find('h3')->wrap('<h2></h2>');
			// echo $things->find('h2');
			$things->find('h3')->contentsUnwrap();
			// echo $things->find('h2');
			// exit;
			$result->append($things->html());
			pq('.clearall')->remove();
		}
	}

	//Adds a element telling User to visit real site for full info
	private function setReferences($result){
		$sources = pq('.references.sources');
		if($sources->length){
			// $result->append('<p><a href="http://www.m.wikihow.com/' . $title->getPartialUrl() . '#info_link">Sources and Citations</a></p>');
			$result->append('<p><Strong>Visit our Full Site to view Sources and Citations</strong></p>');
		}

	}


	//builds header section with title, author, date published, and image
	private function buildHeader($title,$result,$revision){
		$result->prepend('<header></header>');
		$header = $result->find('header');

		//get final image and wrap it in <figure>
		$img = $result->find('img:last');
		$header->append($img->clone());
		$src = $result->find('img:first')->attr('src');

		//$result->find('img:first')->attr('src', "http://pad1.whstatic.com".$src);
		$result->find('img:first')->wrap("<figure data-mode=aspect-fit></figure>");

		//adds title, intro paragraph, and date created
		$header->append('<h1>How to ' . $title . '</h1>');

		$header->append('<time class="op-published">' . (date_create( $revision->getTimestamp()))->format( 'Y-m-d' ) . '</time>');
	}

	//get's the intro text and adds it to result
	private function setIntroText($result){

		//wraps bolded text in <strong> tag which is how facebook bolds
		foreach (pq('b') as $bullets) {
			pq($bullets)->wrap('<strong></strong>');
			pq($bullets)->contentsUnwrap();
		}

		$intro = pq('#intro');
		//remove extra 'user review' text from intro
		$intro->find('.sp_intro_user')->remove();

		//gets rid of unwanted tags
		$intro->find('p')->contentsUnwrap();
		$intro->find('a')->contentsUnwrap();

		$picNode = $this->formatPics($intro->find('.mwimg'));

		$picNode = $intro->find('figure')->remove();
		$intro->find('div')->remove();

		//add text and picture to beginning of result divs
		$result->prepend('<p>' . $intro->html() . '</p>');
		$result->prepend($picNode);
	}

	//gets Picture from mwimg div and wraps in Facebooks Figure Tag
	private function formatPics($picNode){
		$img = $picNode->find('noscript')->find('img');
		$src = $img->attr('src');

		if ( $picNode->nextAll('.step')->find('.whvid_gif')->length > 0 ) {
			$src = $picNode->nextAll('.step')->find('.whvid_gif')->attr('data-src');
		}
		//gets img from noscript tag.
		$img->attr('src', $src );
		$img->wrap("<figure data-mode=aspect-fit></figure>");
		return $picNode;
	}


	private function formatSteps($result){
		foreach (pq('.section.steps') as $section) {
			$sectionNode = pq($section);
			//sets the headline
			$result->append('<h1>' . $sectionNode->find('.mw-headline')->text() . '</h1>');

			foreach ($sectionNode->find('.section_text') as $steps){
				$stepNum=1;
				foreach(pq($steps)->find('.steps_list_2 > li') as $node){
					$visualNode = null;
					if(pq($node)->find('.vid-whvid')->length() > 0){
						// print pq($node)->find('.vid-whvid');
						$visualNode = '<figure><video loop = "true"><source src="http://vid1.whstatic.com' . pq($node)->find('.vid-whvid')->text() . '" type="video/mp4" />  </video></figure>';
						pq($node)->find('.vid-whvid')->remove();
						pq($node)->find('.whvid_gif')->remove();
						pq($node)->find('.img-whvid')->remove();
						pq($node)->find('.player-whvid')->remove();
					} else{
						$visualNode = $this->formatPics(pq($node)->children('.mwimg'))->find('figure');
					}

					$stepNode = pq($node)->find('.step');
					$StepText = '<p><strong>' . $stepNum . ". " . $stepNode->find('.whb')->text() . "</strong></p>";
					$stepNode->find('.whb')->remove();

					$bulletPoints = $stepNode->children('ul');
					$bulletPoints = $this->formmatBullets($bulletPoints);
					$bulletPoints->remove();

					$result->append($visualNode);
					$result->append($StepText);
					$result->append('<p>'.$stepNode->text().'</p>');
					$result->append($bulletPoints);

					$stepNum++;
				}
			}
		}
	}

	//formats bullet points by wrapping each li element in a <ul> tag and placing any photo in the bullet after it
	//facebook won't let it happen any other way
	private function formmatBullets($bulletPoints){
		foreach ($bulletPoints->find('li') as $node){
			if(pq($node)->find('ul')->length){

				pq($node)->find('ul')->contentsUnwrap();
				$obj = pq($node)->find('li')->remove();
				pq($node)->after($obj);
				 // exit;
			}
			pq($node)->wrap('<ul></ul>');

			pq($node)->find('strong')->remove();
			foreach(pq($node)->children('.mwimg') as $img){
				$picNode = $this->formatPics(pq($img));
				pq($node)->after($picNode->find('figure'));
				pq($img)->remove();
			}
		}
		return $bulletPoints->children('');
	}

	// get the content to put in the google doc
	public function getArticleContent( $title, $output ) {
		// now add the html of the article
		//set global Title as $wgTitle
		global $wgTitle;
		$wgTitle = $title;
		$gr = GoodRevision::newFromTitle( $title );
        $revId = $gr->latestGood();
        $revision = Revision::newFromId( $revId );

		$popts = $output->parserOptions();
		$popts->setTidy( true );
		$popts->setEditSection( false );
		$parserOutput = $output->parse( ContentHandler::getContentText( $revision->getContent() ), $title, $popts );

		// process the html
		$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $revision->getContent() ));

		// gets the desktop version of the html
		$parserOutput = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));

		// now modify that html so it's valid for instant articles
		$parserOutput = $this->processHTML( $parserOutput, $title,$revision );

		return $txt = preg_replace('~\h*\[(?:[^][]+|(?R))*+]\h*~', '', $parserOutput);
	}

	//get's Template HTml
	 public function getTemplateHtml( $templateName, $vars = array() ) {
        EasyTemplate::set_path( __DIR__ );
        return EasyTemplate::html( $templateName, $vars );
    }
}
$maintClass = "InstantArticles";
require_once RUN_MAINTENANCE_IF_MAIN;

