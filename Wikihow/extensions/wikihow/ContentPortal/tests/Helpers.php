<?
namespace ContentPortal;
use \User as WhUser;
use __;

class Helpers {
	static function getFakeArticle($vals=[]) {
		$attrs = __::extend(self::getArticleStub(), $vals);
		$article = new Article($attrs);
		$article->save();
		return $article;
	}

	static function getArticleStub() {
		return [
			'title'       => "Fake test " . self::randomString(),
			'is_wrm'      => true,
			'state_id'    => Role::write()->id,
			'category_id' => Category::first()->id,
			'is_test'     => true
		];
	}

	static function randomString() {
		return substr(md5(rand()), 0, 7);
	}

	static function forceState($article, $key) {
		return $article->update_attribute('state_id', Role::find_by_key($key)->id);
	}

	static function cleanupAll() {
		__::invoke(Article::all(['is_test' => true]), 'delete');
	}

	static function setCurrentUser($user) {
		Session::build($user);
	}

	static function getCurrentUser() {
		return Auth::findCurrentUser();
	}

	static function logOut() {
		Auth::destroy();
	}

	static function loadFixture($file) {
		$file = str_replace('.php', '', $file);
		return include(__DIR__ . "/fixtures/{$file}.php");
	}

	static function getAdmin() { return self::getUser(Role::admin()); }
	static function getWriter($allowAdmin=false) { return self::getUser(Role::write(), $allowAdmin); }
	static function getProofReader($allowAdmin=false) { return self::getUser(Role::proofRead(), $allowAdmin); }
	static function getEditor($allowAdmin=false) { return self::getUser(Role::edit(), $allowAdmin); }
	static function getReviewer($allowAdmin=false) { return self::getUser(Role::review(), $allowAdmin); }
	static function getVerifier($allowAdmin=false) { return self::getUser(Role::verify(), $allowAdmin); }

	static function getUser(Role $role, $admin=true) {
		if ($admin) {
			$user = __::first($role->users);
		} else {
			$user = __::chain($role->users)->reject(function ($user) {
				return $user->hasRoleKey(Role::ADMIN_KEY);
			})->first()->value();
		}

		return $user;
	}
}
