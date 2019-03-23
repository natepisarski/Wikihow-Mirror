<?php

require_once __DIR__ . '/../../Maintenance.php';

class ConvertOldGreenBoxes extends Maintenance {

	const LOG = '/var/log/wikihow/green_box_conversion.log';
	const ERROR_LOG = '/var/log/wikihow/green_box_errors.log';
	const GREEN_BOX_CONVERT_LOG_MSG = 'Green boxes converted to new greenbox template';

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Convert green box divs in articles to our new green box template";
		$this->addOption( 'pageid', 'Page id to convert', false, true, 'p' );
		$this->addOption( 'limit', 'Limit of articles to convert', false, true, 'l' );
		$this->addOption( 'nosave', 'Run the script without doing the page updates', false, false, 'n' );
	}

	public function execute() {
		global $wgUser;
		$wgUser = User::newFromName('MiscBot');

		$where = [
			'page_namespace' => NS_MAIN,
			'page_is_redirect' => 0
		];

		if ($this->hasOption('pageid')) {
			$where['page_id'] = $this->getOption('pageid');
		}

		$options = ['ORDER BY' => 'page_id'];

		if ($this->hasOption('limit')) {
			$options['LIMIT'] = $this->getOption('limit');
		}

		$rows = DatabaseHelper::batchSelect(
			'page',
			[
				'page_id',
				'page_title'
			],
			$where,
			__METHOD__,
			$options
		);

		$count = 0;
		$box_count = 0;

		foreach ($rows as $row) {
			if ($this->convertAnyGreenBoxesByPageId($row->page_id)) {
				$this->logIt($row->page_title);
				$box_count++;
			}
			$count++;
			if ($count % 1000 == 0) usleep(500000);
		}

		if ($this->hasOption('nosave'))
			$result_message = "$box_count green boxes to change.";
		else
			$result_message = "Done. $box_count green boxes converted.";

		print $result_message."\n";
	}

	private function convertAnyGreenBoxesByPageId(int $page_id): bool {
		$title = Title::newFromId($page_id);
		if (empty($title) || !$title->exists()) return false;

		$revision = Revision::newFromTitle($title);
		if (empty($revision)) return false;

		$wikitext = ContentHandler::getContentText($revision->getContent(Revision::RAW));

		$green_tips_boxes = $this->getGreenTipBoxes($wikitext);
		if (empty($green_tips_boxes)) return false;

		$updated_wikitext = $wikitext;
		foreach ($green_tips_boxes as $box) {
			$updated_wikitext = $this->replaceGreenTipsBoxWithTemplate($updated_wikitext, $box);
		}

		if (strcmp($wikitext, $updated_wikitext) !== 0) {
			if (!$this->hasOption('nosave'))
				$result = $this->updatePage($title, $updated_wikitext);
			else
				$result = true;
		}
		else {
			$result = false;
		}

		if (!$result) $this->errorLogIt($title->getDbKey());

		return $result;
	}

	private function getGreenTipBoxes(string $wikitext): array {
		preg_match_all('/(?:<br>)?<div class="green_tips_box".*?<\/div>(?:<br>)*/is', $wikitext, $boxes);
		return !empty($boxes[0]) ? $boxes[0] : [];
	}

	private function replaceGreenTipsBoxWithTemplate(string $wikitext, string $green_tips_box): string {
		$green_box_template = $this->convertGreenTipsBoxToTemplate($green_tips_box);

		if (!empty($green_box_template)) {
			$wikitext = str_replace($green_tips_box, $green_box_template, $wikitext);
		}

		return $wikitext;
	}

	private function convertGreenTipsBoxToTemplate(string $green_tips_box): string {
		//get the guts of the div
		preg_match('/<div.*?>(.*)<\/div>/i', $green_tips_box, $inner);
		$inner = !empty($inner[1]) ? $inner[1] : '';

		//format the guts
		$inner = preg_replace('/<span>\'+(.*?)\'+<\/span>/', '== $1 ==', $inner);
		$inner = preg_replace('/\n/', '<br>', $inner);

		//insert into the template
		$green_box_template = '{{'.GreenBox::GREENBOX_TEMPLATE_PREFIX.$inner.'}}';

		return $green_box_template;
	}

	private function updatePage(Title $title, string $wikitext): bool {
		$content = ContentHandler::makeContent($wikitext, $title);
		$comment = self::GREEN_BOX_CONVERT_LOG_MSG;
		$edit_flags = EDIT_UPDATE | EDIT_MINOR;

		$page = WikiPage::factory($title);
		$status = $page->doEditContent($content, $comment, $edit_flags);

		return $status->isOK();
	}

	private function logIt($page_title) {
		$txt = 'https://www.wikihow.com/'.$page_title;
		print $txt."\n";

		$fh = fopen(self::LOG, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}

	private function errorLogIt($page_title) {
		$txt = 'https://www.wikihow.com/'.$page_title;
		print $txt."\n";

		$fh = fopen(self::ERROR_LOG, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}
}

$maintClass = "ConvertOldGreenBoxes";
require_once RUN_MAINTENANCE_IF_MAIN;
