<?php

class CoauthorSheetIntl extends CoauthorSheet
{
	public static function importTranslations(): array {
		global $wgActiveLanguages;

		$stats = [ 'imported'=>[], 'errors'=>[], 'warnings'=>[] ];
		$apiToken = self::getApiAccessToken();

		$enBlurbs = VerifyData::getAllBlurbsFromDB('en');
		$enCoauthors = VerifyData::getAllVerifierInfoFromDB('en');
		$enArticles = VerifyData::getAllArticlesFromDB('en');
		list($overrides, $errors) = self::fetchDateOverridesFromSheet($apiToken);

		foreach ($wgActiveLanguages as $lang) {
			list($translations, $errors, $warnings) = self::fetchBlurbTranslationsFromSheet($lang, $enBlurbs, $apiToken);
			$stats['errors'] = array_merge( $stats['errors'], $errors );
			$stats['warnings'] = array_merge( $stats['warnings'], $warnings );
			if ( !$errors ) {
				$intlBlurbs = self::updateIntlBlurbs( $lang, $enBlurbs, $translations );
				self::updateIntlCoauthors( $lang, $enCoauthors, $intlBlurbs );
				self::updateIntlArticles( $lang, $enArticles, $intlBlurbs, $overrides );

				$worksheetName = strtoupper($lang);
				$rowInfo = self::makeRowInfoHtml(0, self::getLocalizationSheetId(), $worksheetName);
				$stats['imported'][] = $rowInfo . count($translations);
			}
		}

		return $stats;
	}

	public static function getLocalizationSheetId(): string {
		global $wgIsDevServer;
		if ($wgIsDevServer) {
			return '1IN15FiCdCgZ5U9_lXcEFwSdA5_AoTeoTZdAJCthj0FQ'; // dev
		} else {
			return '1wXloPN4fEahP4LEFeG_JMyyZALbK03URnP_uW3un2eg'; // prod
		}
	}

	public static function getOverridesSheetId(): string {
		global $wgIsDevServer;
		if ($wgIsDevServer) {
			return '1P1Qbm4d8QvTdd6uBUNQNntChu3uJk94qzNQUeC_WiAY'; // dev
		} else {
			return '1tm3USIvG-ug-OT7Bv9gYP6idhtIH3uwxy2Dji46sFZQ'; // prod
		}
	}

	public static function recalculateIntlArticles(): array {
		global $wgActiveLanguages;

		$apiToken = self::getApiAccessToken();
		$enArticles = VerifyData::getAllArticlesFromDB('en');
		list($overrides, $errors) = self::fetchDateOverridesFromSheet($apiToken);

		foreach ($wgActiveLanguages as $lang) {
			$intlBlurbs = VerifyData::getAllBlurbsFromDB($lang);
			$intlArticles = self::updateIntlArticles($lang, $enArticles, $intlBlurbs, $overrides);
		}

		$overrideCount = [];
		foreach ($overrides as $lang => $aids) {
			$worksheetName = strtoupper($lang);
			$rowInfo = self::makeRowInfoHtml(0, self::getOverridesSheetId(), $worksheetName);
			$overrideCount[] = $rowInfo . count($aids);
		}

		return [ 'imported' => $overrideCount, 'errors' => $errors, 'warnings' => [] ];
	}

	private static function fetchBlurbTranslationsFromSheet(string $lang, array $enBlurbs, string $apiToken): array
	{
		$translations = [];
		$errors = [];
		$warnings = [];

		$sheetId = self::getLocalizationSheetId();
		$worksheetName = strtoupper($lang);
		$rowGenerator = self::getWorksheetDataV4($sheetId, $worksheetName, $apiToken);

		foreach ($rowGenerator as $num => $row)
		{
			$rowInfo = self::makeRowInfoHtml($num, $sheetId, $worksheetName);
			$blurbId = trim($row['Blurb ID']);
			$byline = trim($row['Byline Translation']);
			$blurb = trim($row['Blurb Translation']);

			list($coauthorId, $blurbNum) = self::parseBlurbId($blurbId, 0, $errors, $rowInfo);

			if ( $blurbNum && !isset($enBlurbs[$blurbId]) ) {
				$errors[] = "$rowInfo Blurb ID not found: $blurbId";
			}

			if ( $blurbNum && isset($translations[$blurbId]) ) {
				$errors[] = "$rowInfo Duplicate blurb ID: $blurbId";
			}

			if ( !$byline ) {
				$errors[] = "$rowInfo Empty byline";
			} elseif ( mb_strlen($byline) < 2 ) {
				$errors[] = "$rowInfo Byline too short: $byline";
			} elseif ( mb_strlen($byline) > 200 ) {
				$errors[] = "$rowInfo Byline too long: $byline";
			}

			if ( !$blurb ) {
				$errors[] = "$rowInfo Empty blurb";
			} elseif ( mb_strlen($blurb) < 5 ) {
				$errors[] = "$rowInfo Blurb too short: $blurb";
			} elseif ( mb_strlen($blurb) > 1500 ) {
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

	private static function updateIntlBlurbs(string $lang, array $enBlurbs, array $translations): array
	{
		$blurbs = [];
		foreach ($enBlurbs as $blurbId => $enBlurb)
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

	private static function updateIntlCoauthors(string $lang, array $enCoauthors, array $intlBlurbs): array
	{
		$coauthors = [];
		foreach ($enCoauthors as $coauthorId => $enCoauthor) {
			$blurbId = "v{$coauthorId}_b01"; // default blurb
			$intlBlurb = $intlBlurbs[$blurbId] ?? null;

			$vd = clone $enCoauthor; // VerifyData
			$vd->blurb = $intlBlurb->byline ?? null;
			$vd->hoverBlurb = $intlBlurb->blurb ?? null;
			$coauthors[$coauthorId] = $vd;
		}

		VerifyData::replaceCoauthors($lang, $coauthors);

		return $coauthors;
	}

	/**
	 * Recalculate and update the list coauthored articles
	 *
	 * @param  string $lang
	 * @param  array  $enArticles  EN coauthored articles
	 * @param  array  $intlBlurbs  Translations for blurbs and bylines
	 * @param  array  $overrides   INTL article IDs to which date rules don't apply
	 * @return array
	 */
	private static function updateIntlArticles(string $lang, array $enArticles,
		array $intlBlurbs, array $overrides): array
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
			'titus_en.ti_page_id' => array_keys($enArticles),
			'titus_en.ti_page_id = titus_intl.ti_tl_en_id',
			'titus_en.ti_expert_verified_date_original IS NOT NULL',
			'titus_en.ti_expert_verified_source' => ['academic', 'community', 'expert', 'video'],
		];

		$rows = $dbr->select($tables, $fields, $where);

		foreach ($rows as $row) {
			// Only accept INTL articles that were created/retranslated after the EN article was verified
			$intlAid = (int) $row->intl_page_id;
			$valid = isset( $overrides[$lang][$intlAid] )
				  || ( $row->intl_first_edit && strcmp($row->en_verif_date, $row->intl_first_edit) < 0 )
				  || ( $row->intl_last_retrans && strcmp($row->en_verif_date, $row->intl_last_retrans) < 0 );
			if ( !$valid ) {
				continue;
			}

			$enAid = (int) $row->en_page_id;
			$enArticle = $enArticles[$enAid];
			$blurbId = $enArticle->blurbId;
			$intlBlurb = $intlBlurbs[$blurbId] ?? null;

			$vd = clone $enArticle; // VerifyData
			$vd->blurb = $intlBlurb->byline ?? null;
			$vd->hoverBlurb = $intlBlurb->blurb ?? null;
			$vd->aid = $intlAid;
			$vd->revisionId = null;

			$articles[$intlAid][] = $vd;
		}

		VerifyData::replaceArticles($lang, $articles);

		return $articles;
	}

	private static function fetchDateOverridesFromSheet(string $apiToken): array
	{
		global $wgActiveLanguages;

		$langs = array_flip($wgActiveLanguages);
		$errors = [];
		$overrides = [];

		$sheetId = self::getOverridesSheetId();
		$worksheetName = 'Master';
		$rowGenerator = self::getWorksheetDataV4($sheetId, $worksheetName, $apiToken);

		foreach ($rowGenerator as $num => $row) {
			$rowInfo = self::makeRowInfoHtml($num, $sheetId, $worksheetName);


			$aid = intval( $row['Article ID'] );
			if ( $aid <= 0 ) {
				$errors[] = "$rowInfo Invalid article ID: " . $row['Article ID'];
				continue;
			}

			$lang = strtolower( trim($row['Lang']) );
			if ( !isset($langs[$lang]) ) {
				$errors[] = "$rowInfo Invalid language code: " . $row['Lang'];
				continue;
			}

			$overrides[$lang][$aid] = true;
		}

		$errMsg = $rowGenerator->getReturn();
		if ($errMsg) {
			$rowInfo = self::makeRowInfoHtml(0, $sheetId, $worksheetName);
			$errors[] = "$rowInfo $errMsg";
		}

		return [ $overrides, $errors ];
	}

}
