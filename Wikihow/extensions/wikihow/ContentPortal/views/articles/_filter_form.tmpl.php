<form class="form-inline" method="get" action="<?= url('articles/index') ?>">
	<div class="form-group">
		<label>In category</label>
		<select class="form-control" name="category">
			<? 
			echo option("All", 'all', params('state'));
			foreach($categories as $cat): 
				echo option($cat->title, $cat->id, params('category'));
			endforeach; 
			?>
		</select>
	</div>
	
	<div class="form-group">	
		<label>with state </label>
		<select class="form-control" name="state">
			<? 
			echo option("Any", 'any', params('state'));
			echo option("Unassigned", 'unassigned', params('state'));
			foreach($roles as $role):
				echo option($role->present_tense, $role->id, params('state'));
			endforeach;
			?>
		</select>
		<input type="submit" class="btn btn-primary" value="Refresh"/>
	</div>
</form>