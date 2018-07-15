<?php
require_once __DIR__ . '/../Maintenance.php';
/**
 * One-time deletion of inactive questions produced for a test
 */
class QAInactiveDeletion extends Maintenance {


	public function __construct() {
		parent::__construct();
		$this->mDescription = "One-time deletion of inactive questions produced for a test";
	}


	/**
	 * Called command line.
	 */
	public function execute() {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			QADB::TABLE_ARTICLES_QUESTIONS,
			'qa_id',
			[
				'qa_updated_timestamp > ' . wfTimestamp(TS_MW, strtotime('20160109')),
				'qa_updated_timestamp < ' . wfTimestamp(TS_MW, strtotime('20160130')),
				'qa_inactive' => 1
			],
			__METHOD__
		);
		$qadb = QADB::newInstance();
		$aqids = [];
		foreach ($res as $row) {
			$aqids []= $row->qa_id;
		}

		if (empty($aqids)) {
			echo "no articles found\n";
			return;
		}

		$aqs = $qadb->getArticleQuestionsByArticleQuestionIds($aqids, true);
//		foreach ($aqs as $aq) {
//			$row = [
//				$aq->getId(),
//				$aq->getCuratedQuestion()->getText(),
//				$aq->getCuratedQuestion()->getCuratedAnswer()->getText()
//			];
//			echo  DataUtil::arrayToDelimitedLine($row) . "\n";
//		}

		foreach ($aqs as $aq) {
			$data = [
				'aqid' => $aq->getId(),
				'cqid' => $aq->getCuratedQuestion()->getId(),
				'sqid' => $aq->getCuratedQuestion()->getSubmittedId(),
				'caid' => $aq->getCuratedQuestion()->getCuratedAnswer()->getId(),
			];
			$res = $qadb->deleteArticleQuestion($data);
			if (!$res->getSuccess()) {
				var_dump($res->getMsg(),$data);
			}

		}

	}
}

$maintClass = "QAInactiveDeletion";
require_once RUN_MAINTENANCE_IF_MAIN;
