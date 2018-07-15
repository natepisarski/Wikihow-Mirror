<?php

/**
 * Computes Wilson's score, which produces a bucketed score, for a given tool
 * depending how many right and wrong answers the user gave to the list of
 * planted / known-answer questions.
 *
 * Note: original source file is in scripts/kredens/wilson.php
 */
class Kredens {

    /**
	 * Run Wilson's score to take the number of questions answered right or
	 * wrong, and return the bucketed user score.
	 * @param int $answered_right
	 */
    public static function calculateUserScore($answered_right, $answered_wrong, $tool_name) {
        $z = 1.960;

        $total = (float)($answered_right + $answered_wrong);

        if ($answered_right == 0) {
            return 0.0;
		}

        $x = $answered_right / $total;

        $top = $x + $z * $z / (2 * $total) - $z * sqrt(($x * (1 - $x) + $z * $z / (4 * $total)) / $total);

        $bottom = 1 + $z * $z / $total;

        return self::bucketScore(($top / $bottom), $tool_name);

    }

	// This method is used internally to compute a bucketed score from the
	// raw score for a given tool name.
    private static function bucketScore($rawscore, $tool_name) {

        // ranges for what bucket the user falls into is tool based
        $score_rule = array(
            "category_guardian" => array(
                "HIGH_TRUST" => 0.79,
                "MEDIUM_TRUST" => 0.67
            ),
            "kb_guardian" => array(
                "HIGH_TRUST" => 0.72,
                "MEDIUM_TRUST" => 0.55
            ),
            "tips_patrol" => array(
                "HIGH_TRUST" => 0.79,
                "MEDIUM_TRUST" => 0.67
            )
        );

		if (!isset($score_rule[$tool_name])) {
			throw new MWException("Tool $tool_name not defined in " . __CLASS__);
		}

        if ($rawscore > $score_rule[$tool_name]["HIGH_TRUST"]) {
            return 1;
        } elseif ($rawscore > $score_rule[$tool_name]["MEDIUM_TRUST"]) {
            return 0.5;
		} else {
			return 0;
		}
    }

	// Used for testing only
	private static function test() {
		for ($i = 1; $i < 16; $i++) {
			$score1 = Kredens::calculateUserScore($i, 16 - $i, "kb_guardian");
			$score2 = Kredens::calculateUserScore($i, 0, "kb_guardian");
			echo "$score1 $score2\n";
		}
	}

}

