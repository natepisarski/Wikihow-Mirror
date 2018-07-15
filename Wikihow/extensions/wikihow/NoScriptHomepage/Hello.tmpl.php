<?=$css?>
<div id="nojs_header"></div>
<div class='top_section'>
<h3><?=$topMessage?></h3>
</div>
<div id="search">
	<form action="/wikiHowTo" _lpchecked="1">
		<div class="nojssb">
			<div id="nojs_input_container">
				<input id="nojssb_input" maxlength="2048" name="search" autocapitalize="off" autocomplete="off" autocorrect="off"  type="search" value="" aria-label="Search" aria-haspopup="false" role="combobox" aria-autocomplete="both" dir="ltr" spellcheck="false" ph="<?=$howTo ?>" placeholder="<?=$howTo ?>">
			</div>
			<button  aria-label="wikiHow Search" id="nojssb_btn" type="submit"> 
				<div class="s_text"><?=$searchText?></div>
				<div class="s_icon"></div> 
			</button>
		</div>
	</form>
</div>
<ul id="nojs_fa">
	<li id="nojs_fa_first" class='nojs_fa_item nojs_fa_odd'><div class="nojs_fa_cell"><?=$faSection?></div></li>
	<? for ( $i = 0; $i < count( $fas ); $i++ ) {
		if ( $i % 2 == 0 ) { ?>
			<li class='nojs_fa_item'>
		<?php } else { ?>
			<li class='nojs_fa_item nojs_fa_odd'>
		<?php } ?>
		<div class="nojs_fa_cell"><?=$fas[$i] ?></div></li>
	<?php } ?>
</ul>
<div id="nojs_surprise">
<a href="/Special:Randomizer" id="nojs_slink" title="test" class="button primary"><?=$surpriseMe ?></a>
</div>
<div id="nojs_b_search_bg">
	<div id="nojs_b_search">
		<form action="/wikiHowTo" _lpchecked="1">
			<div class="nojssb">
				<div id="nojs_b_input_container">
					<input id="nojssb_b_input" maxlength="2048" name="search" autocapitalize="off" autocomplete="off" autocorrect="off"  type="search" value="" aria-label="Search" aria-haspopup="false" role="combobox" aria-autocomplete="both" dir="ltr" spellcheck="false" ph="<?=$howTo ?>" placeholder="<?=$howTo ?>">
				</div>
				<button  aria-label="wikiHow Search" id="nojssb_b_btn" type="submit"> 
					<div class="s_text"><?=$searchText?></div>
					<div class="s_icon"></div> 
				</button>
			</div>
		</form>
	</div>
</div>
