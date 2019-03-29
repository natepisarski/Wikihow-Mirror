<?php
namespace ContentPortal;
use MVC\Paginator;

class CategoriesController extends AppController {

	public $adminOnly = ['*'];

	function index() {
		Paginator::$perPage = 20;
		Paginator::$total = Category::count();
		$this->categories = Category::all([
			"include" => ["users", "articles"],
			'limit'      => Paginator::$perPage,
			'offset'     => Paginator::getOffset(),
			'order'      => Paginator::getSort('title ASC')
		]);
	}

	function _new() {
		$this->category = new Category();
	}

	function create() {
		$this->category = new Category($this->params('category'));

		if ($this->category->save()) {
			setFlash('Your changes have been saved', 'success');
			$this->redirectTo("categories");
		} else {
			$this->errors = $this->category->errors->get_raw_errors();
			$this->render('categories/new');
		}
	}

	function edit() {
		$this->category = Category::find($this->params('id'));
	}

	function update() {
		$this->category = Category::find($this->params('id'));

		if ($this->category->update_attributes($this->params('category'))) {
			setFlash('Your changes have been saved', 'success');
			$this->redirectTo('categories');
		} else {
			$this->errors = $this->category->errors->get_raw_errors();
			$this->render('categories/edit');
		}
	}

	function delete() {
		$cat = Category::find($this->params('id'));
		if ($cat->isUsed()) {
			setFlash("$cat->title has articles and or users assigned to it and cannot be deleted!", 'danger');
		} else {
			setFlash("$cat->title has been deleted", 'success');
			$cat->delete();
		}
		$this->redirectTo("categories");
	}
}
