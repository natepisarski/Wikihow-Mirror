<?php

class JsonApi {
	/**
	 * @param array   $data      To be serialized and rendered in the response body
	 * @param int     $code      HTTP status code
	 * @param string  $callback  An optional function name for JSONP
	 */
	public static function response(array $data, int $code=200, string $callback='') {
		$req = RequestContext::getMain()->getRequest();
		$out = RequestContext::getMain()->getOutput();

		$contentType = empty($callback) ? 'application/json' : 'application/javascript';

		$req->response()->header("Content-type: $contentType");
		// NOTE: must use disable for the response header to send
		$out->disable();

		if ($code != 200) {
			$message = HttpStatus::getMessage($code);
			if ($message) {
				$req->response()->header("HTTP/1.1 $code $message");
			}
		}

		if (empty($callback)) {
			print json_encode($data);
		} else {
			print htmlspecialchars($callback) . '(' . json_encode($data) . ')';
		}

	}

	public static function error(string $msg) {
		static::response(['error' => $msg], 400);
	}

}

