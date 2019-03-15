<?php
if (!defined('MEDIAWIKI')) die();

class ImportVideoYoutube extends ImportVideo {

	private $prevOffset;
	private $nextOffset;

	function shortSnippet($snippet) {
		$min = min(strlen($snippet), 255);
		$snippet = substr($snippet, 0, $min);

		if ( $min === 255 ) {
			$snippet .= '...';
		}

		return $snippet;
	}

	function parseVideo($data) {

		$video = new stdClass();
		$video->id = $data->id;
		$video->title = $data->snippet->title;
		$video->author = $data->snippet->channelTitle;


		$video->likes = $data->statistics->likeCount;
		$video->keywords = $data->snippet->tags;
		$video->views = $data->statistics->viewCount;
		$video->description = $data->snippet->description;
		$video->embeddable = $data->status->embeddable == 1;
		$video->category = $this->getCategory($data->snippet->categoryId);


		$this->mResults[] = $video;
	}

	function parseSearch($data) {
		$data = json_decode($data);

		if ( !$data->items ) {
			return;
		}

		$this->prevOffset = $data->prevPageToken;
		$this->nextOffset = $data->nextPageToken;

		foreach ( $data->items as $video ) {
			$videoInfo = json_decode( $this->getResults( $this->getVideoURL( $video->id->videoId ) ) );
			$this->parseVideo( $videoInfo->items[0] );
		}
	}

	function getVideoURL($id) {
		$data = array(
						'part' => 'id,status,snippet,statistics',
						'id' => $id,
						'key' => WH_YOUTUBE_API_KEY
					);

		return 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query($data);
	}

	function getSearchURL($query, $maxResults, $orderby, $additionalParams = array() ) {

		$data = array(
						'part' => 'snippet',
						'maxResults' => $maxResults,
						'order' => $orderby,
						'q' => $query,
						'type' => 'video',
						'videoEmbeddable' => 'true',
						'videoSyndicated' => 'true',
						'key' => WH_YOUTUBE_API_KEY
					);

		$data = array_merge( $data, $additionalParams );
		return 'https://www.googleapis.com/youtube/v3/search?' . http_build_query($data);
	}

	function addResult($v) {
		//$id, $title, $author_id, $author_name, $keywords) {
		global $wgOut, $wgRequest, $wgImportVideoBadUsers;


		$id = $v->id;
		$views = number_format( $v->views );
		$rating = number_format( $v->likes );
		$title = $v->title;
		$snippet = $this->shortSnippet($v->description);

		if (!$v->embeddable || in_array(strtolower($v->author), $wgImportVideoBadUsers) )  {
			$importOption = wfMessage('importvideo_noimportpossible')->text();
		} else {
			$importOption = "<div class='embed_button'><input class='button primary' type='button' value='" . wfMessage('importvideo_embedit')->text() . "' onclick='WH.ImportVideo.importvideo(\"{$id}\"); gatTrack(\"Registered_Editing\",\"Import_video\",\"Editing_page\");'/></div>";
		}


		$wgOut->addHTML("
		<div class='video_result' style='width: 500px;'>
			<div style='font-size: 120%; font-weight: bold; margin-bottom:10px;'>{$title}</div>
			<table width='100%'>
				<tr>
					<td style='text-align:center'>

						<iframe src='https://www.youtube.com/embed/{$id}' width='425' height='350'></iframe>
					</td>
				</tr>
				<tr>
					<td>
						<b>" . wfMessage('importvideo_rating') . ": </b>{$rating}<br/><br/>
						<b>" . wfMessage('importvideo_views') . ": </b>{$views}  <br/><br/>
						<b>" . wfMessage('importvideo_description') . ": </b>{$snippet}<br /><br />
						{$importOption}
					</td>
				</tr>
				");

		$wgOut->addHTML(" </table></div> ");

	}

	function getCategory($categoryCode) {
		$categories = array(
			1 => 'Film & Animation',
			2 => 'Autos & Vehicles',
			10 => 'Music',
			15 => 'Pets & Animals',
			17 => 'Sports',
			18 => 'Short Movies',
			19 => 'Travel & Events',
			20 => 'Gaming',
			21 => 'Videoblogging',
			22 => 'People & Blogs',
			23 => 'Comedy',
			24 => 'Entertainment',
			25 => 'News & Politics',
			26 => 'Howto & Style',
			27 => 'Education',
			28 => 'Science & Technology',
			29 => 'Nonprofits & Activism',
			30 => 'Movies',
			31 => 'Anime/Animation',
			32 => 'Action/Adventure',
			33 => 'Classics',
			34 => 'Comedy',
			35 => 'Documentary',
			36 => 'Drama',
			37 => 'Family',
			38 => 'Foreign',
			39 => 'Horror',
			40 => 'Sci-Fi/Fantasy',
			41 => 'Thriller',
			42 => 'Shorts',
			43 => 'Shows',
			44 => 'Trailers'
		);

		return $categories[$categoryCode];
	}

	function getTopResults($target, $limit = 10, $query = null) {
		global $wgRequest;
		$orderby = $wgRequest->getVal('orderby', 'relevance');
		$additionalParams = array();

		// let them pass in text if they so desire
		if (!$target instanceof Title) {
			$t = Title::newFromText($target);
			$target = $t->getText();
		}

		if (!$query) {
			$query = wfMessage('howto', $target)->text();
		}

		if ( $wgRequest->getVal( 'page' ) ) {
			$additionalParams['pageToken'] = $wgRequest->getVal( 'page' );
		}

		if ($orderby == 'howto') {
			$additionalParams['videoCategoryId'] = 26;
			$url = $this->getSearchURL( $query, $limit, 'relevance', $additionalParams );
		} else {
			$url = $this->getSearchURL( $query, $limit, $orderby, $additionalParams );
		}

		$results = $this->getResults($url);
		$this->parseSearch($results);
	}

	function loadVideoText($id) {
		if (empty($id)){
			return null;
		}
		$url = $this->getVideoURL($id);
		$data = json_decode($this->getResults($url));
		$this->parseVideo($data->items[0]);
		$v = $this->mResults[0];
		$type = "youtube";

		// check if it is a wikihow account that made the video
		$author = $v->author;
		$wikihowauthors = explode("\n", wfMessage('importvideo_youtube_wikihow_authors')->text());;
		if ( in_array( $author, $wikihowauthors) ) {
			$type = "whyoutube";
		}

		$content = $this->urlCleaner( $v->description );
		$text = "{{Curatevideo|$type|$id|{$v->title}|{$v->keywords}|$content|{$v->category}}}\n{{VideoDescription|{{{1}}} }}";
		return $text;
	}

	function getPreviousNextButtons($prevOffset, $nextOffset) {
		global $wgRequest;
		$query = $wgRequest->getVal('q');
		$target = preg_replace('@ @','+',$wgRequest->getVal('target'));
		$me = Title::makeTitle(NS_SPECIAL, "ImportVideo");

		// Previous, Next buttons if necessary
		$s = "<table width='100%'><tr><td>";
		$url = $me->getFullURL() . "?target=$target&source={$this->mSource}" . $this->getURLExtras();
		$perpage = 10;

		if ($prevOffset != null) {
			$nurl =  $url ."&page=" . $prevOffset . "&q=" . urlencode($query);
			$s .= "<a href='$nurl'>" . wfMessage('importvideo_previous_results', 10)->text() . "</a>";
		}

		$s .= "</td><td align='right'>";

		if ($nextOffset != null) {
				$nurl = $url . "&page=" . $nextOffset . "&q=" . urlencode($query);
				$s .= "<a href='$nurl'>" . wfMessage('importvideo_next_results', 10)->text() . "</a>";
		}

		$s .= "</td></tr></table>";

		return $s;
	}



	function execute ($par) {
		global $wgRequest, $wgOut;

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		if ($wgRequest->wasPosted()) {
			// IMPORTING THE VIDEO NOW
			$id = $wgRequest->getVal('video_id');
			$desc = $wgRequest->getVal('description');
			$target = $wgRequest->getVal('target');

			$text = $this->loadVideoText($id);
			if ($text == null) {
				$wgOut->addHTML(wfMessage('importvideo_error_geting_results')->text());
				return;
			}
			$v = $this->mResults[0];
			$author = $v->author;

			$badauthors = explode("\n", wfMessage('Block_Youtube_Accounts')->text());;
			if ( in_array( $author, $badauthors) ) {
				$wgOut->addHTML(wfMessage('importvideo_youtubeblocked', $author)->text());
				return;
			}

			$title = Title::makeTitle(NS_VIDEO, $target);
			$vid = Title::makeTitle(NS_VIDEO, $title->getText());
			$editSummary = wfMessage('importvideo_addingvideo_summary')->text();
			ImportVideo::updateVideoArticle($vid, $text, $editSummary);
			ImportVideo::updateMainArticle($target, $editSummary);
			return;
		}


		if ($target == '') {
			$wgOut->addHTML(wfMessage('importvideo_notarget')->text());
			return;
		}

		$orderby = $wgRequest->getVal('orderby', 'relevance');
		$wgOut->addHTML($this->getPostForm($target));
		$query = $wgRequest->getVal('q');
		$this->getTopResults($target, 10, $query);

		if ( !$query ) {
			if ( $target instanceof Title ) {
				$query = $target->getText();
			} else {
				$query = str_replace('-', ' ',$target);
			}
		}

		$wgOut->addHTML(" <br/>
			" . wfMessage('importvideo_youtube_sortby')->text() . " <select name='orderby' id='orderby' onchange='WH.ImportVideo.changeUrl();'>
				<OPTION value='relevance' " . ($orderby == 'relevance' ? "SELECTED" : "") . "> " . wfMessage('importvideo_youtubesort_rel')->text() . "</OPTION>
				<OPTION value='howto' " . ($orderby == 'howto' ? "SELECTED" : "") . "> " . wfMessage('importvideo_youtubesort_howto')->text() . "</OPTION>
				<OPTION value='date' " . ($orderby == 'date' ? "SELECTED" : "") . "> " . wfMessage('importvideo_youtubesort_date')->text() . "</OPTION>
				<OPTION value='viewCount' " . ($orderby == 'viewCount' ? "SELECTED" : "") . "> " . wfMessage('importvideo_youtubesort_views')->text() . "</OPTION>
			</select>
			<br/><br/>
			");

		if ($this->mResults == null) {
			$wgOut->addHTML(wfMessage("importvideo_error_geting_results")->text());
			return;
		}



		#print_r($this->mResults);
		if (sizeof($this->mResults) == 0) {
			#$wgOut->addHTML(wfMessage('importvideo_noresults', $target) . htmlspecialchars($results) );
			$wgOut->addHTML(wfMessage('importvideo_noresults', $query)->text());
			$wgOut->addHTML("</form>");
			return;
		}

		$wgOut->addHTML( wfMessage('importvideo_results', $query)->text() );

		$resultsShown = false;
		foreach ($this->mResults as $v) {
			$resultsShown = true;
			$this->addResult($v);
		}

		if (!$resultsShown) {
			$wgOut->addHTML(wfMessage('importvideo_noresults', $query)->text());
			$wgOut->addHTML("</form>");
			return;
		}

		$wgOut->addHTML("</form>");

		$wgOut->addHTML($this->getPreviousNextButtons($this->prevOffset, $this->nextOffset));
	}

}

