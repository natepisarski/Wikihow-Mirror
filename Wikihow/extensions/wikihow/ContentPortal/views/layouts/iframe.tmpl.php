<html>
<head>
	<? if (ENV != 'production'): ?>
		<link rel="shortcut icon" href="<?= imgPath("dev-favicon.ico") ?>" />
	<? endif; ?>
	
	<title>WikiHow Content Portal</title>
	<?= styles() ?>
</head>

<body>
	<div class="<?= containerClass() ?>">
		<?= $yield ?>
	</div>	
</body>
</html>
