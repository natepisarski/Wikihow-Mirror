<?php

use MediaWiki\MediaWikiServices;

/**
 * Tokenizer for AbuseFilter rules.
 */
class AbuseFilterTokenizer {
	/** @var int Tokenizer cache version. Increment this when changing the syntax. **/
	const CACHE_VERSION = 1;
	const COMMENT_START_RE = '/\s*\/\*/A';
	const ID_SYMBOL_RE = '/[0-9A-Za-z_]+/A';
	const OPERATOR_RE =
		'/(\!\=\=|\!\=|\!|\*\*|\*|\/|\+|\-|%|&|\||\^|\:\=|\?|\:|\<\=|\<|\>\=|\>|\=\=\=|\=\=|\=)/A';
	const RADIX_RE = '/([0-9A-Fa-f]+(?:\.\d*)?|\.\d+)([bxo])?/Au';
	const WHITESPACE = "\011\012\013\014\015\040";

	// Order is important. The punctuation-matching regex requires that
	// ** comes before *, etc. They are sorted to make it easy to spot
	// such errors.
	public static $operators = [
		// Inequality
		'!==', '!=', '!',
		// Multiplication/exponentiation
		'**', '*',
		// Other arithmetic
		'/', '+', '-', '%',
		// Logic
		'&', '|', '^',
		// Setting
		':=',
		// Ternary
		'?', ':',
		// Less than
		'<=', '<',
		// Greater than
		'>=', '>',
		// Equality
		'===', '==', '=',
	];

	public static $punctuation = [
		',' => AFPToken::TCOMMA,
		'(' => AFPToken::TBRACE,
		')' => AFPToken::TBRACE,
		'[' => AFPToken::TSQUAREBRACKET,
		']' => AFPToken::TSQUAREBRACKET,
		';' => AFPToken::TSTATEMENTSEPARATOR,
	];

	public static $bases = [
		'b' => 2,
		'x' => 16,
		'o' => 8
	];

	public static $baseCharsRe = [
		2  => '/^[01]+$/',
		8  => '/^[0-8]+$/',
		16 => '/^[0-9A-Fa-f]+$/',
		10 => '/^[0-9.]+$/',
	];

	public static $keywords = [
		'in', 'like', 'true', 'false', 'null', 'contains', 'matches',
		'rlike', 'irlike', 'regex', 'if', 'then', 'else', 'end',
	];

	/**
	 * @param string $code
	 * @return array
	 * @throws AFPException
	 * @throws AFPUserVisibleException
	 */
	public static function tokenize( $code ) {
		static $tokenizerCache = null;

		if ( !$tokenizerCache ) {
			$tokenizerCache = ObjectCache::getLocalServerInstance( 'hash' );
		}

		static $stats = null;

		if ( !$stats ) {
			$stats = MediaWikiServices::getInstance()->getStatsdDataFactory();
		}

		$cacheKey = wfGlobalCacheKey( __CLASS__, self::CACHE_VERSION, crc32( $code ) );

		$tokens = $tokenizerCache->get( $cacheKey );

		if ( $tokens ) {
			$stats->increment( 'abusefilter.tokenizerCache.hit' );
			return $tokens;
		}

		$stats->increment( 'abusefilter.tokenizerCache.miss' );
		$tokens = [];
		$curPos = 0;

		do {
			$prevPos = $curPos;
			$token = self::nextToken( $code, $curPos );
			$tokens[ $token->pos ] = [ $token, $curPos ];
		} while ( $curPos !== $prevPos );

		$tokenizerCache->set( $cacheKey, $tokens, 60 * 60 * 24 );

		return $tokens;
	}

	/**
	 * @param string $code
	 * @param int &$offset
	 * @return AFPToken
	 * @throws AFPException
	 * @throws AFPUserVisibleException
	 */
	protected static function nextToken( $code, &$offset ) {
		$matches = [];
		$start = $offset;

		// Read past comments
		while ( preg_match( self::COMMENT_START_RE, $code, $matches, 0, $offset ) ) {
			if ( strpos( $code, '*/', $offset ) === false ) {
				throw new AFPUserVisibleException(
					'unclosedcomment', $offset, [] );
			}
			$offset = strpos( $code, '*/', $offset ) + 2;
		}

		// Spaces
		$offset += strspn( $code, self::WHITESPACE, $offset );
		if ( $offset >= strlen( $code ) ) {
			return new AFPToken( AFPToken::TNONE, '', $start );
		}

		$chr = $code[$offset];

		// Punctuation
		if ( isset( self::$punctuation[$chr] ) ) {
			$offset++;
			return new AFPToken( self::$punctuation[$chr], $chr, $start );
		}

		// String literal
		if ( $chr === '"' || $chr === "'" ) {
			return self::readStringLiteral( $code, $offset, $start );
		}

		$matches = [];

		// Operators
		if ( preg_match( self::OPERATOR_RE, $code, $matches, 0, $offset ) ) {
			$token = $matches[0];
			$offset += strlen( $token );
			return new AFPToken( AFPToken::TOP, $token, $start );
		}

		// Numbers
		if ( preg_match( self::RADIX_RE, $code, $matches, 0, $offset ) ) {
			$token = $matches[0];
			$input = $matches[1];
			$baseChar = $matches[2] ?? null;
			// Sometimes the base char gets mixed in with the rest of it because
			// the regex targets hex, too.
			// This mostly happens with binary
			if ( !$baseChar && !empty( self::$bases[ substr( $input, - 1 ) ] ) ) {
				$baseChar = substr( $input, - 1, 1 );
				$input = substr( $input, 0, - 1 );
			}

			$base = $baseChar ? self::$bases[$baseChar] : 10;

			// Check against the appropriate character class for input validation

			if ( preg_match( self::$baseCharsRe[$base], $input ) ) {
				$num = $base !== 10 ? base_convert( $input, $base, 10 ) : $input;
				$offset += strlen( $token );
				return ( strpos( $input, '.' ) !== false )
					? new AFPToken( AFPToken::TFLOAT, floatval( $num ), $start )
					: new AFPToken( AFPToken::TINT, intval( $num ), $start );
			}
		}

		// IDs / Keywords

		if ( preg_match( self::ID_SYMBOL_RE, $code, $matches, 0, $offset ) ) {
			$token = $matches[0];
			$offset += strlen( $token );
			$type = in_array( $token, self::$keywords )
				? AFPToken::TKEYWORD
				: AFPToken::TID;
			return new AFPToken( $type, $token, $start );
		}

		throw new AFPUserVisibleException(
			'unrecognisedtoken', $start, [ substr( $code, $start ) ] );
	}

	/**
	 * @param string $code
	 * @param int &$offset
	 * @param int $start
	 * @return AFPToken
	 * @throws AFPException
	 * @throws AFPUserVisibleException
	 */
	protected static function readStringLiteral( $code, &$offset, $start ) {
		$type = $code[$offset];
		$offset++;
		$length = strlen( $code );
		$token = '';
		while ( $offset < $length ) {
			if ( $code[$offset] === $type ) {
				$offset++;
				return new AFPToken( AFPToken::TSTRING, $token, $start );
			}

			// Performance: Use a PHP function (implemented in C)
			// to scan ahead.
			$addLength = strcspn( $code, $type . "\\", $offset );
			if ( $addLength ) {
				$token .= substr( $code, $offset, $addLength );
				$offset += $addLength;
			} elseif ( $code[$offset] == '\\' ) {
				switch ( $code[$offset + 1] ) {
					case '\\':
						$token .= '\\';
						break;
					case $type:
						$token .= $type;
						break;
					case 'n';
						$token .= "\n";
						break;
					case 'r':
						$token .= "\r";
						break;
					case 't':
						$token .= "\t";
						break;
					case 'x':
						$chr = substr( $code, $offset + 2, 2 );

						if ( preg_match( '/^[0-9A-Fa-f]{2}$/', $chr ) ) {
							$token .= chr( hexdec( $chr ) );
							// \xXX -- 2 done later
							$offset += 2;
						} else {
							$token .= 'x';
						}
						break;
					default:
						$token .= "\\" . $code[$offset + 1];
				}

				$offset += 2;

			} else {
				// Should never happen
				$token .= $code[$offset];
				$offset++;
			}
		}
		throw new AFPUserVisibleException( 'unclosedstring', $offset, [] );
	}
}
