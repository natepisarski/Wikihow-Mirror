<?php

class Hypothesis {

	/* Methods */

	/**
	 * Check if Hypothesis should be enabled for a given request.
	 */
	private static function isEnabled( $context ) {
		return (
			// No AMP
			!GoogleAmp::isAmpMode( $context->getOutput() ) &&
			// Only anons
			$context->getUser()->isAnon() &&
			// Only main namespace
			$context->getTitle()->inNamespace( NS_MAIN ) &&
			// Only article view
			Action::getActionName( $context ) === 'view'
		);
	}

	/**
	 * onOptimizelyGetTag hook to override optimizely tag on active Hypothesis pages.
	 */
	public static function onOptimizelyGetTag( $context, $location, &$tag ) {
		$request = $context->getRequest();
		$project = $request->getIntOrNull( 'hyp-opti-project' );
		$experiment = $request->getIntOrNull( 'hyp-opti-experiment' );
		$variation = $request->getIntOrNull( 'hyp-opti-variation' );

		wfDebugLog(
			'hypothesis',
			">> GET_OPTI_TAG " . var_export( [
				'project' => $project,
				'experiment' => $experiment,
				'variation' => $variation
			], true ) . "\n"
		);

		if (
			static::isEnabled( $context ) &&
			$project !== null &&
			$experiment !== null &&
			$variation !== null
		) {
			if ( $location === 'body' ) {
				// Override the Optimizely tag for the body
				$tag =
					Html::inlineScript(
						'window.optimizely = window.optimizely || [];' .
						'window.optimizely.push(' .
							FormatJson::encode( [
								'type' => 'bucketVisitor',
								'experimentId' => $experiment,
								'variationIndex' => $variation
							] ) .
						');' .
						'window.optimizely.push(' .
							FormatJson::encode( [
								'type' => 'page',
								'pageName' => "{$project}_{$experiment}",
							] ) .
						');'
					) .
					Html::rawElement( 'script', [
						'async',
						'src' => "https://cdn.optimizely.com/js/{$project}.js"
					] );
			} else {
				// Ensure this is the only Optimizely tag by blanking out other locations
				$tag = '';
			}
		}
	}

	/**
	 * onBeforePageDisplay hook to add Optimizely code to the body.
	 */
	public static function onBeforePageDisplay( OutputPage &$output, Skin &$skin ) {
		$context = $output->getContext();
		if ( in_array( 'staff', $output->getUser()->getGroups() ) ) {
			switch ( Action::getActionName( $context ) ) {
				case 'history':
					$output->addModules( [ 'ext.wikihow.hypothesis.history' ] );
					break;
			}
		}
	}

	/**
	 * onBeforeInitialize hook to to add Hypothesis headers on normal requests and extra query
	 *   params on bucketed requests.
	 */
	public static function onBeforeInitialize( &$title, &$article, &$output, &$user, $request, $mediaWiki ) {
		$context = $output->getContext();

		// Use general resitrcitons, plus disable when oldid param is provided
		if ( static::isEnabled( $context ) && !$context->getRequest()->getCheck( 'oldid' ) ) {
			$dbr = wfGetDB( DB_REPLICA );
			// Perform a quick query for any test associated with this page
			// Doesn't gaurantee there's an active experiment for this page, as it's just a
			// lightweight way to reduce the amount of work for majority of anon article requests.
			$row = $dbr->selectRow(
				'hyp_test',
				'count(*) as count',
				[ 'hypt_page' => $title->getArticleId() ],
				__METHOD__
			);
			if ( $row->count ) {
				// Perform a complete query for an active test associated with this page
				// Joins the experiment table to collect additional information and verify the
				// experiment is running and meant for the current target.
				$row = $dbr->selectRow(
					[ 'hyp_test', 'hyp_experiment' ],
					[
						'hypt_rev_a',
						'hypt_rev_b',
						'hypx_target',
						'hypx_id',
						'hypx_status',
						'hypx_holdback',
						'hypx_opti_project',
						'hypx_opti_experiment',
					],
					[
						// Test includes this article
						'hypt_page' => $title->getArticleId(),
						// Experiment is running
						'hypx_status' => 'running',
						// Experiment is for current target
						$dbr->makeList( [
							// Desktop and mobile
							'hypx_target' => 'all',
							// OR we're in mobile and target is mobile
							$dbr->makeList( [
								Misc::isMobileMode() ? 'TRUE' : 'FALSE',
								'hypx_target' => 'mobile'
							], LIST_AND ),
							// OR we're in desktop and target is desktop
							$dbr->makeList( [
								!Misc::isMobileMode() ? 'TRUE' : 'FALSE',
								'hypx_target' => 'desktop'
							], LIST_AND )
						], LIST_OR )
					],
					__METHOD__,
					[],
					[ 'hyp_experiment' => [ 'INNER JOIN', [ 'hypt_experiment=hypx_id' ] ] ]
				);

				if ( $row ) {
					// Generate cookie name from experiment ID
					$cookie = "hyp_{$row->hypx_id}";
					// Send the cookie name to varnish via a header
					$request->response()->header( "X-Hypothesis-Cookie: {$cookie}" );
					// Get the bucket from varnish via a header
					$bucket = $request->getHeader( 'X-Hypothesis-Bucket' );
					// Respond differently if user is bucketed or not
					if ( $bucket === false || $bucket === '-' ) {
						// Render normally, send a holdback for bucketing to varnish via a header
						$holdback = ceil( (int)$row->{"hypx_holdback"} / 100 );
						$request->response()->header( "X-Hypothesis-Holdback: {$holdback}" );
					} elseif ( $bucket === 'a' || $bucket === 'b' ) {
						// Render variant by faking the oldid parameter
						$request->setVal( 'oldid', $row->{"hypt_rev_{$bucket}"} );
						$request->setVal( 'hyp-opti-project', $row->hypx_opti_project );
						$request->setVal( 'hyp-opti-experiment', $row->hypx_opti_experiment );
						$request->setVal( 'hyp-opti-variation', $bucket == 'a' ? 0 : 1 );
						// Add a client-side module to suppress oldid formatting on mobile
						if ( Misc::isMobileMode() ) {
							$output->addModules( [ 'ext.wikihow.hypothesis.view.mobile' ] );
						}
					}

					wfDebugLog(
						'hypothesis',
						">> REQUEST " . var_export( [
							'cookie' => $cookie,
							'holdback' => $holdback,
							'bucket' => $bucket,
							'config' => $request->getHeader( 'X-Hypothesis-Config' )
						], true ) . "\n"
					);
				}
			}
		}
	}
}
