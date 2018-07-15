<?

function paginate() {
	$mvcRouter = MVC\Router::getInstance();
	$tpl = new EasyTemplate();
	$tpl->set_vars([
		'total' => \MVC\Paginator::$total,
		'perPage' => \MVC\Paginator::$perPage,
		'currentPage' => \MVC\Paginator::currentPage()
	]);
	return $tpl->execute("{$mvcRouter->mvcDir}/templates/_pagination");
}

function paginationLink($num) {
	$params = params();
	$params['page'] = $num;
	return url("{$_GET['controller']}/{$_GET['action']}", $params);
}

function sortUrl($on, $isField=true, $defaultDir="ASC") {

	if (isset($_GET['sort_dir']) && $on == $_GET['sort']) {
		$dir = $_GET['sort_dir'] == "ASC" ? "DESC" : "ASC";
	} else {
		$dir = $defaultDir;
	}

	return currentUrl(['sort' => $on, 'sort_dir' => $dir, 'field' => $isField]);
}

function sortHeader($label, $on, $isField=true, $defaultDir="ASC") {
	$classes = ['sort'];

	if (isset($_GET['sort']) && $_GET['sort'] == $on) {
		array_push($classes, 'active', $_GET['sort_dir']);
	}

	return el(['type' => 'th', 'class' => implode(' ', $classes)]) . el([
		'type'  => 'a',
		'href'  => sortUrl($on, $isField, $defaultDir)
	]) . $label . close('a') . close('th');
}
