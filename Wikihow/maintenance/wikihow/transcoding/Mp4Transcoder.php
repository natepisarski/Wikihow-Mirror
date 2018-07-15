<?php

require_once __DIR__ . '/../../commandLine.inc'; 

global $IP;
require_once "$IP/extensions/wikihow/common/S3.php"; 
require_once "$IP/extensions/wikihow/DatabaseHelper.class.php";
require_once "$IP/extensions/wikihow/whvid/WikiVideo.class.php";
require_once __DIR__ . "/AbsTranscoder.php";

class Mp4Transcoder extends AbsTranscoder {
	
	public static function getTranscoderService() {
		$aws = WikiVisualTranscoder::getAws();
		return $aws->get('ElasticTranscoder');
	}

	private function getThumbUri(&$awsJob) {
		$output = $awsJob["Outputs"][0];
		$thumbPrefix = $output["Key"];
		$thumbPrefix = str_replace(WikiVisualTranscoder::VIDEO_EXTENSION, ".", $thumbPrefix);
	
		$thumbUris = array();
		$svc = WikiVisualTranscoder::getS3Service();
		$lastKey = null;
		do {
			$inputs = array( 'Bucket' => WikiVisualTranscoder::AWS_TRANSCODING_OUT_BUCKET, 'Prefix' => $thumbPrefix);
			if (!is_null($lastKey)) {
				$inputs['Marker'] = $lastKey;
			}
			$result = $svc->listObjects($inputs);
			$contents = $result['Contents'];
			if ( $contents ) {
				foreach ($contents as $key => $val) {
					$thumbUris[] = $val['Key'];
					$lastKey = $val['Key'];
				}
			}
		} while ($result['IsTruncated']);
	
		// Pull out the jpgs
		// Grab the last thumbnail frame with pattern  <key>/<filename>.{resolution}.{count}.jpg
		$thumbUris = preg_grep('@.*\.jpg$@', $thumbUris);
		$thumbUri = end($thumbUris);

		// if this is a zero step video use the first frame for the thumbnail
		if ( $thumbUris && substr( $thumbPrefix, -3 === '-0.' ) ) {
			$thumbUri = $thumbUris[0];
		}

		return $thumbUri;
	}
	
	
	private function dbRecordTranscodingJobStatus($aid, &$awsJob) {
		$dbw = WikiVisualTranscoder::getDB('write');
	
		// Should only be one output file format
		$output = $awsJob["Outputs"][0];
		$thumbUri = $output['Status'] == 'Complete' ? $this->getThumbUri($awsJob) : '';
		$statusDetail = is_null($output['StatusDetail']) ? "" : $output['StatusDetail'];
		$sql = 'REPLACE INTO wikivisual_vid_transcoding_status SET
			article_id=' . $dbw->addQuotes($aid) . ',
			aws_job_id=' . $dbw->addQuotes($awsJob['Id']) . ',
			aws_uri_in=' . $dbw->addQuotes($awsJob['Input']['Key']) . ',
			aws_uri_out=' . $dbw->addQuotes($output['Key']) . ',
			aws_thumb_uri=' . $dbw->addQuotes($thumbUri) . ',
			processed=' . $dbw->addQuotes(wfTimestampNow(TS_MW)) . ',
			status=' . $dbw->addQuotes($output['Status']) . ',
			status_msg=' . $dbw->addQuotes($statusDetail);
		$dbw->query($sql, __METHOD__);
	}
	
	
	private function dbUpdateArticleJobsStatus($aid) {
		$dbJobs = $this->dbGetTranscodingArticleJobs($aid);
		$svc = self::getTranscoderService();
		foreach ($dbJobs as $dbJob) {
			$awsJob = $this->getAwsTranscodingJobStatus($dbJob['aws_job_id']);
			$this->dbRecordTranscodingJobStatus($aid, $awsJob);
		}
	}

	private function getAwsTranscodingJobStatus($jobId) {
		$svc = self::getTranscoderService();
		$response = $svc->readJob(array("Id" => $jobId));
		return $response['Job'];
	}
	
	public function dbGetTranscodingArticleJobs($aid) {
		$articles = array();
		$dbr = WikiVisualTranscoder::getDB('read');
		// Update any article job status for articles in the transcoding state
		$rows = $dbr->select('wikivisual_vid_transcoding_status', array('*'), array('article_id' => $aid), __METHOD__);
		$jobs = array();
		foreach ($rows as $row) {
			$jobs[] = get_object_vars($row);
		}
		return $jobs;
	}
	
	private function hasTranscodingErrors($aid) {
		$err = '';
		$dbr = WikiVisualTranscoder::getDB('read');
		$rows = $dbr->select('wikivisual_vid_transcoding_status', array('aws_uri_in', 'status_msg'), array('article_id' => $aid, "status" => 'Error'), __METHOD__);
		$errors = array();
		foreach ($rows as $row) {
			$errors[] = $row->aws_uri_in . ' - ' . $row->status_msg;
		}
	
		if (sizeof($errors)) {
			$err = implode("\n", $errors);
		}
		return $err;
	}

	/**
	 * Add a new image file into the mediawiki infrastructure so that it can
	 * be accessed as [[Image:filename.jpg]]
	 */
	public static function addMediawikiImage($articleID, &$image) {
	
		// Download the preview image and set the filename to the temporarary location
		$err = WikiVisualTranscoder::downloadImagePreview($image);
		if ($err) {
			return $err;
		}
	
		// check if we've already uploaded this image
		$dupTitle = DupImage::checkDupImage($image['filename']);
	
		// if we've already uploaded this image, just return that filename
		if ($dupTitle) {
			//$image['dupTitle'] = true;
			$image['mediawikiName'] = $dupTitle;
			return '';
		}
	
	
	
		// find name for image; change filename to Filename 1.jpg if
		// Filename.jpg already existed
		$regexp = '/[^' . Title::legalChars() . ']+/';
		$first = preg_replace($regexp, '', $image['first']);
		$ext = $image['ext'];
		$newName = $first . '-preview.' . $ext;
		$i = 1;
		do {
			$title = Title::newFromText($newName, NS_IMAGE);
			if ($title && !$title->exists()) break;
			$newName = $first . '-preview Version ' . ++$i . '.' . $ext;
		} while ($i <= 1000);
	
		// insert image into wikihow mediawiki repos
		$comment = '{{' . WikiVisualTranscoder::PHOTO_LICENSE . '}}';
		// next 6 lines taken and modified from
		// extensions/wikihow/imageupload/ImageUploader.body.php
		$title = Title::makeTitleSafe(NS_IMAGE, $newName);
		if (!$title) return "Couln't Make a title";
		$file = new LocalFile($title, RepoGroup::singleton()->getLocalRepo());
		if (!$file) return "Couldn't make a local file";
		$ret = $file->upload($image['filename'], $comment, $comment);
		if (!$ret->ok) return "Couldn't upload file " . $image['filename'];
	
		// instruct later processing about which mediawiki name was used
		$image['mediawikiName'] = $newName;
	
		// Add our uploaded image to the dup table so it's no uploaded again
		DupImage::addDupImage($image['filename'], $image['mediawikiName']);
	
		return '';
	}
	
	private function dbRemoveTranscodingJobs($aid) {
		$dbw = WikiVisualTranscoder::getDB('write');
		$dbw->delete('wikivisual_vid_transcoding_status', array("article_id" => $aid), __METHOD__);
	}
	
	/*
	 * Removes db entries and any associated S3 uris associated with the
	* transcoding article
	*/
	private function removeOldTranscodingJobs($aid) {
		self::dbRemoveTranscodingJobs($aid);
	}
	
	
	public function processTranscodingArticle($articleId, $creator) {
		if (!isset($articleId) || !isset($creator)) return false;
		
		$this->dbUpdateArticleJobsStatus($articleId);
		
		if ($err = $this->hasTranscodingErrors($articleId)) {
			return array(WikiVisualTranscoder::STATUS_ERROR, $err);
		} elseif (WikiVisualTranscoder::isStillTranscoding($articleId)) {
			return array(WikiVisualTranscoder::STATUS_TRANSCODING, null);
		}
		return array(WikiVisualTranscoder::STATUS_COMPLETE, null);
	}

	private function createTranscodingJob($dir, $filename) {
		$dir = $dir . "/";
		$svc = self::getTranscoderService();
		$presetId = WikiVisualTranscoder::TRANSCODER_360P_16x9_PRESET;

		if ( substr( $filename, -6 === '-0.mp4' ) ) {
			$presetId = WikiVisualTranscoder::TRANSCODER_360P_16x9_PRESET_AUDIO;
		}

		$params = array(
				'PipelineId' => WikiVisualTranscoder::AWS_PIPELINE_ID,
				'Input' => array(
						'Key' => $dir . $filename,
						'FrameRate' => 'auto',
						'Resolution' =>	'auto',
						'AspectRatio' => 'auto',
						'Interlaced' => 'auto',
						'Container' => 'auto',
					),
					'Output' => array(
						'Key' => $dir . basename($filename, ".mp4") . WikiVisualTranscoder::VIDEO_EXTENSION,
						'ThumbnailPattern' => $dir . basename($filename, ".mp4") . ".{resolution}.{count}",
						'Rotate' => '0',
						'PresetId' => $presetId,
					)
				);
		$ret = $svc->createJob($params);
		return $ret['Job'];
	}
	
	//transcode or schedule transcode
	public function processMedia( $pageId, $creator, $videoList, $warning, $isHybridMedia ) {
		$this->removeOldTranscodingJobs($pageId);

		$err = '';
		$s3 = new S3(WH_AWS_WIKIVIDEO_PROD_ACCESS_KEY, WH_AWS_WIKIVIDEO_PROD_SECRET_KEY);
		
		$transcodeDir = $pageId . "-" . mt_rand();
		foreach ( $videoList as $video ) {
			$transcodeInPath = $transcodeDir . "/" . $video['name'];
			$err = WikiVisualTranscoder::postFile( $s3, $video['filename'], $transcodeInPath, WikiVisualTranscoder::AWS_TRANSCODING_IN_BUCKET );
			self::d( "Posting ". $transcodeInPath ." to S3, err=". $err );
			if ( $err ) {
				break;
			}

			$result = $this->createTranscodingJob( $transcodeDir, $video['name'] );
			if ( $result['Status'] == 'Error' ) {
				$err = "Transcoding job creation error. file: $transcodeInPath, id: {$result[3]}, msg: $result[2]";
				break;
			}

			self::d( "Transcoding job created for", $pageId );
			self::d( "result", $result );
			$this->dbRecordTranscodingJobStatus( $pageId, $result );
		}
		$status = $err ? WikiVisualTranscoder::STATUS_ERROR : WikiVisualTranscoder::STATUS_TRANSCODING;
		self::d("Transcoding job for page: ". $pageId . ", status: ". $status);

		return array( $err, $status );
	}
}
