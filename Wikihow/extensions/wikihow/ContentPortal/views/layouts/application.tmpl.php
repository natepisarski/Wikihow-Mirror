<html>
<head>
	<? if (ENV != 'production'): ?>
		<link rel="shortcut icon" href="<?= imgPath("dev-favicon.ico") ?>" />
	<? else: ?>
		<link rel="shortcut icon" href="<?= imgPath("portal-favicon.ico") ?>" />
	<? endif; ?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>WikiHow Content Portal</title>
	<script>
		window.WH = window.WH || {};
		WH.Routes = <?= json_encode($routes)?>
	</script>

	<?= styles() ?>
</head>

<body class="<?= "{$_GET['controller']} {$_GET['controller']}-{$_GET['action']}" ?>">

	<nav class="navbar navbar-default navbar-fixed-top">
		<div class="<?= containerClass() ?>">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed btn btn-primary" data-toggle="collapse" data-target="#navbar" aria-expanded="false">
					 <i class="fa fa-navicon"></i>
				</button>
				<a class="navbar-brand" href="<?= url('') ?>">
					<img src="/skins/WikiHow/images/wh-sm.png"/>
				</a>
			</div>


			<p class="navbar-text hidden-xs">
				<?= $currentUser->username ?>
			</p>

			<!-- <img class="avatar hidden-xs" src="<?// avatar($currentUser) ?>"></img> -->

			<div id="navbar" class="collapse navbar-collapse">
				<ul class="nav navbar-nav navbar-right">
					<li>
						<a class="nav-articles-dashboard" href="<?= url('articles/dashboard') ?>">
							<span class="badge active-count"><?= blankIfZero(count(currentUser()->active_articles)) ?></span>
							My Work
						</a>
					</li>

					<li>
						<? if ($currentUser->isFisher()): ?>
							<a href= "<?= url('reservations') ?>">
								<i class="fa fa-hand-paper-o"></i>
								Reserve Articles
							</a>
						<? endif; ?>
					</li>

					<li>
						<a class="nav-articles-logout" href="<?= url('session/destroy') ?>">
							<i class="fa fa-sign-out"></i>
							Leave Portal
						</a>
					</li>

					<? if (currentUser()->isAdmin()): ?>
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
								<i class="fa fa-user-plus"></i>
								Admin <span class="caret"></span>
							</a>
							<ul class="dropdown-menu">
								<li><a class="nav-articles-index" href="<?= url('articles') ?>">Articles</a></li>
								<li><a class="nav-users" href="<?= url('users') ?>">Users</a></li>
								<li><a class="nav-categories" href="<?= url('categories') ?>">Categories</a></li>
								<li><a class="nav-events-index" href="<?= url('events') ?>">Events</a></li>
								<li><a class="nav-rules-events" href="<?= url('events/rules') ?>">Rules Log</a></li>
								<li class="divider"></li>
								<li><a class="nav-exports" href="<?= url('exports') ?>">Export Data</a></li>
								<li><a class="nav-imports" href="<?= url('imports/new') ?>">Import Data</a></li>
							</ul>
						</li>
					<? endif; ?>
				</ul>
			</div>


<!-- 			<form class="navbar-form navbar-left" role="search">
				<div class="form-group">
					<input id="article" type="text" name="article" value="<?// $_GET['article'] ?>" class="form-control" placeholder="Search by url">
				</div>
				<button id="search" type="submit" class="btn btn-default btn-primary">Search for Article</button>
			</form> -->

		</div>
	</nav>

	<div class="<?= containerClass() ?>">
		<?= partial('shared/_flash') ?>
		<?= $yield ?>
	</div>


	<? if (isImpersonated()): ?>
		<div id="impersonate-bar" class="animated bounce">

			<i class="fa fa-warning"></i>
			You are impersonating <strong><?= currentUser()->username ?></strong>.
			Be careful of actions you make!

			<div class="btn-group">
				<a class="btn btn-default cancel">Ok, Got it.</a>
				<a class="btn btn-danger" href="<?= url('impersonate/delete') ?>">
					<i class="fa fa-close"></i>
					Stop Impersonating
				</a>
			</div>

		</div>
	<? endif; ?>

	<div id="loading" class="loader">
		<div class="indicator">
			<i class="fa fa-spinner fa-spin"></i>
		</div>
	</div>

	<?= scripts() ?>
	<script>
		WH.cfApp.initialize(<?= json_encode(isset($errors) ? $errors : '') ?>);
	</script>
	<?= inlineScripts() ?>

</body>
</html>
