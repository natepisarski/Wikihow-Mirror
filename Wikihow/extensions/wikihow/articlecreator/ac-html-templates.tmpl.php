<script type="text/html" id="ac_edit_view_tmpl">
<div class='clearall'/>
<div id='ac_edit_form'>
<textarea id='ac_edit_text' />
<br />
<a class='button secondary' id='ac_delete'>Delete</a>
<a class='button primary' id='ac_save'>Save</a>
<a class='button secondary' id='ac_cancel'>Cancel</a>
<div class='clearall'/>
</div>
</script>

<script type="text/html" id="ac_publish_error_tmpl">
	<div class='ac_error_message'>$txt</div>
	<br>
	<?=$copyWikitextMsg?>
    <textarea class='ac_wikitext'>$wikitext</textarea>
</script>

<script type="text/html" id="ac_method_tmpl">
	<li class='ac_method section' id='$id'>
		<div class='ac_method_info'>
			<div class='ac_method_controls'>
				<span class='ac_method_tools'>		 			
					<a class='ac_reorder_method'></a>  
					<a class='ac_remove_method'></a>
				</span>
				<h3>
					<div class="altblock"></div>
					<span class='ac_method_title'>
						<span class='ac_method_prefix'>Method 1</span>
						<span class='ac_method_name'></span>
					</span>
				</h3>
			</div>
			<div class='ac_method_editor'>
				<input type='text' class='ac_edit_method_text' placeholder='<?=$nameMethodPlaceholder?>'></input>				
			</div>
		</div>
		<h2><span class='mw-headline'>Steps</span><span class='ac_desc'><?=$desc?></span></h2>
		<div class='ac_editor'>
			<div class='ac_content' class='ui-draggable'>
				<ul class='ac_lis'></ul>
			</div>
			
			<div class='ac_li_adder'>
				<div class="step_num">1</div>
				<textarea placeholder='<?=$addStepPlaceholder?>' class='ac_new_li'></textarea>
				<br />
				<a class='button secondary ac_add_li'><?=$buttonTxt?></a>
				<div class='clearall'></div>
			</div>
		</div>
	</li>
</script>

<script type="text/html" id="ac_abstract_confirm_tmpl">
    <div>$txt</div>
	<div class='ac_confirm_buttons'>
		<a class='button secondary ac_yes'>Yes</a>		
		<a class='button primary ac_no'>No</a>
	</div>				
</script>

<script type="text/html" id="ac_abstract_alert_tmpl">
    <div>$txt</div>
	<div class='ac_confirm_buttons'>
		<a class='button primary ac_ok'>Okay</a>		
	</div>				
</script>
