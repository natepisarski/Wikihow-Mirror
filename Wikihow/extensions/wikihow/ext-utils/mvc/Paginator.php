<?
namespace MVC;
use __;

class Paginator {

	public static $total = 0;
	public static $perPage = 25;
	public static $linkOffset = 10;

	public static function currentPage() {
		return params('page', 0);
	}

	public static function numPages() {
		if (self::$total == 0) {
			return 1;
		}

		return ceil(self::$total / self::$perPage);
	}

	public static function getOffset() {
		return floor(self::currentPage() * self::$perPage);
	}

	public static function getSort($defaultSort="id DESC") {
		return (params('sort') && params('field')) ? "{$_GET['sort']} {$_GET['sort_dir']}": $defaultSort;
	}

	public static function setDefault($property, $dir="DESC", $field=true) {
		if (!isset($_GET['sort'])) {
			$_GET['sort'] = $property;
			$_GET['sort_dir'] = $dir;
			$_GET['field'] = $field;
		}
	}

	public static function isCollectionSort() {
		return (params('sort') && params('field', true) == false) ? true : false;
	}

	public static function sort($arr) {
		if (!self::isCollectionSort()) {
			return $arr;
		}


		$result = __::sortBy($arr, function ($item) {
			return method_exists($item, params('sort')) ? $item->{params('sort')}() : $item->{params('sort')};
		});

		return params('sort_dir') == 'DESC' ? array_reverse($result) : $result;
	}
}
