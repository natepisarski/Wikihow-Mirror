<style type="text/css">
#searchad_lnk {
	text-decoration: none;
	color: #545454;
	display: block;
	height: 90px;
    width: 728px;
}

#searchad {
	cursor: pointer;
	width: 728px;
	height: 90px;
	background-position: 0 0;
	background-repeat: no-repeat;
	background-size: contain;
}

#searchad_text {
	font-family: Helvetica,arial,sans-serif;
	font-size: 28px;
	line-height: 94px;
	margin-left: 358px;
	white-space: nowrap;
	overflow: hidden;
	width: 369px;
}
.searchad_bnr_C #searchad_text { width: 366px; }

.searchad_bnr_A {
	background-image: url(//www.wikihow.com/extensions/wikihow/SearchAd/images/searchad_bnr_A.png);
}

.searchad_bnr_B {
	background-image: url(//www.wikihow.com/extensions/wikihow/SearchAd/images/searchad_bnr_B.png);
}

.searchad_bnr_C {
	background-image: url(//www.wikihow.com/extensions/wikihow/SearchAd/images/searchad_bnr_C.png);
}

.searchad_bnr_D {
	background-image: url(//www.wikihow.com/extensions/wikihow/SearchAd/images/searchad_bnr_D.png);
}
</style>
<a href="<?=$link?>" id="searchad_lnk" target="_blank">
<div id="searchad" class="searchad_bnr_<?=$version?>">
	<div id="searchad_text">
		<?=wfMessage('searchad_wh2')->text().' '.$title?>
	</div>
</div>
</a>
