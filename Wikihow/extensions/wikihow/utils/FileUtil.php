<?php

/**
 * Methods to create and serve CSV and ZIP files
 *
 * These methods were extracted from DomitianUtil (DomitianDB.class.php)
 */
class FileUtil
{
	const TEMP_FILE_DIR = '/data/file_utils/default/';
	const RATE_LIMIT = 1048576; // 1024*1024 B/s

	public static function writeCSV(string $fname, array &$csv): string
	{
		$path = static::TEMP_FILE_DIR . $fname;
		$fp = fopen($path, 'w');
		if ($fp === false) {
			echo "Can't open file: $path\n";
		} else {
			foreach ($csv as $row) {
				fputcsv($fp, $row);
			}
			fclose($fp);
		}
		return $fname;
	}

	public static function writeZip(array &$files, string $baseName): string
	{
		$zipFname = $baseName . '.' . wfTimestampNow() . '.zip';
		$ret = exec(
			'cd ' . static::TEMP_FILE_DIR . ' && zip ' . $zipFname . ' '
			. implode(' ', $files), $out, $err
		);
		return $zipFname;
	}

	public static function downloadCSV(string $fname, string $body)
	{
		static::outputFile($fname, $body, 'text/csv');
	}

	public static function downloadZip(string $fname)
	{
		static::downloadFile($fname, 'application/zip');
	}

	protected static function outputFile(string $fname, string &$body, string $mimeType)
	{
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: public');
		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		header('Content-Type: ' . $mimeType);
		header('Content-Type: application/download');
		header('Content-Disposition: attachment; filename="'. addslashes($fname) . '"');
		header('Content-Transfer-Encoding: binary');
		flush();
		print $body;
	}

	public static function downloadFile(string $fname, string $mimeType)
	{
		$fp = fopen(static::TEMP_FILE_DIR . $fname, 'r');
		while (!feof($fp)) {
			static::outputFile($fname, fread($fp, round(static::RATE_LIMIT)), $mimeType);
			sleep(1);
		}
		fclose($fp);
	}

	public static function deleteFile(string $fname): bool
	{
		return unlink(static::TEMP_FILE_DIR . $fname);
	}
}

