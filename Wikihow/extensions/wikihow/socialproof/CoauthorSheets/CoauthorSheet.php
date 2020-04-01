<?php

abstract class CoauthorSheet
{
	/**
	 * @param  string|null  $idStr      Expected format is '123'.
	 *                                  Comes from $row->{'gsx$coauthorid'}->{'$t'}.
	 *                                  Normally a string, but NULL if error.
	 * @param  array        &$errors
	 * @param  string       $rowInfo    An HTML link to the row in the spreadsheet
	 * @param  array|null   $coauthors  All authors in the 'Co-Author Lookup' worksheet
	 *
	 * @return int                      0 if $idStr is malformed
	 */
	protected static function parseCoauthorId(
		string $idStr=null, array &$errors, string $rowInfo, array $coauthors=null): int
	{
		$ret = 0;
		$idTrim = trim($idStr);
		$idInt = (int) $idTrim;
		if ( !$idTrim) {
			$errors[] = "$rowInfo Empty coauthor ID";
		} elseif ( $idInt <= 0 ) {
			$errors[] = "$rowInfo Invalid coauthor ID: $idStr";
		} elseif ( $coauthors && !isset($coauthors[$idInt]) ) {
			$errors[] = "$rowInfo Coauthor ID not found in 'Co-Author Lookup': $idStr";
		} else {
			$ret = $idInt;
		}

		return $ret;
	}

	/**
	 * @param  string|null $blurbId   Expected format is 'v0123_b01'
	 * @param  int         $coaId     Parsed 'Coauthor ID' column
	 * @param  array       &$errors
	 * @param  string      $rowInfo
	 *
	 * @return array                  [ COAUTHOR_ID, BLURB_NUM ], or [0, 0] on failure
	 */
	protected static function parseBlurbId(string $blurbId=null, int $coaId,
		array &$errors, string $rowInfo): array
	{
		$coauthorId = $blurbNum = $error = 0;

		if ( !trim($blurbId) ) {
			$error = "$rowInfo Empty blurb ID";
		}
		elseif ( preg_match('/^v([0-9]+)_b([0-9]+)$/', trim($blurbId), $matches) ) {
			$coauthorId = (int) $matches[1];
			$blurbNum = (int) $matches[2];
			if ( $blurbNum <= 0 ) {
				$error = "$rowInfo Invalid blurb ID (blurb # is $blurbNum): $blurbId";
			} elseif ( $coauthorId && $coaId && ($coauthorId != $coaId) ) {
				$error = "$rowInfo Coauthor ID doesn't match blurb ID: $coaId vs $blurbId";
			}
		}
		else {
			$error = "$rowInfo Invalid blurb ID: $blurbId";
		}

		if ($error) {
			$errors[] = $error;
			return [0, 0];
		} else {
			return [ $coauthorId, $blurbNum ];
		}

	}

	protected static function makeRowInfoHtml(int $rowNo, string $sheetId, string $sheetName): string {
		$rowLink = self::makeRowLink($rowNo, $sheetId, $sheetName);
		return "<span class='spa_location'>$rowLink</span>";
	}

	protected static function makeRowLink(int $rowNo, string $sheetId, string $sheetName): string {
		$worksheets = [
			// Master Expert Verified
			'coauthors' => '1516230615',
			'blurbs' => '493402436',
			'expert' => '0',
			'academic' => '736642124',
			'video' => '237286064',
			'community' => '767097190',
			'videoverified' => '1410489847',
			'chefverified' => '2067227246',
			// Coauthor Localization
			'AR' => '1483416064',
			'CS' => '605737712',
			'DE' => '1748546274',
			'ES' => '1501876960',
			'FR' => '368117995',
			'HI' => '193367141',
			'ID' => '1937719486',
			'IT' => '96087586',
			'JA' => '586685789',
			'KO' => '1959273612',
			'NL' => '161402666',
			'PT' => '152528290',
			'RU' => '1243258189',
			'TH' => '865321898',
			'TR' => '1817984306',
			'VI' => '457551658',
			'ZH' => '290031945',
		];

		$worksheetId = $worksheets[$sheetName];
		$linkText = $sheetName;
		$linkHref = "https://docs.google.com/spreadsheets/d/{$sheetId}/edit#gid={$worksheetId}";
		if ($rowNo) {
			$linkText .= ": $rowNo";
			$linkHref .= "&range=A{$rowNo}";
		}

		return Html::rawElement('a', [ 'href'=>$linkHref, 'target'=>'_blank' ], $linkText);
	}

}
