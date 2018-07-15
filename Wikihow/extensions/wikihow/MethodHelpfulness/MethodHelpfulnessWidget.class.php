<?
namespace MethodHelpfulness;
use MethodHelpfulness\ArticleMethod;
use EasyTemplate;

class Widget {
	public static function getWidgetLoaderJS() {
?>
if (window.mw && $('#method-title-info').length && $('#method_helpfulness_box').length && window.WH) {
	$.post(
		'/Special:MethodHelpfulness',
		{
			'action': 'get_widget',
			'aid': wgArticleId
		},
		function (result) {
			if (result) {
				mw.loader.using(['ext.wikihow.methodhelpfulness.widget'], function () {
					if (result['widget_summary']) {
						$('#method_helpfulness_box').html(result['widget_summary']);
						$('#method_helpfulness_box').show();
						var mhw = new WH.MethodHelpfulnessWidget();
						mhw.sortMethods();
					}
				});
			}
		},
		'json'
	);
}
<?
	}
	public static function getWidget($aid, $widgetSectionTypes) {
		global $IP;

		if (!$aid) {
			return '';
		}

		$vars = array('ctaDetails' => array());

		$ctaDetails = ArticleMethod::getCTAVoteDetails($aid);

		foreach ($widgetSectionTypes as $widgetSectionType) {
			$ctaWidgetSectionClass =
				CTAWidgetSection::getCTAWidgetSectionClass($widgetSectionType);

			if ($ctaWidgetSectionClass === false) {
				continue;
			}

			$ctaWidgetSectionClass = 'MethodHelpfulness\\' . $ctaWidgetSectionClass;

			$ctaWidgetSectionVars = $ctaWidgetSectionClass::getFormattedVars($ctaDetails);

			if ($ctaWidgetSectionVars === false) {
				continue;
			}

			// Don't show totals for now (10/29/15)
			// $ctaWidgetSectionTotal = ArticleMethod::getTotalCTAVotes($widgetSectionType, $aid);
			$ctaWidgetSectionTotal = false;

			$vars['ctaDetails'][$widgetSectionType] = $ctaWidgetSectionVars;
			$vars['ctaTotals'][$widgetSectionType] = $ctaWidgetSectionTotal;
		}

		EasyTemplate::set_path('');
		$widget = EasyTemplate::html(
			"$IP/extensions/wikihow/MethodHelpfulness/resources/widget/mh_widget.tmpl.php",
			$vars
		);

		return $widget;
	}

}

abstract class CTAWidgetSection {
	public static function getCTAWidgetSectionClass($type) {
		if ($type === 'bottom_form') {
			return BottomFormWidgetSection;
		} elseif ($type === 'method_thumbs') {
			return MethodThumbsWidgetSection;
		} elseif ($type === 'combined_simple') {
			return CombinedSimpleWidgetSection;
		} elseif ($type == 'method_header') {
			return MethodHeaderWidgetSection;
		} else {
			return false;
		}
	}

	public static function getFormattedVars($voteData) {
		$formattedVars = array();

		$formattedVars['displayName'] = static::getDisplayName();
		$formattedVars['headers'] = static::getColumnHeaders();
		$formattedVars['nColumns'] = count($formattedVars['headers']);

		$intermediateVoteData = array();
		$collectVoteDataFn = static::getIntermediateVoteDataCollectorFunction();
		$validCTATypes = static::getValidCTATypes();

		foreach ($voteData as $ctaType=>$ctaData) {
			if (in_array($ctaType, $validCTATypes)) {
				static::$collectVoteDataFn($intermediateVoteData, $ctaType, $ctaData);
			}
		}

		$formattedVoteData = array();
		$formatVoteDataFn = static::getVoteDataFormattingFunction();

		foreach ($intermediateVoteData as $method=>$voteInfo) {
			$formattedVoteData[] = static::$formatVoteDataFn($method, $voteInfo);
		}

		if (count($formattedVoteData) == 0) {
			return false;
		}

		$formattedVars['rows'] = $formattedVoteData;

		return $formattedVars;
	}

	public static function getIntermediateVoteDataCollectorFunction() {
		return 'simpleVoteCount';
	}

	public static function simpleVoteCount(&$intermediateVoteData, $ctaType, $ctaData) {
		$goodVotes = static::goodVoteTypes();

		foreach ($ctaData as $voteRow) {
			$method = $voteRow['method'];
			$vote = $voteRow['vote'];
			$count = $voteRow['count'];

			$intermediateVoteData[$method]['total'] += $count;

			if (in_array($vote, $goodVotes)) {
				$intermediateVoteData[$method]['count'] += $count;
			}
		}
	}


	public static function simpleVoteFormat($method, $voteInfo) {
		return array(
			$method,
			number_format(100.0 * $voteInfo['count'] / $voteInfo['total'], 0) . '%&nbsp;(<i>' . $voteInfo['total'] . '</i>)'
		);
	}

	public static function rawVoteFormat($method, $voteInfo) {
		return array(
			\Sanitizer::escapeId( $method ),
			number_format(100.0 * $voteInfo['count'] / $voteInfo['total'], 0) 
		);
	}

	abstract public static function getVoteDataFormattingFunction();	

	abstract public static function getDisplayName();

	abstract public static function getColumnHeaders();

	abstract public static function getValidCTATypes();

	// TODO: Put this somewhere at some point, like MethodHelpfulnessCTA?
	public static function goodVoteTypes() {
		return array('checkbox_checked', 'smiley_happy', 'vote_yes');
	}
}

class BottomFormWidgetSection extends CTAWidgetSection {
	public static function getDisplayName() {
		return 'Multi-Checkbox Method Votes';
	}

	public static function getColumnHeaders() {
		return array('Method', 'Checked (Total)');
	}

	public static function getValidCTATypes() {
		return array('bottom_form');
	}
	
	public static function getVoteDataFormattingFunction() {
		return 'simpleVoteFormat';
	}
}

class MethodThumbsWidgetSection extends CTAWidgetSection {
	public static function getDisplayName() {
		return 'Individual Per-Method Votes';
	}

	public static function getColumnHeaders() {
		return array('Method', 'Upvoted (Total)');
	}

	public static function getValidCTATypes() {
		return array('method_thumbs');
	}

	public static function getVoteDataFormattingFunction() {
		return 'simpleVoteFormat';
	}
}

class CombinedSimpleWidgetSection extends CTAWidgetSection {
	public static function getDisplayName() {
		return 'Combined Method Helpfulness Votes';
	}

	public static function getColumnHeaders() {
		return array('Method', 'Upvoted (Total)');
	}
	public static function getValidCTATypes() {
		return array('bottom_form', 'method_thumbs');
	}

	public static function getVoteDataFormattingFunction() {
		return 'simpleVoteFormat';
	}
}

class MethodHeaderWidgetSection extends CTAWidgetSection {
	public static function getDisplayName() {
		return 'Method Helpfulness Votes';
	}

	public static function getColumnHeaders() {
		return array('Method', 'Checked (Total)');
	}
	public static function getValidCTATypes() {
		return array('method_thumbs');
	}

	public static function getVoteDataFormattingFunction() {
		return 'rawVoteFormat';
	}
}

