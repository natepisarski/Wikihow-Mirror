<?php
namespace ContentPortal;
use \MVC\Paginator;
class EventsController extends AppController {
	public $adminOnly = ['*'];

	function index() {
		Paginator::$total = Event::count();
		Paginator::$perPage = PER_PAGE;

		$this->events = Event::all([
			'conditions' => ['grouping' => Event::GENERAL],
			'order' => 'created_at DESC, id DESC',
			'limit' => PER_PAGE,
			'offset' => Paginator::getOffset()
		]);
	}

	function user() {
		$this->user = User::find($_GET['user_id']);
		$conditions = [
			'logged_user_id' => $this->user->id,
			'grouping' => Event::GENERAL
		];
		Paginator::$total = Event::count($conditions);
		Paginator::$perPage = PER_PAGE;

		$this->events = Event::all([
			'conditions' => $conditions,
			'limit' => PER_PAGE,
			'offset' => Paginator::getOffset(),
			'order' => 'created_at DESC'
		]);

		$this->render('events/user_events');
	}

	function rules() {
		$conditions = ['grouping' => Event::RULES];
		Paginator::$total = Event::count($conditions);
		Paginator::$perPage = PER_PAGE;

		$this->events = Event::all([
			'conditions' => $conditions,
			'limit' => PER_PAGE,
			'offset' => Paginator::getOffset(),
			'order' => 'created_at DESC'
		]);

		$this->render('events/rules_events');
	}
}
