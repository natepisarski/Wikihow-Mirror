<?php

require_once __DIR__ . '/../../Maintenance.php';


class RemoveBadWebsolr extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "delete from websolr pages which are not in page table";
		$this->addOption( 'debug', 'print debug messages', false, false, 'd' );
    }

	function debug( $message, $data ) {
		if ( $this->debug ) {
			decho( $message, $data, $false );
		}
	}

	function logTime(&$time, $message = NULL) {
		if (!$message) {
			$message = "time";
		}
		$time += microtime(true);
		echo "$message: ",sprintf('%f', $time),PHP_EOL;
		$time = -microtime(true);
	}

	public function execute() {
		// this will query for pages with no icon...a good place to start looking for bad data in the websolr index
		// but if there are no results with this field missing, it will still return normal results so we have to check each result
		//$url = "http://index.websolr.com/solr/1040955300c/select?wt=json&rows=1000&q=-image_58x58:*";
		$time = microtime( true );

		$this->debug = $this->getOption( 'debug' );

		// just look at all the rows by giving a number larger than is probably in db
		$num = 300000;
		// get only id from all pages sorted by id asc (in case we want to paginate in the future)
		$queryUrl =	"http://index.websolr.com/solr/1040955300c/select?wt=json&fl=id&rows=$num&sort=id+asc";

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml" ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_URL, $queryUrl );
		$output = curl_exec( $ch );

		if ( curl_errno( $ch ) ) {
			$this->output( 'error:' . curl_error($c) );
			exit(1);
		}

		$output = json_decode( $output );
		$numFound = $output->response->numFound;
		$this->output( "found $numFound ids in websolr index to process\n");
		
		$docs = $output->response->docs ?? [];

		$pageIds = array();
		foreach ( $docs as $doc ) {
			$id = $doc->id;
			// check each result  to see if it's in the page table or not
			$name = Title::nameOf( $id );
			if ( $name == null ) {
				$this->debug( 'null id', $id );
				$pageIds[] = $id;
			}
		}

		// delete the pages which are not in page table
		$postData = '<delete>';
		$count = 0;
		foreach( $pageIds as $pageId ) {
			decho( "deleting id", $pageId, false );
			$postData .= "<id>$pageId</id>";
			$count++;
		}
		$postData .= '</delete>';

		if ( $count > 0 ) {
			$updateUrl = "http://index.websolr.com/solr/1040955300c/update";
			curl_setopt( $ch, CURLOPT_URL, $updateUrl );
			curl_setopt( $ch , CURLOPT_POSTFIELDS , $postData );
			$output = curl_exec( $ch );
			curl_close( $ch );
			decho( "websolr response", $output, false );
		}
		$this->logTime($time, "deleted $count documents in");
	}
}


$maintClass = "RemoveBadWebsolr";
require_once RUN_MAINTENANCE_IF_MAIN;

