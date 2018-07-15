<?php
/* This class implements rank biased overlap as described here
 *
 * "A Similarity Measure for Indefinite Rankings" William Webber, Alistair Moffat,
 * and Justin Zobel (Nov 2010).
 *
 *  http://www.williamwebber.com/research/papers/wmz10_tois.pdf
 *
 * based on python code from here
 * https://github.com/dlukes/rbo
 *
 * Only implemeted RBO point estimate based on extrapolating observed overlap.
 * Equation 32 in the paper
 *
 * This takes into account the rank order of matches as well vs. only looking at
 * overlap
 */
class RankBiasedOverlap {

	private function agreement( $arr1, $arr2, $depth ) {
		$a1 = array_slice( array_unique( $arr1 ), 0, $depth );
		$a2 = array_slice( array_unique( $arr2 ), 0, $depth );
		$xCount = count( array_intersect( $a1, $a2 ) );

		$agree = 2 * $xCount / ( count( $a1 ) + count( $a2 ) );

		return $agree;
	}

	private function overLap( $arr1, $arr2, $depth ) {
		$val = min( count( $arr1 ), count( $arr2 ), $depth );
		$agr = $this->agreement( $arr1, $arr2, $depth );
		return ( $val * $agr );
	}
	/* The main function to be called
	*/
	public function rankedOverlap( $arr1 , $arr2 , $p = 0.9 ) {
		$arr1Len = count( $arr1 );
		$arr2Len = count( $arr2 );

		$overLap1 = $this->overLap( $arr1, $arr2, $arr1Len );
		$overLap2 = $this->overLap( $arr1, $arr2, $arr2Len );

		foreach ( range( 1, $arr1Len + 1 )  as $num ) {
			$innerTerm1 += $p ** $num * $this->agreement( $arr1, $arr2, $num );
		}

		foreach ( range( $arr2Len + 1, $arr1Len + 1 )  as $num ) {
			$innerTerm2 = $p ** $num * $overLap2 * ( $num - $arr1Len ) / $arr1Len / $arr2Len;
		}

		$term1 = ( 1 - $p ) / $p * ( $innerTerm1 + $innerTerm2 );
		$term2 = $p ** $arr1Len * ( ( $overLap1 - $overLap2 ) / $arr1Len + $overLap2 / $arr2Len );

		$finalValue = $term1 + $term2;

		return $finalValue;
	}

}
