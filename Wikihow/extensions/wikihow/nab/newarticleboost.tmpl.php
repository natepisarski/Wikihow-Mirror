<div id="nab-article-container">
<div class="arrow_box">
	<form action="/Special:NewArticleBoost" name="nap_form" id="nap_form" method="post">
		<a class="button secondary" id="nap_promote" data-event_action='promote'>Promote</a>
		<a class="button secondary" id="nap_star" data-event_action='rising_star'>&nbsp;</a>
		<br />
		<a class="button secondary" id="nap_demote_btn" data-event_action='demote'>Demote</a><br />
		<input type="hidden" id="nap_demote" name="nap_demote" value="" />
		<a class="button secondary" id="nap_skip_btn" data-event_action='skip'>Skip</a>
		<input type="hidden" id="nap_skip" name="nap_skip" value="" />
		<br />
		<select id="nap_delete" name='param1_param1' class="button secondary">
			<option VALUE='delete'>NFD...</option>
			<option VALUE='acc'>Accuracy issues</option>
			<option VALUE='adv'>Advertisement</option>
			<option VALUE='cha'>Character article - well below standards</option>
			<option VALUE='dan'>Extremely dangerous, reckless, and irrational</option>
			<option VALUE='dru'>Recreational drug based</option>
			<option VALUE='hat'>Hate based or racist content</option>
			<option VALUE='imp'>Impossible instructions</option>
			<option VALUE='inc'>Incomplete</option>
			<option VALUE='jok'>Joke topic</option>
			<option VALUE='mea'>Mean spirited activity</option>
			<option VALUE='not'>Not a how-to</option>
			<option VALUE='pol'>Political opinion</option>
			<option VALUE='pot'>Potty humor</option>
			<option VALUE='sar'>Sarcastic / Reverse logic</option>
			<option VALUE='sex'>Sexually charged content</option>
			<option VALUE='soc'>Societal instructions</option>
			<option VALUE='ill'>Universally illegal</option>
			<option VALUE='van'>Vanity page</option>
		</select>
		<input type="hidden" name="nap_submit" id="nap_submit" value="" />
		<input type="hidden" name="template1_nfd" id="template1_nfd" value="" />
		<input type="hidden" name="wikitext_template" id="wikitext_template" value="" />
		<input type="hidden" name="template3_merge" id="template3_merge" />
		<input type="hidden" name="param3_param1" id="param3_param1" />
		<input type='hidden' name='cb_risingstar' id="cb_risingstar" />
		<input type='hidden' name='target' value='<?= htmlspecialchars($titleText) ?>'/>
		<input type='hidden' name='page' value='<?= $articleId ?>'/>
		<input type='hidden' name='prevuser' value='<?= $userName ?>'/>
		<input type='hidden' name='maxrcid' value='<?= $maxrcid ?>'/>
		<input type="hidden" name="sortValue" value="<?= $sortValue ?>" />
		<input type="hidden" name="sortOrder" value="<?= $sortOrder ?>" />
		<input type="hidden" name="low" value="<?= $low ?>" />
		<input type="hidden" name="old" value="<?= $old ?>" />
		<input type="hidden" name="nextNabUrl" id="nextNabUrl" value="<?= $nextNabUrl ?>" />
		<input type="hidden" name="nextNabTitle" id="nextNabTitle" value="<?= $nextNabTitle ?>" />
	</form>
</div>

<div id="nap_header" class="section_text">
	<h5>New Article Boost</h5>
	<h1><a href="<?= $fullUrl ?>"><?= $articleTitle ?></a></h1>
	<div id="author_info">
		<?= $authorInfo ?> <br />
		Score: <?= $score ?>
		<a style="float:right;" href="/Special:NewArticleBoost?sortOrder=<?=$sortOrder?>&sortValue=<?=$sortValue?>&low=<?=$low?>">&larr; Back to NAB</a>
	</div>
	<?= $lockedMsg ?>
	<?= $patrolledMsg ?>
</div>
<div class='minor_section'>
	<h2><?= wfMessage('nap_similarresults')?><span class="nap_expand"> </span></h2>
	<div class='nap_body section_text'>
		<?php if (count($matches) > 0): ?>
			<?= wfMessage('nap_already-related-topics') ?>
			<table id="nap_duplicates" cellspacing="0" cellpadding="0">
				<?php foreach ($matches as $match): ?>
					<tr class=" <?= $match['count']%2?'even':'odd'; ?>">
						<td style="width:473px;"><?= $match['relatedLink'] ?></td>
						<td class="nap_duplicates_actions">
							<a href='#' onclick='window.WH.nab.addTemplateNfdDup("<?= $match['safeTitle'] ?>"); return false;' class="button secondary top_link" data-event_action='nfd' data-assoc_id='<?=$match['relatedId']?>'><?= wfMessage('nap_deleteduplicate') ?></a>
						</td>
					</tr>
				<? endforeach ?>
			</table>
		<? else: ?>
			<?= wfMessage('nap_no-related-topics') ?>
		<? endif; ?>
	</div>
</div>

<div class='minor_section'>
	<a name='article' id='anchor-article'></a>
	<h2><a href="<?= $fullUrl ?>" target="_blank"><?= wfMessage('nap_articlepreview')?></a>
		<span class="nap_expand"> </span>
		<a href="<?= $editUrl ?>" target="new" class="button secondary top_link" style="float:right;" data-event_action='edit' data-edit_type='normal_edit'><?= $externalLinkImg?> <?= wfMessage('edit')?></a>
		<a href="<?= $fullUrl ?>?action=history" target="new" class="button secondary top_link" style="float:right;"><?= $externalLinkImg ?> <?= wfMessage('history') ?></a>
		<a href="<?= $talkUrl ?>" target="new" class="button secondary top_link" style="float:right;"><?= $externalLinkImg ?> <?= wfMessage('discuss') ?></a>
		<input id='editButton' type='button' class='button secondary top_link editButton' name='wpEdit' value='Quick Edit' onclick='window.WH.nab.editClick("<?= $quickEditUrl ?>");' data-event_action='edit' data-edit_type='quick_edit_top'/>
	</h2>
	<div class='nap_body'>
		<div id='article_contents'>
			<?= $articleHtml ?>
		</div>
		<div id='quickedit_contents'></div>
		<input id='editButton' type='button' class='button secondary editButton' name='wpEdit' value='Quick Edit' onclick='window.WH.nab.editClick("<?= $quickEditUrl ?>");' data-event_action='edit' data-edit_type='quick_edit_bottom'/>
	</div>
</div>

<div class='minor_section'>
	<a name='talk' id='anchor-talk'></a>
	<h2><a href="<?= $talkUrl ?>" target="_blank"><?= wfMessage('nap_discussion') ?></a>
		<span class="nap_expand"> </span>
	</h2>
	<div class='nap_body section_text'>
		<div id='disc_page'>
			<?= $discText ?>
			<?= $commentForm ?>
		</div>
	</div>
</div>

<div class='minor_section'>
	<a name='user' id='anchor-user'></a>
	<h2><a href="<?= $userTalkUrl?>" target="_blank"><?= wfMessage('nap_userinfo') ?></a><span class="nap_expand"> </span></h2>
	<div class='nap_body section_text' id="nap_user_talk">
		<?= $userInfo ?>
		<?= $userMsg ?>
		<?= $userTalkComment ?>
	</div>
</div>

<script type='text/javascript'>

	// This is not in newarticleboost.js because there would be a delay if we put it there
	$(".wh_block:first").remove();
	$("#nap_header").next().css("margin-top", ($("#nap_header").height() + 58) + "px");

	// Handlers for expand/contract arrows
	(function ($) {
		$('.nap_expand').click(function() {
			var thisSpan = $(this);
			var body = thisSpan.parent().next();
			if (body.css('display') != 'none') {
				var oldHeight = body.height();
				body.slideUp( 'slow',
					function () {
						thisSpan.addClass("collapsed");
					});
			} else {
				body.slideDown( 'slow',
					function () {
						thisSpan.removeClass("collapsed");
					});
			}
			return false;
		});
	})(jQuery);

</script>

</div>
