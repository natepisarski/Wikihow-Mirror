<?php

class AdminTrustedSources extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'AdminTrustedSources');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		//staff only
		if ( !in_array( 'staff', $user->getGroups() ) ) {
			throw new UserBlockedError( $user->getBlock() );
			return;
		}

		$req = $this->getRequest();

		if ($req->wasPosted()) {
			set_time_limit(0);
			$out->setArticleBodyOnly(true);
			$error = "";
			$action = $req->getVal('action');
			if ($action == 'ats_import') {
				$filename = $req->getFileTempName('ats_file');
				$ret = $this->importNewSources($filename);
				print json_encode( ['results' => $ret] );
			} elseif ($action == 'retrieve-list') {
				$this->downloadCategories();
			} elseif ($action =='ats_download') {
				$out->setArticleBodyOnly(true);
				$this->getAllSourcesFile();
				return;
			} elseif( $action == 'ats_delete') {
				$out->setArticleBodyOnly(true);
				$ids = array_map('intval', json_decode($req->getVal('ids')));
				TrustedSources::deleteSources($ids);
				echo json_encode(['success' => true, 'ids' => $ids]);
				return;
			} else {
				$error = 'unknown action';
			}
			if ($error) {
				print json_encode(array('error' => $error));
			}
			return;
		}


		//default display
		$out->addModuleStyles('ext.wikihow.admin_trusted_sources.styles');
		$out->addModules('ext.wikihow.admin_trusted_sources');
		$out->setPageTitle(wfMessage('admin_trusted_sources')->text());
		$out->addHTML($this->getBody());
	}

	/**
	 * getBody()
	 *
	 * @return HTML
	 */
	public function getBody() {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);

		$vars = [
			'total_sources' => TrustedSources::getTotalTrustedSources(),
			'sources' => TrustedSources::getAllSources()
		];

		$m = new Mustache_Engine($options);
		$html = $m->render('admin.mustache', $vars);

		return $html;
	}

	private function getAllSourcesFile() {
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="trusted_sources' . date('Ymd') . '.tsv"');

		$headers = ["source name", "source url", "source description"];
		print join("\t", $headers) . "\n";

		$sources = TrustedSources::getAllSources();
		foreach($sources as $source) {
			print $source['ts_name'] . "\t" . $source['ts_source'] . "\t" . $source['ts_description'] . "\n";
		}
	}

	private function importNewSources($filename) {
		$content = file_get_contents($filename);
		if ($content === false) {
			$error = 'internal error opening uploaded file';
			return array('error' => $error);
		}

		$lines = preg_split('@(\r|\n|\r\n)@m', $content);
		$rows = [];

		foreach ($lines as $line) {
			$fields = explode("\t", $line);
			if($fields[0] == "Name") {
				continue;
			}
			$fields = array_map('trim', $fields);
			if(count($fields) < 3) continue;
			$rows[] = ['ts_name' => $fields[0], 'ts_source' => $fields[1], 'ts_description' => $fields[2]];
		}

		TrustedSources::addSources($rows);

		return $rows;
	}

}
