<div name="userlogin" class="userlogin">

	<h3><?= $hdr_txt ?></h3>
	<div id='social-login-navbar' data-return-to='<?= htmlspecialchars($return_to, ENT_QUOTES) ?>'>
		<div id="fb_connect<?=$suffix?>"><a id="fb_login<?=$suffix?>" href="#" role="button" class="ulb_button loading" aria-label="<?=wfMessage('aria_facebook_login')->showIfExists()?>"><span class="ulb_loading_indicator"></span><span class="ulb_icon"></span><span class="ulb_label"><?=wfMessage('ulb-btn-fb')?></span><span class="ulb_status"><?=wfMessage('ulb-btn-loading')?></span></a></div>
		<div id="gplus_connect<?=$suffix?>"><a id="gplus_login<?=$suffix?>" href="#" role="button" class="ulb_button loading"  aria-label="<?=wfMessage('aria_google_login')->showIfExists()?>"><span class="ulb_loading_indicator"></span><span class="ulb_icon"></span><span class="ulb_label"><?=wfMessage('ulb-btn-gplus')?></span><span class="ulb_status"><?=wfMessage('ulb-btn-loading')?></span></a></div>
		<?php if (CivicLogin::isEnabled()): ?>
			<div id="civic_connect<?=$suffix?>"><a id="civic_login<?=$suffix?>" href="#" role="button" class="ulb_button loading"  aria-label="<?=wfMessage('aria_civic_login')->showIfExists()?>"><span class="ulb_loading_indicator"></span><span class="ulb_icon"></span><span class="ulb_label"><?=wfMessage('ulb-btn-civic')?></span><span class="ulb_status"><?=wfMessage('ulb-btn-loading')?></span></a></div>
		<?php endif ?>
	</div>

	<div>
		<a href="<?= htmlspecialchars($wH_button_link, ENT_QUOTES) ?>" role="button" id="wh_login<?=$suffix?>" class="ulb_button <?=$is_login?>" aria-label="<?= $hdr_txt ?>"><span class="ulb_icon"></span><span class="ulb_label"><?=$wh_txt?></span></a>
	</div>

	<div class="userlogin_links">
		<?= $bottom_txt_1 ?>
		<a href="<?= htmlspecialchars($wH_text_link, ENT_QUOTES) ?>"><?= $bottom_txt_2 ?></a>
		<?= $privacy_link ?>
	</div>
</div>
