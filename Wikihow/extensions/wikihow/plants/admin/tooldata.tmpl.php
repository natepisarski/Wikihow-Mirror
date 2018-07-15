<form>
	<a href="/Special:AdminPlants" style="float:right">Back to Tool Selection</a>
	<a href="#" class="button secondary plant_save">Save</a>
	<ul class="ui-sortable">
		<? foreach ($data as $row): ?>
			<?= $row ?>
		<? endforeach ?>
	</ul>
	<a href="#" class="button secondary plant_save">Save</a>
</form>