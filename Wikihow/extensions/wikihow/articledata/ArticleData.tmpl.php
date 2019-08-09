<div id='ct_a'><?=$ct_a?></div>

<? if ($ct_a == 'cats') { ?>
<label for="ct_cat"><b>Enter Category URL</b> </label><input type="text" class="ct_cat" name="ct_cat" id="ct_data"/>
<? } elseif ($ct_a == 'ids') { ?>
<label for="ct_urls"><b>Enter Article  IDs</b> </label>
<? } else { ?>
<label for="ct_urls"><b>Enter Article  URLs</b> </label>
<? } ?>
<div>
<textarea class="ct_urls" name="ct_urls" id="ct_data"/></textarea>
</div>

<? if ($ct_a != "ids") { ?>
<div class='ct_row'>
<label for="ct_slow"><b>Include Slower Data (alt methods, images and article size)</b> </label><input type="checkbox" name="ct_slow" id="ct_slow" />
</div>
<div class='ct_row'>
<label for="ct_introonly"><b>Intro Image only</b> </label><input type="checkbox" name="ct_introonly" id="ct_introonly" />
</div>
<? } ?>
<div class='ct_row'>
<input type='button' id='ct_button' value='Get File'></input>
<input type='button' id='ct_button_html' value='Get Html'></input>
</div>
<div id='ct_html'></div>
