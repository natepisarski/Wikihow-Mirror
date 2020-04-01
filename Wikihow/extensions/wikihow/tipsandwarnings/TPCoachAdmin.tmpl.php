<?=$css?>
<div id="article_tabs">
	<div id="tabs">
	  <a href="#" class='tpc-tab' id="tab-scores" title="scores">Scores</a>
	  <a href="#" class='tpc-tab' id="tab-tests" title="tests">Tests</a>
	</div>
</div>
<div id="article_tabs_line"></div>
<div id="content-tests">
	<div class="tpc_newtest_line">
		<h3>Add new test:</h3>
	</div>
	<form action="">
		<div class="tpc_newtest_line">
			<label for="page">Page:</label>
			<input type="text" id="tpc_input_page" style="width:200px;"/>
			(Article name or ID)
		</div>
		<div class="tpc_newtest_line">
			<label for="tip">Tip:</label>
			<input type="text" id="tpc_input_tip" style="width:560px;"/>
		</div>
		<div class="tpc_newtest_line">
			<label for="tip">Fail Message:</label>
			<input type="text" id="tpc_input_fail_message" maxlength="320" style="width:500px;"/>
		</div>
		<div class="tpc_newtest_line">
			<label for="tip">Success Message:</label>
			<input type="text" id="tpc_input_success_message" maxlength="320" style="width:500px;"/>
		</div>
		<div class="tpc_newtest_line">
			<label for="type">Difficulty:</label>
			<select id="tpc_select_difficulty">
				<option value="easy">Easy</option>
			</select>
		</div>
		<div class="tpc_newtest_line">
			<label for="type">Correct Response:</label>
			<select id="tpc_select_answer">
				<option value="delete">Delete</option>
				<option value="publish">Publish</option>
			</select>
		</div>
		<input type="submit" name="submit" id='tpc_newtest_submit' value="Add Test"/>
	</form>
	<br>
	<h3>Tips Patrol Tests:</h3>
	<br>

	<table class="tpc_scores">
		<tr>
			<th>Page</th>
			<th>Tip</th>
			<th>Answer</th>
			<th>Fail Message</th>
			<th>Success Message</th>
			<th>Difficulty</th>
			<th>Creator</th>
			<th></th>
		</tr>
		<?
		$i = 0;
		foreach ($tests as $test):
			$class = $i % 2 == 0 ? 'even' : 'odd';
			$i++
		?>
			<tr class="<?=$class?>" id='<?=$test['tpt_page']?>'>
				<td><a href='<?=$test['page']?>' class='tpt_detail'><?=$test['page']?></a></td>
				<td><?=$test['tpt_tip']?></td>
				<td><?=$test['answer']?></td>
				<td><?=$test['tpt_fail_message']?></td>
				<td><?=$test['tpt_success_message']?></td>
				<td><?=$test['difficulty']?></td>
				<td><?=$test['user']?></td>
				<td><a href='#' class='tpc_delete_test' testId=<?=$test['tpt_id']?>>Delete</td>
			</tr>
		<? endforeach; ?>
	</table>
</div>
<div id="content-scores" style="display:None;">
	<h3>Past Scores:</h3>
	<form class="tpc_ts" method="GET">
	<label for="days">Test scores from the past </label><input type="text" name="days" value="<?=$days?>"/> days
	<input type="submit" maxlength="2" name="submit" value="Go"/>
	</form>
	<table class="tpc_scores">
		<tr>
			<th>User</th>
			<th>%Easy</th>
			<th>%Correct</th>
			<th>%Wrong</th>
			<th>Tests Taken</th>
			<th>Patrol Count</th>
			<th>Reset Test State</th>
			<th>Undo Tips</th>
			<th>Block</th>
		</tr>
		<?
		$i = 0;
		foreach ($scores as $result):
			$class = $i % 2 == 0 ? 'even' : 'odd';
			$i++
		?>
			<tr class="<?=$class?>" id='<?=$result['user_id']?>'>
				<td><a href='/<?=$result['user_link']?>' class='tpc_detail'><?=$result['user_name']?></a></td>
				<td><?=$result['percent_easy']?></td>
				<td><?=$result['correct']?></td>
				<td><?=$result['incorrect']?></td>
				<td><?=$result['total']?></td>
				<td><?=$result['patrol_count']?></td>
				<td><a href='#' class='reset_test' userId=<?=$result['user_id']?>>Reset</a></td>
				<td><a href='/Special:UnpatrolTips?target=<?=$result['user_id']?>' target="_blank">Unpatrol</a></td>
				<td><a href='#' class='blockuser' userId=<?=$result['user_id']?>><?=$result['block']?></a></td>
			</tr>
		<? endforeach; ?>
	</table>
</div>
<br>
<div id="article_tabs_line"></div>
<div id="tpc_disable" style="display:<?=$disableDisplay?>;"><a href='#' class="button button220">Disable TPCoach</a></div>
<div id="tpc_enable" style="display:<?=$enableDisplay?>;"><a href='#' class="button button220">Enable TPCoach</a></div>
