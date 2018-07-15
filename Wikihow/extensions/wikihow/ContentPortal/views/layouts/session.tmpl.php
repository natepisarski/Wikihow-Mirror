<!DOCTYPE html>
<html>
	<head>
		<title>WikiHow Content Portal</title>
	</head>
	<?= styles() ?>
	<body>

	<nav class="navbar navbar-default navbar-fixed-top">
		<div class="container">
			<div class="navbar-header">
				<a class="navbar-brand" href="<?= url('') ?>">
					<img src="/skins/WikiHow/images/wh-sm.png"/>
				</a>
			</div>
		</div>
	</nav>

	<div class="container">
		<?= partial('shared/_flash') ?>
		<?= $yield ?>
	</div>

	<?= scripts() ?>

	<script>
		WH.cfApp.initialize(<?= json_encode(isset($errors) ? $errors : '') ?>);
	</script>

	<?= inlineScripts() ?>
	
	</body>
</html>