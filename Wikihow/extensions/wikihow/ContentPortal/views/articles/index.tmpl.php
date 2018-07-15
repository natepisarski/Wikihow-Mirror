
<? if ($redirectCount > 0): ?>
	<div class="alert alert-warning">
		<p>
			<i class="fa fa-exclamation-triangle"></i>
			There are <strong>(<?= $redirectCount ?>)</strong> Articles that have been redirected.
			<a class="alert-link" href="<?= redirectsUrl() ?>">
				Fix them now
			</a>
		</p>
	</div>
<? endif; ?>

<? if ($deleteCount > 0): ?>
	<div class="alert alert-danger">
		<p>
			<i class="fa fa-exclamation-triangle"></i>
			There are <strong>(<?= $deleteCount ?>)</strong> Articles that have been deleted.
			<a class="alert-link" href="<?= deletesUrl() ?>">
				Fix them now
			</a>
		</p>
	</div>
<? endif; ?>


<div class="row">
	<div class="col-md-8">
		<div class="well">
			<h2>
				Articles
				<small>
				<?
					if (isset($category)):
						echo "for Category <strong>{$category->title}</strong> ";
					endif;
					if (isset($state) && $state != 'any'):
						echo "for State <strong>{$state->present_tense}</strong>";
					endif;
				?>
				</small>
			</h2>

			<ul class="nav nav-tabs" role="tablist">
				<li role="presentation" class="active">
					<a href="#filter" id="invoice-tab" aria-controls="home" role="tab" data-toggle="tab">Filter</a>
				</li>
				<li role="presentation">
					<a class="f-search" href="#search" aria-controls="messages" role="tab" data-toggle="tab">Search</a>
				</li>
			</ul>

			<!-- Tab panes -->
			<div class="tab-content well-body">
				<div role="tabpanel" class="tab-pane active" id="filter">
					<?= partial('articles/_filter_form') ?>
				</div>

				<div role="tabpanel" class="tab-pane" id="search">

					<div id="suggest">
						<div class="row">
							<div class="col-md-8">
								<input class="form-control input-lg input-block" type="text" name="title_search" class="f-search-field" placeholder="Search Titles" />
							</div>

							<div class="col-md-2">
								<button id="full-search" class="btn btn-primary btn-lg" type="submit">
									Search
									<i class="fa fa-search"></i>
								</button>
							</div>
						</div>
					</div>

				</div>

			</div>
		</div>
	</div>

	<div class="col-md-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				Create an Article
			</div>
			<div class="panel-body">
				<p>
					Create a new article in the Portal from an existing article on WikiHow.
				</p>
				<a class="f-create pull-right btn btn-primary btn-block" href="<?= url('articles/new') ?>">
					<i class="fa fa-plus"></i>
					Create Article
				</a>
			</div>
		</div>
	</div>

</div>

<div id="articles" class="well article-container">


	<?
		if (empty($articles)):
			$cat = $category ? $category->title : '';
			echo alert("There are currently no articles in {$cat} " . stateDescrip($state), 'danger');
		else:
			echo partial('articles/_admin_table');
		endif;
	?>
</div>

