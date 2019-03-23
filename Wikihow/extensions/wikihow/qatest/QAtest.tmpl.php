<div class="section qa_test sticky" id="qa_test1">
	<h2><span class="mw_headline">Questions &amp; Answers</span></h2>
	<div class="section_text">
	<? foreach ($qas as $qa) { ?>
		<div class="altblock"></div>
		<ul>
		 <div class="qa_test_hdr"><?=$qa['q']?></div>			
		<?=$qa['a']?>
		</ul>
	<? } ?>
	</div>
</div>
