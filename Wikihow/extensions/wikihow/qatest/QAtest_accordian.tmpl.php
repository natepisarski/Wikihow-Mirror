<?=$css?>
<?=$fa?>
<div class="section qa_test sticky" id="qa_test2">
   <h2><span class="mw_headline">Questions &amp; Answers</span></h2>
   <div class="section_text">
   <? foreach ($qas as $k => $qa) { ?>
	   <div class="altblock"></div>
	   <ul>
	   <div class="qa_test_hdr"><i class="fa fa-plus-square-o"></i><?=$qa['q']?></div>
	   <div class="qa_test_ans2"><?=$qa['a']?></div>
	   <? if (($k+1)  < count($qas)) { ?><div class="qa_test_bottom"></div><? } ?>
	   </ul>
   <? } ?>
   </div>
</div>