<?php

class Optimizely {

	/* Static Methods */

	public static function listExperiments() {

		return static::query( 'GET', 'experiments', [ 'project_id' => WH_HYPOTHESIS_OPTIMIZELY_PROJECT ] );
	}

	public static function getExperiment( $id ) {
		return static::query( 'GET', "experiments/{$id}" );
	}

	public static function getPage( $id ) {
		return static::query( 'GET', "pages/{$id}" );
	}

	public static function createExperiment( $data ) {
		$metrics = [
			[
				// startStu
				'aggregator' => 'unique',
				'event_id' => 10312420656,
				'scope' => 'visitor',
				'winning_direction' => 'increasing'
			],
			[
				// Page_3s
				'aggregator' => 'unique',
				'event_id' => 10317221235,
				'scope' => 'visitor',
				'winning_direction' => 'increasing'
			],
			[
				// Page_5s
				'aggregator' => 'unique',
				'event_id' => 10306940942,
				'scope' => 'visitor',
				'winning_direction' => 'increasing'
			],
			[
				// Page_10s
				'aggregator' => 'unique',
				'event_id' => 10311241410,
				'scope' => 'visitor',
				'winning_direction' => 'increasing'
			],
			[
				// Page_30s
				'aggregator' => 'unique',
				'event_id' => 10317221234,
				'scope' => 'visitor',
				'winning_direction' => 'increasing'
			],
			[
				// Page_60s
				'aggregator' => 'unique',
				'event_id' => 10314650457,
				'scope' => 'visitor',
				'winning_direction' => 'increasing'
			],
			[
				// Page_180s
				'aggregator' => 'unique',
				'event_id' => 10308720895,
				'scope' => 'visitor',
				'winning_direction' => 'increasing'
			],
			[
				// Page_300s
				'aggregator' => 'unique',
				'event_id' => 10309770415,
				'scope' => 'visitor',
				'winning_direction' => 'increasing'
			]
		];

		$script = <<<'JAVASCRIPT'
window.optimizely = window.optimizely || [];
window.optimizely.push( { type: 'event', eventName: 'startSTU' } );
setTimeout( function() { window.optimizely.push( { type: 'event', eventName: 'Page_3s' } ); }, 3000 );
setTimeout( function() { window.optimizely.push( { type: 'event', eventName: 'Page_5s' } ); }, 5000 );
setTimeout( function() { window.optimizely.push( { type: 'event', eventName: 'Page_10s' } ); }, 10000 );
setTimeout( function() { window.optimizely.push( { type: 'event', eventName: 'Page_30s' } ); }, 30000 );
setTimeout( function() { window.optimizely.push( { type: 'event', eventName: 'Page_60s' } ); }, 60000 );
setTimeout( function() { window.optimizely.push( { type: 'event', eventName: 'Page_180s' } ); }, 180000 );
setTimeout( function() { window.optimizely.push( { type: 'event', eventName: 'Page_300s' } ); }, 300000 );
JAVASCRIPT;

		return static::query(
			'POST',
			'experiments',
			[ 'action' => $action ],
			array_merge(
				[
					'name' => 'Hypothesis Experiment',
				],
				$data,
				[
					'project_id' => (int)WH_HYPOTHESIS_OPTIMIZELY_PROJECT,
					'holdback' => 0,
					'type' => 'a/b',
					'variations' => [
						[ 'name' => 'First revision', 'weight' => 5000 ],
						[ 'name' => 'Second revision', 'weight' => 5000 ]
					],
					'changes' => [
						[
							'async' => false,
							'type' => 'custom_code',
							'value' => $script
						]
					],
					'metrics' => $metrics
				]
			)
		);
	}

	public static function updateExperiment( $id, $data = array(), $action = '' ) {
		$params = $action !== '' ? [ 'action' => $action ] : [];
		$data = empty( $data ) ? '{}' : $data;
		return static::query( 'PATCH', "experiments/{$id}", $params, $data );
	}

	public static function deleteExperiment( $id ) {
		return static::query( 'DELETE', "experiments/{$id}" );
	}

	/* Private Static Methods */

	private static function query( $method, $endpoint, $query = [], $body = null ) {

		$curl = curl_init();
		$url = wfAppendQuery( "https://api.optimizely.com/v2/{$endpoint}", $query );
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer " . WH_HYPOTHESIS_OPTIMIZELY_ACCESS_TOKEN,
				'Cache-Control: no-cache'
			]
		];
		if ( $body ) {
			if ( is_array( $body ) ) {
				$data = FormatJson::encode( $body );
			} else {
				$data = $body;
			}
			$options[CURLOPT_POSTFIELDS] = $data;
			$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
			$options[CURLOPT_HTTPHEADER][] = 'Content-Length: ' . strlen( $data );
		}
		curl_setopt_array( $curl, $options );

		$response = (array)FormatJson::decode( curl_exec( $curl ) );
		$error = curl_error( $curl );
		$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		curl_close( $curl );

		wfDebugLog(
			'hypothesis',
			">> {$method} {$url} " . var_export( $body, true ) . "\n" .
				"<< {$status} " . var_export( $response, true ) . "\n"
		);

		return $error ?
			[ 'status' => $status, 'response' => [ 'code' => 'curl_error', 'message' => $error ] ] :
			[ 'status' => $status, 'response' => $response ];
	}
}
