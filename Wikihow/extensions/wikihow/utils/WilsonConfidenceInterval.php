<?php

/**
 * Compute a confidence interval score based on up/down votes.
 *
 * Adapted from: https://possiblywrong.wordpress.com/2011/06/05/reddits-comment-ranking-algorithm/
 */
class WilsonConfidenceInterval {

	public static function getScore($ups, $downs) {
		if ($ups == 0) {
			return -$downs;
		}
		$n = $ups + $downs;
		// 1.0 = 85%, 1.6 = 95%
		$z = 1.64485;
		$phat = floatval($ups) / $n;
		//     return (phat+z*z/(2*n)-z*sqrt((phat*(1-phat)+z*z/(4*n))/n))/(1+z*z/n)
		return ($phat + $z*$z / (2*$n) - $z * sqrt(($phat * (1 - $phat) + $z * $z / (4 * $n)) / $n)) / (1 + $z * $z / $n);
	}
}
