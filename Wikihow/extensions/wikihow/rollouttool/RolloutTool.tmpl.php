<form>
<span for="startDate">Start Date(I.E. 11/01/2014):</span><input type="text" id="startDate" name="startDate" /><br/>
<span for="duration">Rollout Duration(in seconds):</span><input type="text" id="duration" name="duration" /><br/>
Languages:
<?php foreach($languages as $lg) { ?>
	<input class="all" id="filter_<?php print $lg['languageCode']?>" type="checkbox" name="filter_<?php print $lg['languageCode'] ?>" value="<?php print $lg['languageCode']?>"> <?php print $lg['languageName'] ?>                                                                                                                                                             
<?php } ?>
<br/>
<input type="submit" value="Get List" />
</form>
