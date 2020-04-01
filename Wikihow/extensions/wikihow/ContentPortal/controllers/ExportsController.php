<?php
namespace ContentPortal;
use __;
use mnshankar\CSV\CSV;

class ExportsController extends AppController {

	public $adminOnly = ['*'];

	function beforeRender() {
		parent::beforeRender();
		$this->today        = Export::today();
		$this->exportFields = ArticleCSV::$csvFields;
		$this->exportTypes  = Export::getExportTypes();
	}

	function index() {}

	function download() {
		$params = $this->params('export');

		if ($params['type'] == 'all')
			$data = ArticleCSV::getAll($params['fields']);
		elseif ($params['type'] == 'date-range')
			$data = ArticleCSV::byRoleAndDateRange($params['key'], $params['range'], $params['fields']);
		elseif ($params['type'] == 'urls')
			$data = ArticleCSV::byUrls($this->parseUrls($params['urls']), $params['fields']);
		else
			$data = ArticleCSV::byRole($params['key'], $params['fields']);

		if (empty($data)) {
			setFlash('There were no articles matching your search', 'danger');
			$this->render('exports/index');

		} else {
			$this->renderCSV($data, $this->getFileName());
		}
	}

	function users() {
		$this->renderCSV(UserCSV::userStats(UserCSV::$csvFields), 'user_stats');
	}

	function dump() {
		$dump = Export::lastDump();

		if (!$dump) {
			setFlash('There are no CSV exports saved at this time.', 'danger');
			$this->redirectTo('');
			return;
		}

		$name = __::last(explode('/', $dump));
		header('Content-Type: application/csv');
		header("Content-Disposition: attachment; filename=$name");
		header('Pragma: no-cache');

		echo file_get_contents($dump);
		$this->continue = false;
	}

	function testMail() {
		$data = [
			['article' => Article::first(), 'title' => \Title::newFromText('Dummy')],
			['article' => Article::last(), 'title' => \Title::newFromText('Hey there...')]
		];

		$this->results = [
			'deletes' => $data, 'moves' => $data, 'redirects' => $data
		];
		$this->render('mail/nightly', false);
	}

	private function getFileName() {
		$param = $this->params('export');

		if ($param['type'] == 'all') {
			return "All Articles " . Export::today();

		} elseif ($param['type'] == 'date-range') {
			$role = Role::find_by_key($param['key']);
			return "{$role->past_tense} {$param['range']}";

		} elseif ($param['type'] == 'urls') {
			return "Articles matching urls" . Export::today();
		} else {
			$role = Role::find_by_key($param['key']);
			return "All articles in {$role->present_tense} " . Export::today();
		}
	}

	private function parseUrls($urls) {
		return is_array($urls) ? $urls : explode("\n", $urls);
	}

}
