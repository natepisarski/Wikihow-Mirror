<div class="slider" id="hp_container">
    <ul>
        <?php foreach($items as $item): ?>
        <div id="hp_top_<?= $item->itemNum ?>">
            <li><a href="<?= $item->url ?>"></a>
                <div class="hp_text" title="<?= $item->text?>"></div>
                <img class="hp_image" src="<?= $item->imagePath ?>" />
            </li>
        </div>
        <?php endforeach; ?>
    </ul>
	<p class="hp_tag"><?= wfMessage("main_tag_mobile")->text()?></p>
</div>
<noscript>
<style>
.search .cse_sa {
  background-size: 28px 25px !important;
}
#header_logo {
	display:block;
	margin-top:10px;
}
#search_oversearch #cse-search-box {
	margin-left:10px;
}
.wh_search .cse_q, #cse-search-box .cse_q {
	margin-bottom:5px;
	background-color: #FFF;
	width:75%;
	float:left;
	color: #000;
	-moz-border-radius: 5px 0 0 5px;
	-webkit-border-radius: 5px 0 0 5px;
	-khtml-border-radius: 5px 0 0 5px;
	border-radius: 5px 0 0 5px;
}
#search_oversearch .cse_sa {
	background-color:#6d855e;
	background-size: 32px;
	width:31px;
	height: 29px;
	margin:0;
	position:static;
	float:left;
	background-image: url(/extensions/wikihow/mobile/images/white_mag.png);
	-moz-border-radius: 0 5px 5px 0;
	-webkit-border-radius: 0 5px 5px 0;
	-khtml-border-radius: 0 5px 5px 0;
	border-radius: 0 5px 5px 0;
}
#search_oversearch {
	display:inline-block;
	height:40px;
	margin-top:40px;
	width:100%;
}
#mw-mf-main-menu-button {
	display:none;
	visibility:hidden;
}
.header .search {
	display:none;
}
.search{
	margin:0 -5px 10px -5px;
}
#search_footer .cse_q {
	color: #FFF;
	background-color: #B3CE9C;
	float:none;
	width:85%;
	-moz-border-radius: 5px 5px 5px 5px;
	-webkit-border-radius: 5px 5px 5px 5px;
	-khtml-border-radius: 5px 5px 5px 5px;
	border-radius: 5px 5px 5px 5px;
}
#search_footer .cse_sa {
	background-image: url(/extensions/wikihow/mobile/images/white_mag.png);
	background-size:24px;
	width:22px;
	height:22px;
}
@media only screen and (max-width: 260px) {
.wh_search .cse_q, #cse-search-box .cse_q {
	width:70%;
}
}
@media only screen and (max-width: 200px) {
.wh_search .cse_q, #cse-search-box .cse_q {
	width:60%;
}
}
@media only screen and (max-width: 150px) {
.wh_search .cse_q, #cse-search-box .cse_q {
	width:50%;
}
}

</style>
</noscript>

</div>
