<?=$css?>
<?=$js?>
<?=$nav?>

<div class='kbsectioncontainer'>
<h3 class='kbsection'>Bulk Actions</h3>
</div>

<div class='kbsectioncontents'>
<h4 class='kbsection' style='margin-top:0px'>Bulk Add</h4>
<div>
<p>Paste or type in comma-separated values (CSV) here. One entry per line.</p>
<p>Format for an entry:<br />
<strong>ArticleID,Topic,Phrase</strong><br />
Let ArticleID be 0 for topics not associated with an article.</p>
<p>Example:<br />
<strong>3115,jumping,how to jump<br />
0,basket weaving,how to basket weave underwater</strong></p><br />
<textarea id='kbbulkadd'></textarea>
</div>
<a class='button primary kbbuttonlarge' id='kbbulkaddbtn'>Add</a>

<h4 class='kbsection'>Bulk Update</h4>
<div>
<p>Paste or type in comma-separated values (CSV) here. One entry per line.</p>
<p>Format for an entry:<br />
<strong>KnowledgeBoxID,ArticleID,Topic,Phrase</strong><br />
This will update the article ID, topic and phrase associated with the given KnowledgeBox ID.</p>
<p>Example:<br />
<strong>1,3115,jumping,how to jump<br />
2,0,basket weaving,how to basket weave underwater</strong></p><br />
<textarea id='kbbulkupdate'></textarea>
</div>
<a class='button primary kbbuttonlarge' id='kbbulkupdatebtn'>Update</a>

<h4 class='kbsection'>Bulk Disable</h4>
<div>
<p>Paste or type in KnowledgeBox IDs here. One entry per line.</p><br />
<textarea id='kbbulkdisable'></textarea>
</div>
<a class='button primary kbbuttonlarge' id='kbbulkdisablebtn'>Disable</a>

<h4 class='kbsection'>Bulk Enable</h4>
<div>
<p>Paste or type in KnowledgeBox IDs here. One entry per line.</p><br />
<textarea id='kbbulkenable'></textarea>
</div>
<a class='button primary kbbuttonlarge' id='kbbulkenablebtn'>Enable</a>

<h4 class='kbsection'>Disable All</h4>
<div id='kbwarning'><strong>Warning</strong>: This deactivates ALL articles
in KnowledgeBox</div>
<a class='button primary kbbuttonlarge' id='kbdisableall' title='Disable all'>Disable all</a>
</div>

<div class='kbsectioncontainer'>
<h3 class='kbsection'>Topic List</h3>
</div>

<div class='kbsectioncontents'>

<div>
  Number of entries:
  <span id='kbtablecount'><?=count($kb_articles)?></span> 
  (<span id='kbactivecount'>?</span> active,
  <span id='kbinactivecount'>?</span> inactive)<br />
  Tip: You can sort by multiple columns.
  Try sorting by "Active", then shift+click "Submissions".<br />
  <a href='/Special:AdminKnowledgeBox?include_inactive=1'>Show inactive articles?</a>
</div>

<br />

<div id='kbexport' style='margin-bottom: 14px;width: 96px;text-align: center' class='button secondary'>
  <a href='/Special:AdminKnowledgeBox?action=export'>Download CSV</a>
</div>

<div class='kbtable'>
<table id='kbadmintable' class='tablesorter'>

<thead>
  <tr>
    <th class='empty-bottom'><span class='kbsmallheader'>Active</span></th>
    <th class='empty-bottom'>ID</th>
    <th class='empty-bottom'>Article ID</th>
    <th class='empty-bottom'>Article Title</th>
    <th class='empty-bottom'>Time created</th>
    <th class='empty-bottom'>Time modified</th>
    <th class='empty-bottom'>Topic</th>
    <th class='empty-bottom'>Phrase</th>
    <th class='empty-bottom'><span class='kbsmallheader'>Submissions</span></th>
    <th class='empty-bottom'>Action</th>
  </tr>
</thead>

<tbody>
<? foreach ($kb_articles as $row) { ?>
  <? $activeClass = $row['active'] ? 'kbactive' : 'kbinactive'; ?>
  <tr class='kbrow <?=$activeClass?>' kbid='<?=$row['id']?>'>
    <td class='kbrowactive'>
      <div class='kbitemstatic'>
        <?=$row['active'] ? 'Yes' : 'No'?>
      </div>
    </td>

    <td class='kbrowid'>
      <div class='kbitemstatic'>
        <?=$row['id']?>
      </div>
    </td>

    <td class='kbrowaid'>
      <div class='kbitemstatic'>
        <?=$row['aid']?>
      </div>
    </td>

    <td class='kbrowtitle'>
      <div class='kbitemstatic'>
<? if (!isset($row['title']) || $row['title'] === '') { ?>
      <span class='kbgray'>No live article</span>
<? } else { ?>
        <a href='<?=$row['url']?>'><?=$row['title']?></a>
<? } ?>
      </div>
    </td>

    <td class='kbrowtime'>
      <div class='kbitemstatic'>
        <?=$row['timestamp']?>
      </div>
    </td>

    <td class='kbrowmodified'>
      <div class='kbitemstatic'>
        <?=$row['modified']?>
      </div>
    </td>

    <td class='kbrowtopic'>
      <div class='kbitemstatic'>
        <?=$row['topic']?>
      </div>
      <div class='kbitemediting'>
        <input type='text' name='kbedittopic' value='<?=$row['topic']?>'>
      </div>
    </td>

    <td class='kbrowphrase'>
      <div class='kbitemstatic'>
        <?=$row['phrase']?>
      </div>
      <div class='kbitemediting'>
        <input type='text' name='kbeditphrase' value='<?=$row['phrase']?>'>
      </div>
    </td>

    <td class='kbrowsubcount'>
        <div class='kbitemstatic'>
<? if (!isset($row['submissions']) || $row['submissions'] === '') { ?>
            <span class='kbgray'>N/A</span>
<? } else { ?>
            <?=$row['submissions']?> (<a href='<?=$row['baseurl']?>/Special:KnowledgeBox?csvurl=<?=$row['aid']?>'>csv</a>)
<? } ?>
        </div>
    </td>

    <td class='kbrowaction'>
      <div class='kbitemstatic'>
        <a href='#' class='kbedit' title='Edit'>Edit</a>
        /
<? if ($row['active']) { ?>
        <a href='#' class='kbdisable' title='Disable'>X</a>
<? } else { ?>
        <a href='#' class='kbenable' title='Enable'>&#10004;</a>
<? } ?>
      </div>
      <div class='kbitemediting'>
        <a href='#' class='kbcancel' title='Cancel'>Cancel</a><br />
        <a href='#' class='kbsave' title='Save'>Save</a>
        <div class='kbtooltip'></div>
      </div>
    </td>
  </tr>
<? } ?>

  <tr class='kbdummyrow'>
    <td class='kbrowactive'>
      <div class='kbitemstatic'>

      </div>
    </td>

    <td class='kbrowid'>
      <div class='kbitemstatic'>

      </div>
    </td>

    <td class='kbrowaid'>
      <div class='kbitemstatic'>

      </div>
    </td>

    <td class='kbrowtitle' kbtitle='0'>
      <div class='kbitemstatic'><span class='kbgray'>No live article</span></div>
    </td>

    <td class='kbrowtitle' kbtitle='1'>
      <div class='kbitemstatic'>
        <a href='#'></a>
      </div>
    </td>

    <td class='kbrowtime'>
      <div class='kbitemstatic'>
        <span class='kbgray'>Timestamp</span>
      </div>
    </td>

    <td class='kbrowmodified'>
      <div class='kbitemstatic'>
        <span class='kbgray'>Timestamp</span>
      </div>
    </td>

    <td class='kbrowtopic'>
      <div class='kbitemstatic'>

      </div>
      <div class='kbitemediting'>
        <input type='text' name='kbedittopic' value=''>
      </div>
    </td>

    <td class='kbrowphrase'>
      <div class='kbitemstatic'>

      </div>
      <div class='kbitemediting'>
        <input type='text' name='kbeditphrase' value=''>
      </div>
    </td>

    <td class='kbrowsubcount'>
        <div class='kbitemstatic'>
            <span class='kbgray'>Submissions</span>
        </div>
    </td>

    <td class='kbrowaction'>
      <div class='kbitemstatic'>
        <a href='#' class='kbedit' title='Edit'>Edit</a>
        /
<? if ($row['active']) { ?>
        <a href='#' class='kbdisable' title='Disable'>X</a>
<? } else { ?>
        <a href='#' class='kbenable' title='Enable'>&#10004;</a>
<? } ?>
      </div>
      <div class='kbitemediting'>
        <a href='#' class='kbcancel' title='Cancel'>Cancel</a><br />
        <a href='#' class='kbsave' title='Save'>Save</a>
        <div class='kbtooltip'></div>
      </div>
    </td>
  </tr>

</tbody>
</table>
</div>

<table class='kbaddtable'>
  <tr id='kbrow-add'>
    <td class='kbrowaid'>
      <input type='text' placeholder='Article ID' name='kbnewaid' />
    </td>

    <td class='kbrowtopic'>
      <input type='text' placeholder='Topic' name='kbnewtopic' />
    </td>

    <td class='kbrowphrase'>
      <input type='text' placeholder='Phrase' name='kbnewphrase' />
    </td>

    <td class='kbrowaction'>
      <a class='button primary kbbuttonsmall' id='kbaddrow'>Add</a>
      <div class='kbtooltip'></div>
    </td>
  </tr>
</table>

</div>
