<?=$css?>
<div class="qa_test sidebox" id="qa_test3">
	<h3>Questions &amp; Answers</h3>
	<? foreach ($qas as $key => $qa) { 
			$qa_hidden = $key > 4 ? ' qa_hidden' : '';
			if ($key == 5) print '<div id="qa_see_all"><a href="#">See all</a></div>';
	?>
		<div class='qa_block<?=$qa_hidden?>'>
			<div class="altblock"></div>
			<ul>
			 <div class="qa_test_hdr"><?=$qa['q']?></div>			
			<?=$qa['a']?>
			</ul>
		</div>
	<? } ?>
</div>
