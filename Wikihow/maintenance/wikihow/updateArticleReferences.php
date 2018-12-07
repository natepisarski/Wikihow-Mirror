<?php

require_once __DIR__ . '/../Maintenance.php';

/*
contains the title and other info for an external link
   CREATE TABLE `link_info` (
   `li_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
   `li_url` varchar(255) NOT NULL,
   `li_title` varchar(255) NOT NULL,
   `li_code` int(10) unsigned NOT NULL,
   `li_date_checked` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `li_user_checked` int(10) unsigned NOT NULL,
   PRIMARY KEY (`li_id`),
   KEY (`li_url`)
   );

used to keep track of which externallinks entries we have fetcheD data for

   CREATE TABLE `externallinks_link_info` (
   `eli_el_id` int(10) unsigned NOT NULL,
   `eli_li_id` int(10) unsigned NOT NULL,
   PRIMARY KEY (`eli_el_id`),
   KEY (`eli_el_id`, `eli_li_id`)
   );

// query to get a csv list of all 4xx,4xx urls and their pages
   SELECT CONCAT('https://www.wikihow.com/', page_title), el_from, el_to FROM `externallinks`,`page` WHERE (el_from = page_id) AND (el_id IN (select eli_el_id from externallinks_link_info, link_info where eli_li_id = li_id and li_code >= 400)) INTO OUTFILE '/tmp/aaron.out' FIELDS TERMINATED BY ',' LINES TERMINATED BY '\n';

for a given page what external references exist NOT USED
   CREATE TABLE `page_reference_info` (
   `pri_page_id` int(10) unsigned NOT NULL,
   `pri_el_id` int(10) unsigned NOT NULL,
   `pri_eli_id` int(10) unsigned NOT NULL,
   );
 */
class updateArticleReferences extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "update article references";
		$this->addOption( 'title', 'title of page', false, true, 't' );
    }

	private static function get_pdf_prop($file) {
		$f = fopen($file,'rb');
		if(!$f)
			return false;

		//Read the last 17KB
		fseek($f, -16384, SEEK_END);
		$s = fread($f, 16384);

		//Extract cross-reference table and trailer
		if(!preg_match("/xref[\r\n]+(.*)trailer(.*)startxref/s", $s, $a))
			return false;
		$xref = $a[1];
		$trailer = $a[2];

		//Extract Info object number
		if(!preg_match('/Info ([0-9]+) /', $trailer, $a))
			return false;
		$object_no = $a[1];

		//Extract Info object offset
		$lines = preg_split("/[\r\n]+/", $xref);
		$line = $lines[1 + $object_no];
		$offset = (int)$line;
		if($offset == 0)
			return false;

		//Read Info object
		fseek($f, $offset, SEEK_SET);
		$s = fread($f, 1024);
		fclose($f);

		//Extract properties
		if(!preg_match('/<<(.*)>>/Us', $s, $a))
			return false;
		$n = preg_match_all('|/([a-z]+) ?\((.*)\)|Ui', $a[1], $a);
		$prop = array();
		for($i=0; $i<$n; $i++)
			$prop[$a[1][$i]] = $a[2][$i];
		return $prop;
	}

	private static function savePDF( $fileUrl ) {
		//The path & filename to save to.
		$saveTo = '/tmp/ref.pdf';

		//Open file handler.
		$fp = fopen($saveTo, 'w+');

		//If $fp is FALSE, something went wrong.
		if($fp === false){
			throw new Exception('Could not open: ' . $saveTo);
		}

		//Create a cURL handle.
		$ch = curl_init($fileUrl);

		//Pass our file handle to cURL.
		curl_setopt($ch, CURLOPT_FILE, $fp);

		//Timeout if the file doesn't download after 20 seconds.
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);

		//Execute the request.
		curl_exec($ch);

		//If there was an error, throw an Exception
		if(curl_errno($ch)){
			throw new Exception(curl_error($ch));
		}

		//Get the HTTP status code.
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		//Close the cURL handler.
		curl_close($ch);

		if($statusCode == 200){
			decho('Downloaded!', $fileUrl);
			return true;
		} else{
			decho("Status Code: " . $statusCode, $fileUrl);
			return false;
		}

	}

	private static function getPDFTitle( $url, $code, $data ) {
		$result = ['code' => $code, 'text' => ''];
		$parsedUrl = parse_url( $url );
		$path = $parsedUrl['path'];
		$pathArray = explode( '/', $path );
		$pdfTitle = $path;
		if ( $pathArray ) {
			$pdfTitle = end( $pathArray );
		}
		$result['text'] = $pdfTitle;
		if ( $code >= 300 ) {
			return $result;
		}

		// do not try to download pdf anymore
		//$downloaded = self::savePDF( $url );
		$downloaded = null;
		if ( !$downloaded ) {
			return $result;
		}
		/*
		$res = self::get_pdf_prop( '/tmp/ref.pdf' );
		decho("res", $res);
		if ($res) {
			decho("found pdf", $url);
			decho("pdf file", $pdfTitle);
			decho("found res", $res);
		}
		return $result;
		*/
	}
	/*
	* get data about this url
	* @return array with keys text, code
	*/
	private static function getRemoteInfo( $url ) {
		$result = ['code' => 0, 'text' => ''];
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_HEADER, 0);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt( $ch, CURLOPT_URL, $url);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $ch, CURLOPT_TIMEOUT, 11);

		$data = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$result['code'] = $httpCode;

		// if this is a pdf we have to get the title in another way
		if ( strtolower( substr( $url, -4 ) ) == '.pdf' ) {
			$result = self::getPDFTitle( $url, $httpCode, $data );
			return $result;
		}

		$doc = new DOMDocument();
		@$doc->loadHTML($data);
		$nodes = $doc->getElementsByTagName('title');

		//get and display what you need:
		if ( !$nodes ) {
			//decho("no nodes for", $url);
		} else if ( !$nodes->item(0) ) {
			//decho("no items for url $url for node", $nodes);
		} else {
			$title = $nodes->item(0)->nodeValue;
			$result['text'] = $title;
		}
		return $result;

		//$metas = $doc->getElementsByTagName('meta');

		/*
		for ( $i = 0; $i < $metas->length; $i++ ) {
			$meta = $metas->item($i);
			if($meta->getAttribute('name') == 'description')
				$description = $meta->getAttribute('content');
			if($meta->getAttribute('name') == 'keywords')
				$keywords = $meta->getAttribute('content');
		}
		*/

		//echo "Title: $title". '<br/><br/>';
		//echo "Description: $description". '<br/><br/>';
		//echo "Keywords: $keywords";
	}


	public static function getLatestGoodRevisionText( $title ) {
		$gr = GoodRevision::newFromTitle( $title );
		if ( !$gr ) {
			return "";
		}

		$latestGood = $gr->latestGood();
		if ( !$latestGood ) {
			return "";
		}
		$r = Revision::newFromId( $latestGood );
		if ( !$r ) {
			return "";
		}
		return $r->getText();
	}

	private static function getUrlList( $pageId ) {
		$urlList = array();

		$dbr = wfGetDb( DB_SLAVE );
        $table = 'externallinks';
        $var = 'el_to';
		$cond = array( 'el_from' => $pageId );
		$options = array();
		$res = $dbr->select( $table, $var, $cond, __METHOD__, $options );
		foreach( $res as $row ) {
			$urlList[] = $row->el_to;
		}
		return $urlList;
	}

	private function updateReferences( $title, $forceUpdate ) {
		$pageId = $title->getArticleID();
		$urlList = self::getUrlList( $pageId );

		foreach ( $urlList as $url ) {
			$text = WikihowArticleHTML::getLinkInfo( $url );
			if ( !$text || $forceUpdate ) {
				$data = self::getRemoteInfo( $url );
				if ( $text ) {
					self::insertLinkInfo( $url, $data );
				}
			}
		}
	}
	private function processItem( $url, $externalLinkId ) {
		$forceUpdate = false;

		$linkInfoId = self::getLinkInfoId( $url );
		//if ( $linkInfoId ) {
			//decho( "found link info for url $url with id", $linkInfoId );
		//}
		if ( !$linkInfoId || $forceUpdate ) {
			$data = self::getRemoteInfo( $url );
			if ( $data ) {
				$linkInfoId = self::insertLinkInfo( $url, $data );
			}
		}
		// update our join table so we know we have processed this row
		$this->addExternalLinkInfoItem( $externalLinkId, $linkInfoId );
	}

	private function processItems() {
		$dbr = wfGetDb( DB_SLAVE );
        $table = array(
			'externallinks',
			'page'
		);
        $var = array(
			'el_to',
			'el_id'
		);
		$cond = array(
			'el_from = page_id',
			'el_id NOT IN (select eli_el_id from externallinks_link_info)',
			'page_namespace' => 0,
		);
		$options = array( 'LIMIT' => 100000 );
		$res = $dbr->select( $table, $var, $cond, __METHOD__, $options );
		foreach ( $res as $row ) {
			$this->processItem( $row->el_to, $row->el_id );
		}
	}

	private static function addExternalLinkInfoItem( $externalLinkId, $linkInfoId ) {
		$dbw = wfGetDB( DB_MASTER );
        $table = 'externallinks_link_info';
        $values = array(
            'eli_el_id' => $externalLinkId,
            'eli_li_id' => $linkInfoId,
        );
		$options = array( 'IGNORE' );
        $dbw->insert( $table, $values, __METHOD__ );

		return;
	}

	// look for some bad titles, and make them blank instead
	// we can then update entries with blank titles later
	private static function processTitleText( $title ) {
		$result = trim( $title );
		$badTitles = array(
			'Access Denied',
			'404' ,
		);
		if ( in_array( $result ,$badTitles ) ) {
			$result = '';
		}
		return $result;
	}

	private static function insertLinkInfo( $url, $data ) {
		$title = self::processTitleText( $data['text'] );
		$code = $data['code'];

		$dbw = wfGetDB( DB_MASTER );
        $table = 'link_info';
        $values = array(
            'li_url' => $url,
            'li_title' => $title,
            'li_code' => $code,
        );
		$options = array( 'IGNORE' );
        $dbw->insert( $table, $values, __METHOD__ );

		return $dbw->insertId();
	}

	private static function getLinkInfoId( $url ) {
		$dbr = wfGetDb( DB_SLAVE );
        $table = 'link_info';
        $var = 'li_id';
		$cond = array( 'li_url' => $url );
		$options = array();
		$id = $dbr->selectField( $table, $var, $cond, __METHOD__, $options );
		return $id;
	}
	private function processTitle( $title, $forceUpdate = false ) {
		global $wgTitle;
		if ( !$title ) {
			return;
		}
		$this->updateReferences( $title, $forceUpdate );
	}

	private function updateAll() {
		$this->processItems();
	}

	public function execute() {
		global $wgTitle;

		$checkAll = true;

		$title = $this->getOption( 'title' );
		if ( $title ) {
			$checkAll = false;
		}

		if ( $checkAll == true ) {
			// all not allowed yet
			$this->updateAll();
		}

		$title = Misc::getTitleFromText( $title );
		$forceUpdate = true;
		$this->processTitle( $title, $forceUpdate );
	}
}


$maintClass = "updateArticleReferences";
require_once RUN_MAINTENANCE_IF_MAIN;

