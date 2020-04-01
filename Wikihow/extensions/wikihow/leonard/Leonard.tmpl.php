<form action="/Special:Leonard?act=getTitles" method="post" enctype="multipart/form-data">
<br/>
Upload AdWords CSV File: 
<input type="file"
                   name="csvfile"
                   onchange="jQuery('#image-upload-input-button').prop('disabled', false);
                             jQuery('#image-upload-form').submit();"
                   id="csvfile_id">
<br/>
group titles: <input type="checkbox" name="groupTitles" id="groupTitlesId" value="true">
<br/>                 
Avg monthly searches filter <input type="text" name="thresh" value="<?php echo Leonard::AVG_GAD_KEYWORD_MONTHLY_SEARCH_THRESH ?>" id="thresh_id" />
<br/>
<input type="submit"/>
</form>

<b>Usage</b><br/>
<ol>
<li>Goto Google AdWords -> Tools -> Keyword Planner</li>
<li>Click on "Search for new keyword and ad group ideas. e.g. dialysis"</li>
<li>Enter seed keyword in "product or service" field.</li>
<li>Under keyword options select "Only show ideas closely related to my search terms".</li>
<li>Get Ideas</li>
<li>Select keyword ideas tab</li>
<li>Do not change default sort (by relevancy). </li>
<li>Observe results</li>
<li>Use different seed keyword if needed. If needed trun off "Only show ideas closely related to my search terms".
<li>Download results as Excel CSV and save it.</li>
<li>Upload saved CSV to Leonard (this page :)</li>
<li>Save created title suggestions CSV and open it in excel or equivalent app</li>
</ol>
Note: We filter out all keywords having average monthly search count below value mentioned in field "Avg monthly searches filter", default is <?php echo Leonard::AVG_GAD_KEYWORD_MONTHLY_SEARCH_THRESH ?>.
Due to this you may see some keywords in the ad words CSV but not in the suggested titles. Lowering this value might result in explosion of titles.
