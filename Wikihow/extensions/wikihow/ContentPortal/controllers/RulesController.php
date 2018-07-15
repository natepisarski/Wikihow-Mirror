<?
namespace ContentPortal;

class RulesController extends AppController {

	public $adminOnly = ['*'];

	function index() {
		$this->rules = AssignRule::all([
			'order' => 'rule_type DESC, priority ASC'
		]);

		$this->formAction = url('rules/update');
	}

	function update() {
		$all_rules = AssignRule::loadAllRuleIds();
		$enabled_rules = $this->params('enabled');

		foreach($all_rules as $id) {
			$rule = AssignRule::findById($id);

			if (in_array($id, $enabled_rules)) {
				if ($rule->disabled) $rule->enable();
			}
			else {
				if (!$rule->disabled) $rule->disable();
			}
		}

		$this->redirectTo('rules');
	}

}
