<?php

class ImportVideoHowcast extends ImportVideo {

	function parseStartElement ($parser, $name, $attrs) {
	   switch ($name) {
			#case "MEDIA:PLAYER":
			#    $this->mCurrentNode['MEDIA:URL'] = $attrs['URL'];
			#    break;
		}
		if ($name == 'VIDEO') {
			$this->mCurrentNode = array();
		}
		$this->mCurrentTag = $name;
	}

	function parseEndElement ($parser, $name) {
		if ($name == "VIDEO") {
			$this->mResults[] = $this->mCurrentNode;
			$this->mCurrentNode = null;
		}
	}
	function parseResults($results) {
		$data = new SimpleXMLElement($results);
		$entries = get_object_vars($data->entries);
		$entries = $entries["entry"];

		if (!$entries) {
			return;
		}

		foreach ($entries as $entry) {
			$entry = get_object_vars($entry);
			if ($entry["resource-type"] == "Howto") {
				$this->mResults[] = $entry;
			}
		}
	}

	function execute($par) {
		global $wgOut, $wgRequest, $wgHowcastAPIKey;

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		if ($wgRequest->wasPosted()) {
			// IMPORTING THE VIDEO NOW
			$id = $wgRequest->getVal('video_id');
			$desc = $wgRequest->getVal('desc');
			//$url = "http://api.howcast.com/videos/{$id}.xml?api_key={$wgHowcastAPIKey}";
			$url = "http://msapi.howcast.com/howtos/{$id}.xml?channel=howcast";
			$results = $this->getResults($url);
			if ($results == null) {
				$wgOut->addHTML(wfMessage("importvideo_error_geting_results")->text());
				return;
			}


			$title = Title::makeTitle(NS_VIDEO, $target);
			#echo $v['TITLE']; exit; echo $id; print_r($this->mResults); print_r($v); exit;
			$v = new SimpleXMLElement($results);
			$v = get_object_vars($v);
			$titletext = $v['title'];
			//$tags = trim($v['TAGS']);
			$tags = '';
			$text = "{{Curatevideo|howcast|$id|{$titletext}|{$tags}|{$v['description']}||{$desc}}}
{{VideoDescription|{{{1}}} }}";
			$editSummary = wfMessage('importvideo_addingvideo_summary')->text();
			ImportVideo::updateVideoArticle($title, $text, $editSummary);
			ImportVideo::updateMainArticle($target, $editSummary);
			return;
		}

		$t = Title::newFromText($target);
		$target = $t->getText();
		$tar_es = urlencode($target);
		$query = $wgRequest->getVal('q');
		if ($query == '') $query = $tar_es;
		else $query = urlencode($query);

		$url = "http://msapi.howcast.com/search.xml?term={$query}&channels=howcast&per_page=10&page=1";
		//$url = "http://info.howcast.com/search.xml?q={$query}&view=video&api_key={$wgHowcastAPIKey}";
		$results = $this->getResults($url);
		$this->parseResults($results);
		$wgOut->addHTML($this->getPostForm($target));

		if (!is_array($this->mResults) || sizeof($this->mResults) == 0) {
			$wgOut->addHTML(wfMessage('importvideo_noarticlehits')->text());
			$wgOut->addHTML("</form>");
			return;
		}
		$resultsShown = false;
		foreach ($this->mResults as $v) {
			/* Turning off since howcast no longer provides a created date for the search api videos
			if (!$this->isValid($v['CREATED-AT'])) {
				continue;
			}
			*/
			$resultsShown = true;
			$this->addResult($v);
		}
		if (!$resultsShown) {
			$wgOut->addHTML(wfMessage('importvideo_noarticlehits')->text());
			$wgOut->addHTML("</form>");
			return;
		}
		$wgOut->addHTML("</form>");
	}

	function addResult($v) {
		//$id, $title, $author_id, $author_name, $keywords) {
		global $wgOut, $wgRequest;

		$url = $v['resource-url'];
		$id = end(explode("/", $url));
		$id = array_shift(explode('-', $id));
		$min = min(strlen($v['description']), 255);
		$snippet = substr($v['description'], 0, $min);
		if ($min == 255) $snippet .= "...";
		$title = $v['title'];
		$vid = wfMessage('importvideo_howcast_result', $id)->text();


		$wgOut->addHTML("
		<div class='video_result'>
			<div style='font-size: 120%; font-weight: bold; margin-bottom:10px;'>" . wfMessage('video') . ": {$title}</div>
			<table width='100%'>
				<tr>
					<td style='text-align:center'>{$vid}</td>
				</tr>
				<tr>
					<td>
						<b>" . wfMessage('importvideo_description')->text() . ": </b>{$snippet}<br /><br />
						<div class='embed_button'>
							<input class='button primary' type='button' value='" . wfMessage('importvideo_embedit')->text() . "' onclick='WH.ImportVideo.importvideo(\"{$id}\"); gatTrack(\"Registered_Editing\",\"Import_video\",\"Editing_page\");'/>
						</div>
					</td>
				</tr>
			</table>
		</div>
			");
	}

}
