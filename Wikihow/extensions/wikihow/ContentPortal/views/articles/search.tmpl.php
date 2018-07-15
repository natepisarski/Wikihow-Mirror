
<h4>
	Search results for "<?= $_GET['title_search'] ?>"
	<small>
		<a class="btn btn-default btn-xs pull-right" href="<?= url('articles/index') ?>">
			<i class="fa fa-times-circle"></i>
			Clear Search
		</a>
	</small>
</h4>

<hr/>

<?= partial('articles/_admin_table') ?>