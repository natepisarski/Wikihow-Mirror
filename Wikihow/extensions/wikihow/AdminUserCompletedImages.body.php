<?php

class AdminUserCompletedImages extends UnlistedSpecialPage {
	private $specialPage;
	private $preTextURL;
	private $defaultRowsPerPage = 10;
	private $defaultColumnsPerRow = 4;

	public function __construct() {
		global $wgTitle, $wgHooks;

		$this->specialPage = $wgTitle->getPartialUrl();
		$wgHooks['ShowSideBar'][] = array($this, 'removeSideBarCallback');

		parent::__construct($this->specialPage);
	}

	/**
	 * Execute special page. Only available to wikiHow staff.
	 */
	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$langCode = $this->getLanguage()->getCode();

		$userGroups = $user->getGroups();
		if ($langCode != 'en' || $user->isBlocked() || !in_array('staff', $userGroups) && $user->getName() != "G.bahij") {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
		} else {
			// Fetch images before or after time 't'
			$timeCutoff = $req->getVal('t') or wfTimestamp(TS_MW);
			if (!ctype_digit($timeCutoff))
				$timeCutoff = wfTimestamp(TS_MW);

			$after = (bool) $req->getVal('a');
			$gridView = (bool) $req->getVal('g');

			$rowsPerPage = $req->getVal('r');
			if ($rowsPerPage && is_numeric($rowsPerPage) && ctype_digit($rowsPerPage))
				$rowsPerPage = max(5, min(100, $rowsPerPage)); // limit it to something reasonable
			else
				$rowsPerPage = $this->defaultRowsPerPage;

			$copyVioFilter = (bool) $req->getVal('c');

			$title = 'Admin - Manage User Completed Images';
			$out->setHTMLTitle(wfMessage('pagetitle', $title));
			$out->setPageTitle('Manage User Completed Images');

			$tmpl = $this->genAdminForm($timeCutoff, $after, $rowsPerPage, $gridView, $copyVioFilter);
			$out->addHTML($tmpl);
		}
	}

	private function genAdminForm($timeCutoff, $after, $rowsPerPage, $gridView, $copyVioFilter) {
		global $wgCanonicalServer;

		$req = $this->getRequest();

		$columnsPerRow = $this->defaultColumnsPerRow;
		$totalImages = $rowsPerPage * ($gridView ? $columnsPerRow : 1);
		$basepage = '/' . Title::makeTitle(NS_SPECIAL, $this->specialPage);
		$html = '';
		$dateTimeTmp = Datetime::createFromFormat('YmdHis', $timeCutoff);
		$timePrintFormat = 'd M Y, H:i:s (T)';
		if (!$dateTimeTmp) { // Timestamp is not in MediaWiki format
			if (ctype_digit($timeCutoff)) { // Just interpret it as UNIX time.
				$timeCutoffPrint = Datetime::createFromFormat('U', $timeCutoff)->format($timePrintFormat);
			} else { // Not numeric? Ignore it and use current time
				$timeCutoff = wfTimestamp(TS_MW);
				$timeCutoffPrint = Datetime::createFromFormat('YmdHis', $timeCutoff)->format($timePrintFormat);
			}
		} else {
			$timeCutoffPrint = $dateTimeTmp->format($timePrintFormat);
		}
		$tDirection = $after ? '>' : '<';
		$tDirectionPrint = $after ? 'after' : 'before';
		$firstTime = $timeCutoff;
		$lastTime = $timeCutoff;

$html .= <<<HHTML
<div>
	Showing {$totalImages} results submitted {$tDirectionPrint} {$timeCutoffPrint}.<br />
	[[NAVIGATIONLINKS]]
</div>
HHTML;

		$whereClause = array();
		$whereClause[] = 'uci_timestamp ' . $tDirection . ' ' . $timeCutoff;

		// $whereClause['uci_is_deleted'] = '0';

		if ($copyVioFilter) {
			$whereClause['uci_copyright_checked'] = '1';
			$whereClause['uci_copyright_violates'] = '1';
		} elseif ($req->getVal('hideCprViolations')) {
			$whereClause['uci_copyright_violates'] = '0';
			// $whereClause['uci_copyright_checked'] = '1';
		} elseif ($req->getVal('onlyCprChecked')) {
			$whereClause['uci_copyright_checked'] = '1';
		}

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			'user_completed_images',
			'*',
			$whereClause,
			__METHOD__,
			array(
				'ORDER BY' => 'uci_timestamp ' . ($after ? 'ASC' : 'DESC'),
				'LIMIT' => $totalImages
			)) or die("Error, DB query failed.");

		if ($res->numRows() == 0) {
			$html .= '<br /><div align="center"><hr style="width: 80%; color: #e4e4e4; background-color: #e4e4e4; border: 1px; border-color: #e4e4e4; border-style: dashed;" /><br /></div>';
			$html .= 'No images found<br/><br/>';
		} else {
			$res_arr = array();
			foreach($res as $row)
				$resArr[] = $row;
			if ($after)
				$resArr = array_reverse($resArr);

			$first = true;
			$imageIndex = 0;
			$thumbnailW = $gridView ? 216 : 480;
			$thumbnailH = $gridView ? 216 : -1;

			if ($gridView) {
				$html .= '<br /><div align="center"><hr style="width: 80%; color: #e4e4e4; background-color: #e4e4e4; border: 1px; border-color: #e4e4e4; border-style: dashed;" /><br /></div>';
				$html .= '<table>';
			}

			foreach ($resArr as $row) {
				$imgName = 'User-Completed-Image-' . $row->uci_image_name;
				$imgTitle = Title::makeTitleSafe(NS_IMAGE, $imgName);
				$imgPageURL = '/' . $imgTitle->getPrefixedUrl();
				$articleName = str_replace('-', ' ', $row->uci_article_name);
				$articleURL = '/' . Title::makeTitleSafe(NS_MAIN, $row->uci_article_name)->getPartialUrl();
				$userName = $row->uci_user_text;
				$userURL = '/' . Title::makeTitleSafe(NS_USER, $userName);
				$userPrint = "<a href=\"$userURL\" rel=\"nofollow\">$userName</a>";
				$date = Datetime::createFromFormat('YmdHis', $row->uci_timestamp)->format($timePrintFormat);
				$deleteName = urlencode($imgName);
				$lastTime = $row->uci_timestamp;
				if ($first) {
					$first = false;
					$firstTime = $row->uci_timestamp;
				}
				$cprChecked = $row->uci_copyright_checked;
				$cprError = $row->uci_copyright_error;
				$cprViolates = $row->uci_copyright_violates;
				$cprSources = $row->uci_copyright_top_hits;

				$notfound = false;
				if (!$imgTitle) {
					$notfound = true;
				} else {
					$file = wfFindFile($imgTitle);
					if (!$file) {
						$notfound = true;
					}

					if ($req->getVal('updateDBFileURLs')) {
						$fileURL = $wgCanonicalServer . $file->getUrl();
						$dbw = wfGetDB(DB_MASTER);
						$dbw->update(
							'user_completed_images',
							array('uci_image_url' => $fileURL),
							array('uci_image_name' => $row->uci_image_name),
							__METHOD__);
						$dbw->commit();
					}
				}

				if ($gridView) {
					if ($imageIndex % $columnsPerRow == 0) {
						$html .= '<tr>';
					}
					$html .= '<td class="image-view-td">';
				}

				if ($notfound) {
$html .= <<<IMGHTML
<div id="image-{$imageIndex}" class="image-view">
	<br />
	<div align="center" class="image-view-elem image-not-found">
		<hr style="width: 80%; color: #e4e4e4; background-color: #e4e4e4; border: 1px; border-color: #e4e4e4; border-style: dashed;" /><br />
	</div>
	<div class="image-view-text image-view-not-found">
		An entry for the image <a href="{$imgPageURL}" rel="nofollow">{$imgTitle}</a> was found in the database, but not found on wikiHow. The image might recently have been deleted on wikiHow without updating the database. The image was uploaded in <a href="{$articleURL}" rel="nofollow">{$articleName}</a> on {$date} by {$userPrint} according to the database entry.
	</div>
</div>

IMGHTML;

					if ($gridView) {
						$html .= '</td>';
						if (($imageIndex+1) % $columnsPerRow == 0) {
							$html .= '</tr>';
						}
					}

					$imageIndex += 1;

					continue;
				}

				$thumb = $file->getThumbnail($thumbnailW, $thumbnailH, true, true);

				$thumbURL = $thumb->getUrl();

				$copyrightUnchecked = '';
				$copyrightText = '';
				$copyrightRefs = '';

				if (!$cprChecked) {
					$copyrightUnchecked = "<div class='image-view-copyright-unchecked'>";
					$copyrightUnchecked .= "Copyright not yet checked</div>";
				} else {
					$copyrightText = "<div class='image-view-copyright-vio'>";
					if ($cprError) {
						$copyrightText .= "Error during copyright check</div>";
					} elseif ($cprViolates) {
						$copyrightText .= "Copyright violation detected ({$row->uci_copyright_matches} matches)</div>";
						$copyrightRefs = "<div class='image-view-copyright-refs'>";
						if ($cprSources) {
							$cprRefData = json_decode($cprSources);
							$cprRefRefs = array();
							$cprRefImgs = array();
							foreach($cprRefData as $cprRefRow) {
								$cprRefRefs[] = $cprRefRow->ref;
								$cprRefImgs[] = $cprRefRow->img;
							}
							$copyrightRefs .= 'Pages: ';
							$i = 1;
							foreach($cprRefRefs as $cprRefRef) {
								$copyrightRefs .= "<a href='{$cprRefRef}'>{$i}</a> ";
								$i++;
							}
							$copyrightRefs .= '<br/>Images: ';
							$i = 1;
							foreach($cprRefImgs as $cprRefImg) {
								$copyrightRefs .= "<a href='{$cprRefImg}'>{$i}</a> ";
								$i++;
							}
						} else {
							$copyrightRefs = 'No sources registered.';
						}
						$copyrightRefs .= '</div>';
					} else {
						$copyrightText = '';
					}
					$copyrightUnchecked = '';
				}

				$flaggedDeletion = '';
				if ($row->uci_is_deleted) {
					$flaggedDeletion = '<div class="image-view-is-deleted">';
					$flaggedDeletion .= 'Flagged for deletion!</div>';
				}

$html .= <<<IMGHTML
<div id="image-{$imageIndex}" class="image-view">
	<br />
	<div align="center" class="image-view-elem">
		<hr style="width: 80%; color: #e4e4e4; background-color: #e4e4e4; border: 1px; border-color: #e4e4e4; border-style: dashed;" /><br />
	</div>
	<div align="center">
		<a href="{$imgPageURL}" rel="nofollow"><img src="{$thumbURL}" /></a>
	</div>
	<br />
	<div class="image-view-text">
		<a href="{$imgPageURL}" rel="nofollow">Image</a> uploaded in <a href="{$articleURL}" rel="nofollow">{$articleName}</a> on {$date} by {$userPrint}.
	</div>
	<div class="image-view-delete">(<a href="/Special:ImageUploadHandler?delete={$deleteName}" id="delete-image-{$imageIndex}" class="delete-link">Delete</a>)</div>
	{$copyrightUnchecked}
	{$copyrightText}
	{$copyrightRefs}
	{$flaggedDeletion}
</div>

IMGHTML;


				if ($gridView) {
					$html .= '</td>';
					if (($imageIndex+1) % $columnsPerRow == 0) {
						$html .= '</tr>';
					}
				}

				$imageIndex += 1;
			}

			if ($gridView) {
				$html .= '</table>';
			}

			$html .= '<br />';
		}

		$html .= '<div align="center"><hr style="width: 80%; color: #e4e4e4; background-color: #e4e4e4; border: 1px; border-color: #e4e4e4; border-style: dashed;" /><br /></div>';

		$html .= '<style>';
		if ($gridView) {
			$html .= '.image-view {display: inline;} ';
			$html .= '.image-view-elem {display: none;} ';
			$html .= '.image-view-text {font-size: 9px;} ';
			$html .= '.image-view-td {width: 228px; border: 1px dashed #d8d8d8; background-color: #f4f4f4;} ';
			$html .= '.image-view-delete {font-size: 13px; text-align: center;} ';
			$html .= '.image-view-not-found {color: #940000;} ';
		} else {
			$html .= '.image-view-delete {font-size: 16px;} ';
		}
		$html .= '.image-view-copyright-unchecked {font-size: 10px; color: #aaaaaa;} ';
		$html .= '.image-view-copyright-vio {font-size: 11px; color: #b40000;} ';
		$html .= '.image-view-copyright-refs {font-size: 10px; color: #666666;} ';
		$html .= '.image-view-is-deleted {font-size: 11px; color: #b40000;} ';
		$html .= '</style>';

		$prevPage = ($after || $req->getVal('t')) ? '<a href="' . $basepage . '?t=' . $firstTime . '&a=1&r=' . $rowsPerPage . '&g=' . $gridView . '&c=' . $copyVioFilter . '" rel="nofollow">Previous</a>' : 'Previous';

		$nextPage = (!$after || $req->getVal('t')) ? '<a href="' . $basepage . '?t=' . $lastTime . '&a=0&r=' . $rowsPerPage . '&g=' . $gridView . '&c=' . $copyVioFilter . '" rel="nofollow">Next</a>' : 'Next';

		$newestPage = ($after || $req->getVal('t')) ? '<a href="' . $basepage . '?r=' . $rowsPerPage . '&g=' . $gridView . '&c=' . $copyVioFilter . '" rel="nofollow">Newest</a>' : 'Newest';

		$oldestPage = (!$after || $req->getVal('t')) ? '<a href="' . $basepage . '?t=0&a=1&r=' . $rowsPerPage . '&g=' . $gridView . '&c=' . $copyVioFilter . '" rel="nofollow">Oldest</a>' : 'Oldest';

		$gridToggle = '<a href="' . $basepage . '?t=' . $timeCutoff . '&a=' . $after . '&r=' . $rowsPerPage . '&g=' . !((bool) $gridView) . '&c=' . $copyVioFilter . '" rel="nofollow">Toggle grid view</a>';

		$cvfToggle = '<a href="' . $basepage . '?t=' . $timeCutoff . '&a=' . $after . '&r=' . $rowsPerPage . '&g=' . $gridView . '&c=' . !((bool) $copyVioFilter) . '" rel="nofollow">Toggle copyvio filter</a>';


		$ppArrVals = array(10,25,50,100);
		$perPage = "<select id='ppsel'>\n";
		foreach ($ppArrVals as $ppVal) {
			$ppDisp = $ppVal * ($gridView ? $columnsPerRow : 1);
			$perPage .= "  <option value='$basepage?t=$timeCutoff&a=$after&r=$ppVal&g=$gridView' id='ppopt$ppVal'>$ppDisp per page</option>\n";
		}
		$perPage .= "</select>\n";

		$navLinks1 = join(' | ', array($prevPage,$nextPage,$newestPage,$oldestPage,$perPage,$gridToggle,$cvfToggle));
		$navLinks2 = join(' | ', array($prevPage,$nextPage,$newestPage,$oldestPage,$gridToggle,$cvfToggle));

		$html .= "$navLinks2\n";
		$html = str_replace('[[NAVIGATIONLINKS]]', $navLinks1, $html);

		$onDelete = "";
		if ($gridView) {
			$onDelete = "outerdiv.html('Deleted');";
			$onDelete .= "outertd.css('background-color', '#fafafa');";
			$onDelete .= "outerdiv.css('color', '#a4a4a4');";
			$onDelete .= "outertd.css('width','228px');";
			$onDelete .= "outertd.css('text-align', 'center');";
			$onDelete .= "outertd.css('vertical-align', 'middle');";
		} else {
			$onDelete = "outerdiv.hide();";
		}

		$html .=
<<<JSHTML
<script type="text/javascript">
var rowsPerPage = {$rowsPerPage};
var select = document.getElementById('ppsel');
var match = false;
for (var opt, i = 0; opt = select.options[i]; i++) {
	if (opt.id == "ppopt" + rowsPerPage) {
		select.selectedIndex = i;
		match = true;
	}
}
if (!match)
	select.selectedIndex = 0;
select.onchange = function() { window.location = this.value; }

$('.delete-link').click(function(e) {
	var url = $(this).attr('href');
	var outerdiv = $(this).parent().parent();
	var outertd = outerdiv.parent();
	$.get(
		url,
		function (data) {
			data = JSON.parse(data);
			if (data.hasOwnProperty('error')) {
				alert(data.error);
			} else if (data.hasOwnProperty('success')) {
				{$onDelete}
			} else {
				alert('Unknown or network error');
			}
			return false;
		}
	);
	return false;
});
</script>
JSHTML;

		return $html;
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}
}
