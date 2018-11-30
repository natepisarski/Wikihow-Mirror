<?php

class JsonApi
{
	/**
	 * @param array   $data      To be serialized and rendered in the response body
	 * @param int     $code      HTTP status code
	 * @param string  $callback  An optional function name for JSONP
	 */
	public static function response(array $data, int $code=200, string $callback='') {
		global $wgOut, $wgRequest;

		$contentType = empty($callback) ? 'application/json' : 'application/javascript';

		$wgRequest->response()->header("Content-type: $contentType");
		$wgOut->disable();

		if ($code != 200) {
			$message = HttpStatus::getMessage($code);
			if ($message) {
				$wgRequest->response()->header("HTTP/1.1 $code $message");
			}
		}

		if (empty($callback)) {
			echo json_encode($data);
		} else {
			echo htmlspecialchars($callback) . '(' . json_encode($data) . ')';
		}

	}

	public static function error(string $msg) {
		static::response(['error' => $msg], 400);
	}

}

