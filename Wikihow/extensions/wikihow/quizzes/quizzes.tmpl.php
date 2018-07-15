<?=$quiz_ads_interstitial?>
<!-- lang stuff -->
<?php
	$langKeys = array('quiz-response-wrong', 'quiz-response-correct', 'quiz-done-button', 'quiz-results', 'take-more-quizzes', 'quiz_other_super');
	echo Wikihow_i18n::genJSMsgs($langKeys);
?>
<div id="timer"></div>
<?=$quiz_ads2?>
<div id="quiz_sidebar">	
	<? if ($quiz_found != '') { ?>
	<div class="quiz_sidebubble">
		<div class="sidebar_carrot"></div>
		<h4><?=wfMessage('quiz-header-found')?></h4>
		<table class="quiz_sidelist">
		<?=$quiz_found?>
		</table>
	</div>
	<? } ?>
	<?= $quiz_ads ?>
	<? if ($quiz_related != '') { ?>
	<div class="quiz_sidebubble">
		<h4><?=wfMessage('quiz-header-related')?></h4>
		<?=$quiz_related?>
	</div>
	<? } ?>
</div>

<h1 id="quiz_title"><?=$quiz_title.' '.wfMessage('quiz-suffix')?></h1>
<div id="the_quiz_bg"<?if ($quiz_bg) {?> style="background-image: url(<?=$quiz_bg?>)"<?}?>></div>
<div id="quiz_progress"><?=$quiz_progress?></div>
<div id="the_quiz">
	<div id="quiz_question"><?=$quiz_question?></div>
	<ol>
	<? foreach ($quiz_answers as $key=>$answer) { ?>
		<li id="answer_<?=$key?>"><div class="encircler"></div><p><?=chr($key+97)?></p><?=$answer?></li>
	<? } ?>
	</ol>
	<div id="quiz_response_box">
		<div id="quiz_shark"></div>
		<div id="quiz_response"></div>
		<div id="quiz_reason"></div>
	</div>
	<a href="#" id="quiz_submit" class="quiz_button"><?=wfMessage('quiz-submit-button')?></a>
	<a href="#" id="quiz_next" class="quiz_button"><?=wfMessage('quiz-next-button')?></a>
</div>

<?=$quiz_ads3?>

<div id="quiz_name"><?=$quiz_name?></div>
<div id="full_quiz"><?=$full_quiz?></div>
<div id="quiz_quips"><?=$quiz_quips?></div>
<br class="clearall" />
