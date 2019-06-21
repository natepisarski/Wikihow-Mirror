<?php
/**
 * API for querying schema markup
 *
 * @license MIT
 * @author Trevor Parscal <trevorparscal@gmail.com>
 */
$wgExtensionCredits['SchemaMarkupApi'][] = array(
	'name' => 'Schema Markup API',
	'author' => 'Trevor Parscal',
	'description' => 'API that generates schema markup',
);
$wgAutoloadClasses['ApiSchemaMarkup'] = __DIR__ . '/ApiSchemaMarkup.body.php';
$wgAPIModules['schema_markup'] = 'ApiSchemaMarkup';
