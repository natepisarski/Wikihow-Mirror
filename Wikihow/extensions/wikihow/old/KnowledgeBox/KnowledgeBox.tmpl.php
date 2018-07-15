<div class='section knowledgebox_section sticky' id='knowledgebox'>
    <h2>
        <span class='mw-headline kbheadline'>We could really use your help!</span>
    </h2>

    <div class='kbrow'>
<?
	// George 2015-03-31: We're hijacking the first KB box to point to RateTool.
	// This was done through Optimizely before, but I moved it here per Alissa's request.
	// George 2015-05-29: Hijacking disabled per PM's request.
	$hijack = false;
	foreach ($kbTopics as $kbTopic) {
		if ($hijack) {
?>
		<div class='kbcol kbhijack' id='<?=$kbTopic['aid']?>'>
<?
		} else {
?>
		<div class='kbcol' id='<?=$kbTopic['aid']?>'>
<?
		}
?>
            <div class='kbcol-inner'>
                <div class='kbgradients'></div>
                <div class='kbtop kbgreen'>
					<div class='kbtellus<?=$hijack ? ' kbnodisplay' : ''?>'>
                        Can you tell us about<br />
						<b class='kbttopic'><?=$kbTopic['topic']?>?</b>
					</div>
<? if ($hijack) { ?>
					<div class='kbhijackprompt kbtellus'>
						Can you help us<br /><b class='kbttopic'>rate articles?</b>
					</div>
<? } ?>
                </div>
                <div class='kbbot kbbotborder noselect'>
                    <div class='kbbotwrapper'>
                        <div class='kbbotleftwrapper'>
                            <div class='kbyes'>Yes</div>
                        </div>
                        <div class='kbbotrightwrapper'>
                            <div class='kbno'>No</div>
                        </div>
                    </div>
                    <div class='kbbotstripe kbbotborder'></div>
                </div>
                <div class='kbnodisplay kbctopic'><?=$kbTopic['topic']?></div>
                <div class='kbnodisplay kbcphrase'><?=$kbTopic['phrase']?></div>
                <div class='kbnodisplay'><img class='kbcthumb' src='<?=$kbTopic['thumburl']?>' alt='<?=$kbTopic['altText']?>' /></div>
                <div class='kbarrow'></div>
            </div>
        </div>

<?
		$hijack = false;
    }
?>
    </div>

    <div class='clearall'></div>

    <input type='hidden' id='kbthankstext' value='Thanks!'>
</div>

<div class='section kbedit_section' id='knowledgebox_edit'>
    <div class='kbeditheader'>
        <div class='kbeditimage'>
            <div class='kb-image-inner'>
                <div class='kb-placeholder-image'></div>
                <div class='kb-actual-image'></div>
            </div>
        </div>
        <div class='kbeditheadertext'>
            <div>
                <div class='kbheaderarrow-outer'></div>
                <div class='kbheaderarrow-inner'></div>
            </div>
            <div class='kb-headertext-inner'>
                <div class='kbrdhp'>Thanks for helping! Please tell us everything you know about</div>
                <div class='kbrdht' id='kbheaderphrase'>...</div>
            </div>
        </div>
        <div class='kbclose'><div class='kbrcw'></div><div class='kbrccw'></div></div>
    </div>

    <div class='kbformcontainer'>
        <form id='kbform' action='#'>
            <div contenteditable='true' disabled='true' class='inactive fancy_input' id='kbcontentbox'><span class='fancy_input_placeholder' id='kbplaceholder'><?=wfMessage('kb-tell-us')->plain()?></span></div>
            <div class='kbnewcontent'>
                <input type='hidden' id='kbaid' value='0'>
                <input type='hidden' id='kbtopic' value=''>
                <textarea id='kbcontent' class='active fancy_input kbpadmore' maxlength='5000'></textarea>
                <div class='kbtipsbox'>
                    <div class='kbtipstoggle'>
                        <div class='kbtipstogglebtn'><div class='kbtipshbar'></div><div class='kbtipsvbar'></div></div> Tips
                    </div>
                    <div class='kbtipsheader'><strong>Provide Details.</strong></div>
                    <div class='kbtipsdetails'>
                        Please be as detailed as possible in your explanation. Don't worry about formatting! We'll take care of it.
                        For example:<br />
                        <strong>Don't say</strong>: <em>Eat more fats.</em><br />
                        <strong>Do say</strong>: <em>Add fats with some nutritional value to the foods you already eat. Try olive oil, butter, avocado, and mayonnaise.</em>
                    </div>
                </div>
                <div class='kbformbot'>
                    <div class='kbformbot-left'>
						<input placeholder="(Optional) Enter email address for article updates" id='kbemail' class='kbinput'>
						<input placeholder="(Optional) Enter your name" id='kbname' class='kbinput'>
                    </div>
                    <div class='kbformbot-right'>
                        <a id='kbadd' class='button primary op-action'>Submit</a>
                        <div class='kbspinner' id='kbwaiting'><img src='<?=wfGetPad('/extensions/wikihow/rotate.gif')?>' alt='' /></div>
                    </div>
                </div>
            </div>
            <div class='clearall'></div>
        </form>
    </div>
</div>
