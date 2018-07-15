<? if (isset($errors)): ?>
	<div class="alert alert-danger alert-dismissible fade in">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
			<span aria-hidden="true">&times;</span>
		</button>
		
		<strong><?= isset($title) ? $title : 'There were the following errors:' ?></strong>
			<? foreach($errors as $field => $messages): ?>
				<ul>
					<? foreach($messages as $msg): ?> 
						<li><strong><?= is_string($field) ? ucfirst($field) : '' ?></strong> <?= $msg ?></li>
					<? endforeach; ?>
				</ul>
			<? endforeach; ?>
	</div>
<? endif; ?>
