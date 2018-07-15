<?php
class QAMaintenance {

	public function processScheduled() {
		$qadb = QADB::newInstance();
		$docs = $qadb->getImportDocs(QAImportDoc::STATUS_NEW);

		foreach ($docs as $doc) {
			$qadb->updateImportDoc($doc, QAImportDoc::STATUS_PENDING);

			$importer = new QAImporter();
			$importer->import($doc->getUrl());

			$qadb->updateImportDoc($doc, QAImportDoc::STATUS_COMPLETE);
		}
	}
}
