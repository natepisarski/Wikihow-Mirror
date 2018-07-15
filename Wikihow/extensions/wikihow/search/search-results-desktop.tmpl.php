<?
if ($q == null):
	return;
endif;
?>

<? if (count($results) == 0): ?>
	<h2 class="sr_noresults_for"><?= wfMessage('lsearch_no_results_for', $enc_q)->inContentLanguage()->text() ?></h2>
	<script type="text/javascript">
			setTimeout(function() {
				$("#bubble_search .search_box, #bubble_search .search_button").css("border", "2px solid white");
				setTimeout(function() {
					$("#bubble_search .search_box, #bubble_search .search_button").css('border', 'none');
				}, 3000);
			}, 1000);
	</script>
<? endif; ?>

<? if ($suggestionLink): ?>
	<div class="sr_suggest"><?= wfMessage('lsearch_suggestion', $suggestionLink)->text() ?></div>
<? endif; ?>

<?= $ads; ?>

<div id="search_adcontainer1"></div>
<div id="search_adblock_top" class="search_adblock"></div>

<? if (count($results) == 0): ?>
	<div class="sr_noresults"><?= wfMessage('lsearch_desktop_noresults', $enc_q) ?></div>
	<div id='searchresults_footer'><br /></div>
	<? return; ?>
<? endif; ?>

<div id='searchresults_list' class='wh_block'>

	<?
	$noImgCount = 0;
	foreach($results as $i => $result):

		if (empty($result['img_thumb_250'])) {
			$result['img_thumb_250'] = $noImgCount++ % 2 == 0 ?
				$no_img_green : $no_img_blue;
		}
		if ($i == 5) {
			echo '<div id="search_adcontainer2"></div>';
			echo '<div id="search_adblock_middle" class="search_adblock"></div>';
		}
	?>

		<div class="result <?=$i == 0 || $i == 3 ? "result_margin" : "";?>">
			<? if (!$result['is_category']): ?>
				<div class='result_thumb'>
				<? if (!empty($result['img_thumb_250'])): ?>
					<img src="<?= $result['img_thumb_250'] ?>" />
				<? endif; ?>
				</div>
			<? else: ?>
				<div class='result_thumb cat_thumb'><img src="<?= $result['img_thumb_250'] ? $result['img_thumb_250'] : $noImg ?>" /></div>
			<? endif; ?>

			<?
			$url = $result['url'];
			if (!preg_match('@^http:@', $url)) {
				$url = $BASE_URL . '/' . $url;
			}
			?>
			<div class="result_data">
			<? if ($result['has_supplement']): ?>
				<? if (!$result['is_category']): ?>
					<a href="<?= $url ?>" class="result_link"><?= $result['title_match'] ?></a>
				<? else: ?>
					<a href="<?= $url ?>" class="result_link"><?= wfMessage('lsearch_article_category', $result['title_match']) ?></a>
				<? endif; ?>
				<div class="result_data_divider"></div>
				<ul class="search_results_stats">
					<li class="sr_view"><span class="sp_circle sp_views_icon"></span>
						<?=wfMessage('lsearch_views', number_format($result['popularity']))->text();?>
					</li>
					<? if ($result['verified']): ?>
						<li class="sr_verif"><span class="sp_verif_icon"></span>
							<?= SocialProofStats::getIntroMessage($result['verified']) ?>
						</li>
					<? else: ?>
						<li>&nbsp;</li>
					<? endif ?>
					<li class="sr_updated"><span class="sp_circle sp_updated_icon"></span>
						<?=wfMessage('lsearch_last_updated_desktop', wfTimeAgo(wfTimestamp(TS_UNIX, $result['timestamp']), true));?>
					</li>
				</ul>
			<? else: ?>
				<a href="<?= $url ?>" class="result_link"><?= $result['title_match'] ?></a>
			<? endif; // has_supplement ?>
			<? // Sherlock-form ?>
			<?= EasyTemplate::html('sherlock-form', array("index" => $i + $first, "result" => $result)); ?>
			</div>


		</div>
	<? endforeach; ?>
	<div id="search_adcontainer3"></div>
	<div id="search_adblock_bottom" class="search_adblock"></div>
</div>

<?
if (($total > $start + $max_results
		&& $last == $start + $max_results)
	|| $start >= $max_results): ?>

	<div id='searchresults_footer'>
		<?=$next_button.$prev_button?>
		<div class="sr_foot_results"><?= wfMessage('lsearch_results_range', $first, $last, number_format($total)) ?></div>
		<div class="sr_text"><?= wfMessage('lsearch_mediawiki', $specialPageURL . "?search=" . urlencode($q)) ?></div>
	</div>
<? endif; ?>
