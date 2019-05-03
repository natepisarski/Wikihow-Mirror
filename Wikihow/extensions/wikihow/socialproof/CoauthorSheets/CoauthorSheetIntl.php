<?php

class CoauthorSheetIntl extends CoauthorSheet
{
	private $enBlurbs;
	private $enCoauthors;
	private $enArticles;

	public function doImport() {
		global $wgActiveLanguages;

		$this->enBlurbs = VerifyData::getAllBlurbsFromDB();
		$this->enCoauthors = VerifyData::getAllVerifierInfoFromDB();
		$this->enArticles = VerifyData::getAllArticlesFromDB();
		$apiToken = self::getApiAccessToken();
		$stats = [ 'imported'=>[], 'errors'=>[], 'warnings'=>[] ];

		foreach ($wgActiveLanguages as $lang) {
			list($translations, $errors, $warnings) = $this->fetchBlurbTranslationsFromSheet($lang, $apiToken);
			$stats['errors'] = array_merge( $stats['errors'], $errors );
			$stats['warnings'] = array_merge( $stats['warnings'], $warnings );
			if ( !$errors ) {
				$intlBlurbs = $this->updateIntlBlurbs( $lang, $translations );
				$intlCoauthors = $this->updateIntlCoauthors( $lang, $intlBlurbs );
				$intlArticles = $this->updateIntlArticles( $lang, $intlBlurbs );

				$worksheetName = strtoupper($lang);
				$rowInfo = self::makeRowInfoHtml(0, self::getSheetId(), $worksheetName);
				$stats['imported'][] = $rowInfo . count($translations);
			}
		}

		return $stats;
	}

	public static function getSheetId(): string {
		global $wgIsDevServer;
		if ($wgIsDevServer) {
			return '1sFcNapOXQemTD0tbYbi3RYY1vSFy0TaolHcJYXhKXTY';
		} else {
			return '1wXloPN4fEahP4LEFeG_JMyyZALbK03URnP_uW3un2eg';
		}
	}

	private function fetchBlurbTranslationsFromSheet(string $lang, string $apiToken): array
	{
		$translations = [];
		$errors = [];
		$warnings = [];

		$sheetId = self::getSheetId();
		$worksheetName = strtoupper($lang);
		$rowGenerator = self::getWorksheetDataV4($sheetId, $worksheetName, $apiToken);

		foreach ($rowGenerator as $num => $row)
		{
			$rowInfo = self::makeRowInfoHtml($num, $sheetId, $worksheetName);
			$blurbId = trim($row['Blurb ID']);
			$byline = trim($row['Byline Translation']);
			$blurb = trim($row['Blurb Translation']);

			list($coauthorId, $blurbNum) = self::parseBlurbId($blurbId, 0, $errors, $rowInfo);

			if ( $blurbNum && !isset($this->enBlurbs[$blurbId]) ) {
				$errors[] = "$rowInfo Blurb ID not found: $blurbId";
			}

			if ( $blurbNum && isset($translations[$blurbId]) ) {
				$errors[] = "$rowInfo Duplicate blurb ID: $blurbId";
			}

			if ( !$byline ) {
				$errors[] = "$rowInfo Empty byline";
			} elseif ( strlen($byline) < 2 ) {
				$errors[] = "$rowInfo Byline too short: $byline";
			} elseif ( strlen($byline) > 150 ) {
				$errors[] = "$rowInfo Byline too long: $byline";
			}

			if ( !$blurb ) {
				$errors[] = "$rowInfo Empty blurb";
			} elseif ( strlen($blurb) < 5 ) {
				$errors[] = "$rowInfo Blurb too short: $blurb";
			} elseif ( strlen($blurb) > 1000 ) {
				$errors[] = "$rowInfo Blurb too long: $blurb";
			}

			$translations[$blurbId] = compact(
				'byline', 'blurb', 'blurbId', 'coauthorId', 'blurbNum'
			);
		}

		$errMsg = $rowGenerator->getReturn();
		if ($errMsg) {
			$rowInfo = self::makeRowInfoHtml(0, $sheetId, $worksheetName);
			$errors[] = "$rowInfo $errMsg";
		}

		return [ $translations, $errors, $warnings ];
	}

	private function updateIntlBlurbs(string $lang, array $translations): array
	{
		$blurbs = [];
		foreach ($this->enBlurbs as $blurbId => $enBlurb)
		{
			$translation = $translations[$blurbId] ?? null;
			if ( $translation ) {
				$cb = clone $enBlurb; // CoauthorBlurb
				$cb->byline = $translation['byline'];
				$cb->blurb = $translation['blurb'];
				$blurbs[$blurbId] = $cb;
			}
		}

		VerifyData::replaceBlurbs($lang, $blurbs);

		return $blurbs;
	}

	private function updateIntlCoauthors(string $lang, array $intlBlurbs): array
	{
		$coauthors = [];
		foreach ($this->enCoauthors as $coauthorId => $enCoauthor) {
			$blurbId = "v{$coauthorId}_b01"; // default blurb
			$intlBlurb = $intlBlurbs[$blurbId] ?? null;
			if ( $intlBlurb ) {
				$vd = clone $enCoauthor; // VerifyData
				$vd->blurb = $intlBlurb->byline;
				$vd->hoverBlurb = $intlBlurb->blurb;
				$coauthors[$coauthorId] = $vd;
			}
		}

		VerifyData::replaceCoauthors($lang, $coauthors);

		return $coauthors;
	}

	private function updateIntlArticles(string $lang, array $intlBlurbs): array
	{
		$articles = [];

		$dbr = wfGetDB(DB_REPLICA);
		$tables = [
			'titus_en'   => Misc::getLangDB('en') . '.titus_copy',
			'titus_intl' => Misc::getLangDB('en') . '.titus_copy',
		];
		$fields = [
			'en_verif_date'     => 'titus_en.ti_expert_verified_date_original',
			'en_page_id'        => 'titus_en.ti_page_id',
			'intl_page_id'      => 'titus_intl.ti_page_id',
			'intl_first_edit'   => 'titus_intl.ti_first_edit_timestamp',
			'intl_last_retrans' => 'titus_intl.ti_last_retranslation',
		];
		$where = [
			'titus_en.ti_language_code' => 'en',
			'titus_intl.ti_language_code' => $lang,
			'titus_en.ti_page_id' => array_keys($this->enArticles),
			'titus_en.ti_page_id = titus_intl.ti_tl_en_id',
			'titus_en.ti_expert_verified_date_original IS NOT NULL',
		];

		$rows = $dbr->select($tables, $fields, $where);

		foreach ($rows as $row) {
			// Only accept INTL articles that were created/retranslated after the EN article was verified
			$valid = ( $row->intl_first_edit && strcmp($row->en_verif_date, $row->intl_first_edit) < 0 )
				  || ( $row->intl_last_retrans && strcmp($row->en_verif_date, $row->intl_last_retrans) < 0 );
			if ( !$valid ) {
				continue;
			}

			$enAid = (int) $row->en_page_id;
			$intlAid = (int) $row->intl_page_id;
			$enArticle = $this->enArticles[$enAid];
			$blurbId = $enArticle->blurbId;
			$intlBlurb = $intlBlurbs[$blurbId] ?? null;

			if ($intlBlurb) {
				$vd = clone $enArticle; // VerifyData
				$vd->blurb = $intlBlurb->byline;
				$vd->hoverBlurb = $intlBlurb->blurb;
				$vd->aid = $intlAid;
				$vd->revisionId = null;

				$articles[$intlAid][] = $vd;
			}
		}

		VerifyData::replaceArticles($lang, $articles);

		return $articles;
	}

}
