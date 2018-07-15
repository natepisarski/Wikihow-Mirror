<?php
class QADataFile {
	public static function getColumnNames() {
		return [
			"Article id",
			"Article url",
			"Original question",
			"Rewritten question (may be empty)",
			"Answer (may be empty)",
			"Order priority (1=high, 5=medium, 10=low)",
			"Ignore in future exports (1 to ignore, blank otherwise)",
			"Inactive (1 if inactive, blank otherwise)",
			"Original question id (don't touch)",
			"Article question id, if any (don't touch)",
			"Rewritten question id, if any (don't touch)",
			"Answer id, if any (don't touch)"
		];
	}
}