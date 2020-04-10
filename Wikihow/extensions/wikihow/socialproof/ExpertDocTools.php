<?php

class ExpertDocTools {
	const DRIVE_ROOT_FOLDER = '0ANxdFk4C7ABLUk9PVA';

	const EXPERT_FEEDBACK_FOLDER = '0B9xdFk4C7ABLakZJdm8zUGFCa1k';
	const EXPERT_FEEDBACK_FOLDER_DEV = '1Cxws3Fy6mTO3MGwrycVf9bTFKKp7mcRX';

	const CPORTAL_FOLDER = '0B66Rhz56bzLHfllfVlJlTzNhRFJGOTNudnpDaFgxMkM5bmtLeUNFYjdxYmd4TUVKd3hIYWc';
	const CPORTAL_FOLDER_DEV = '1l2q77Jb5yVvhyHVcB6f62rnPl-MBIRw_';

	const EXPIRED_ITEMS_FOLDER = '0B0oYgpQLcJkJdnY5Nk95SFFadkk';
	const EXPIRED_ITEMS_FOLDER_DEV = '1_t63cjy9d6i_BaM6YAxsufK2lfE3NSWw';

	// create an expert google doc for use by experts to review
	// params:
	// $article - the name of an article (as a url or just a title (or even a page id will work)
	// $name - the name of a user to create the doc for..it will be used in the title of the doc
	// $context - a context object which is used to get the wikitext output
	public function createExpertDoc( $article, $name, $context, $folderId, $includeImg=False ) {
		$title = Misc::getTitleFromText( $article );
		if ( !$title ) {
			return null;
		}

		$service = GoogleDrive::getService();

		// get the text title of the google doc
		$titleText = $article;
		if ($name) {
			$titleText .= ' - ' . $name;
		}

		$content = $this->getExpertDocContent( $title, $article, $name, $context->getOutput(), $includeImg );
		$fileMeta = new Google_Service_Drive_DriveFile([
			'name' => $titleText,
			'description' => $name,
			'parents' => [ $folderId ],
			'mimeType' => 'application/vnd.google-apps.document'
		]);
		$reqParams = [
			'data' => $content,
			'mimeType' => 'text/html',
			'uploadType' => 'multipart',
			'fields' => 'id,name,description,webViewLink'
		];
		$file = $service->files->create($fileMeta, $reqParams);

		$perm = new Google_Service_Drive_Permission([
			'type' => 'anyone',
			'role' => 'commenter',
		]);
		$res = $service->permissions->create($file->id, $perm);

		return $file;
	}

	/*
	 * Find items older than 6 months from a list of folders, then:
	 * remove the anyoneWithLink permission
	 * move the file to a new folder
	 */
	public function fixPermissions($maxResults = 100) {
		global $wgIsDevServer;

		$service = GoogleDrive::getService();

		$oldParents = array(
			$wgIsDevServer ? self::CPORTAL_FOLDER_DEV : self::CPORTAL_FOLDER,
			$wgIsDevServer ? self::EXPERT_FEEDBACK_FOLDER_DEV : self::EXPERT_FEEDBACK_FOLDER,
		);

		$newParent = $wgIsDevServer
			? self::EXPIRED_ITEMS_FOLDER_DEV
			: self::EXPIRED_ITEMS_FOLDER;

		$datetime = date( DateTime::RFC3339, strtotime( "-6 months" ) );

		$processedCount = 0;
		foreach ( $oldParents as $oldParent ) {
			if ( $processedCount >= $maxResults ) {
				break;
			}

			$parameters = [ 'q' => "('$oldParent' in parents) and modifiedTime < '$datetime'" ];
			decho("searching with q", $parameters['q']);
			$fileList = $service->files->listFiles( $parameters );
			$emptyMeta = new Google_Service_Drive_DriveFile();
			$reqParams = [ 'addParents' => $newParent, 'removeParents' => $oldParent ];
			$permsToRemove = [ 'anyone', 'anyoneWithLink' ];

			foreach( $fileList->getFiles() as $file ) {
				echo "id: {$file->id}\tname: {$file->name}\n";
				foreach ($permsToRemove as $perm) {
					try {
						$service->permissions->delete( $file->id, $perm );
					} catch (Google_Service_Exception $e) {
						// ^ raised when trying to remove a permission that the file does not have
					}
				}
				$service->files->update($file->id, $emptyMeta, $reqParams);
				$processedCount++;
			}
		}

		return $processedCount;
	}

	// get the content to put in the google doc
	private function getExpertDocContent( $title, $article, $name, $output, $includeImg=False ) {
		// add the first line of text which is the article name and users name
		$result = $this->getContentFirstLines( $title, $article, $name );

		// now add the html of the article
		$revision = Revision::newFromTitle( $title );
		$popts = $output->parserOptions();
		$popts->setTidy( true );
		$popts->setEditSection( false );
		$parserOutput = $output->parse( ContentHandler::getContentText( $revision->getContent() ), $title, $popts );

		// process the html

		$magic = WikihowArticleHTML::grabTheMagic(ContentHandler::getContentText( $revision->getContent() ));
		$parserOutput = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic));
		$parserOutput = $this->processHTML( $parserOutput, $title, $includeImg );

		$result .= $parserOutput;

		$result .= $this->getContentLastLines( $article, $name );

		return $result;
	}

	// get the beginning lines of the google doc
	private function getContentFirstLines( $title, $article, $name ) {
		$result = "";

		$url = Misc::makeUrl( $title );
		$titleLink = Html::rawElement( 'a', array( 'href'=>$url ), $article );

		$result .= $titleLink;

		if ( $name ) {
			$result .= " - " . $name;
		}
		$result .= "<br><br>";

		if ( $name ) {
			$result .= "Hi, ".$name."! ";
		} else {
			$result .= "Hi! ";
		}

		$instructions = wfMessage('expert_doc_instructions', $name, $article )->text();
		$result .= $instructions;
		$result .= "<h2>Introduction</h2>";

		return $result;
	}

	// get the ending lines of the google doc
	private function getContentLastLines( $article, $name ) {
		$result = wfMessage('expert_doc_instructions_bottom', $name, $article )->text();
		return $result;
	}

	private function processHTML( $body, $title = null, $includeImg=False ) {

		$qa = "";
		if ( $title ) {
			$qa = self::getQAHtml( $title );
		}

		$doc = phpQuery::newDocument( $body );
		pq('.section.steps:last')->after($qa);

		if ( $includeImg == false ) {
			pq('.mwimg')->remove();
		} else {
			foreach (pq('.mwimg') as $node) {
				$pqNode = pq($node);
				$src = $pqNode->find('img')->attr('src');
				if ( $pqNode->nextAll('.step')->find('.whvid_gif')->length > 0 ) {
					$src = $pqNode->nextAll('.step')->find('.whvid_gif')->attr('data-src');
				}
				$pqNode->find('img')->attr('src', "http://pad1.whstatic.com".$src );
				$pqNode->find('img')->attr('width', 364);
				$pqNode->find('img')->attr('height', 273);
			}
			pq('.mwimg')->after('<br>');
		}

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
		pq('.stepanchor')->remove();

		$html = $doc->htmlOuter();
		return $html;
	}

	/**
	 * Methods that were copied from AdminInstantArticles.body.php
	 * TODO: remove these duplicated methods
	 */

	private static function getSubmittedQuestions( $title, $approved, $limit ) {
		$dbr = wfGetDB(DB_REPLICA);
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

}
