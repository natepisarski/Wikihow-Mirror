<div class="tool quiz">
<!--
	<div class="tool_help">
		<a href="/Use-the-wikiHow-Category-Checker" target="_blank">Learn how</a>
	</div>
-->
	<div class="tool_header mobile-header">
	
		<div class="quiz-body">
			<div class="spinner"></div>


			<div id="render-target">
				<!-- template is rendered here... -->
			</div>

			<div id="next-container" class="next-bar button-bar">
			<a href="#" id="next-batch" class="next button primary block arrow-right" data-event_action="next_category" role="button">
				<?= wfMessage('catch-skip')->text() ?>
				<div class="arrow"></div>
			</a>
		</div>
		</div>
	</div>
</div>

<!-- javascript template for rendering the article from JSON response -->
<div id="quiz-template" style="display:none;">
	<div class="question">
		<?= wfMessage('catch-question') ?> 
		{{#category.link}}
			<a href="{{category.link}}" aria-label="<?= wfMessage('aria_catguard_category')->showIfExists() ?> {{category.mTextform}}">
				<strong>&ldquo;{{category.mTextform}}&rdquo;?</strong>
			</a>
		{{/category.link}}
		{{^category.link}}
			<strong>&ldquo;{{category.mTextform}}&rdquo;?</strong>
		{{/category.link}}
	</div>
	
	<div class="answer-container">
		{{#articles}}
			<div class="answer" data-cat_slug="{{category.mDbkeyform}}" data-page_id="{{page_id}}" data-id="{{id}}" data-votes_up="{{votes_up}}" data-votes_down="{{votes_down}}" data-pqc_id="{{pqc_id}}">
				<div class="answer-text" data-article_id="{{page_id}}" data-assoc_id="{{id}}" data-category="{{category.mDbkeyform}}">
					<div class="toggle"></div>
					<a href="#" class="title" role="button" aria-label="<?= wfMessage('aria_catguard_article')->showIfExists() ?> {{page_title}}. <?= wfMessage('aria_catguard_article_see_details')->showIfExists() ?>">
						{{page_title}}
					</a>
					<div class="blurb">{{blurb}}</div>
				</div>
				<div class="answer-options">
					<a href="#"  class="no choice op-action">
						<div class="counter red" aria-hidden="true">{{votes_down}}</div>
						<i class="fa fa-times off" role="button" aria-label="<?= wfMessage('aria_catguard_reject')->showIfExists() ?> {{category.mTextform}}"></i>
						<i class="fa fa-times-circle-o on" aria-hidden="true"></i>
					</a>
					<a href="#" class="yes choice op-action">
						<div class="counter green" aria-hidden="true">{{votes_up}}</div>
						<i class="fa fa-check off" role="button" aria-label="<?= wfMessage('aria_catguard_accept')->showIfExists() ?> {{category.mTextform}}"></i>
						<i class="fa fa-check-circle-o on" aria-hidden="true"></i>
					</a>
				</div>
			</div>
		{{/articles}}
	</div>
</div>

<div id="limit-reached-template" style="display:none;">
	<div class="question anon-limit">
		<div class="message">
			<p><?= wfMessage('catch-msg-anon-limit')->text() ?></p>
			<a href="/Special:UserLogin?type=signup&amp;returnto=Special:CategoryGuardian{{params}}" class="button primary">
				<?= wfMessage('catch-sign-up')->text() ?>
			</a>
		</div>
	</div>
</div>

<div id="no-more-template" style="display:none;">
	<div id="no-more" class="alert danger icon">
		<?= wfMessage('catch-error-no-articles')->text() ?>
	</div>
</div>

<div id="skip_prompt">
	<div id="skip_prompt_inner">
		<a href="#" id="skip_prompt_x" class="fa fa-times"></a>
		<p id='firstP'><?=wfMessage('cg-skip-prompt-head')->text()?></p>
		<p id='secondP'><?=wfMessage('cg-skip-prompt-details')->text()?></p>
		<input id="skip_prompt_button' type="button" class="button primary" value="<?=wfMessage('cg-skip-prompt-button')->text()?>" />
	</div>
</div>
<?=$badgesTemplate?>
