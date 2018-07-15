<?php
/**
 * Elasticsearch base extension.  Used by other extensions to ease working with
 * elasticsearch.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'Elastica',
	'author'         => array( 'Nik Everett', 'Chad Horohoe' ),
	'descriptionmsg' => 'elastica-desc',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:Elastica',
	'version'        => '1.0.1.2'
);

/**
 * Classes
 */
$dir = __DIR__ . '/';
$wgAutoloadClasses['ElasticaConnection'] = $dir . 'ElasticaConnection.php';
$wgAutoloadClasses['ElasticaHttpTransportCloser'] = $dir . 'ElasticaConnection.php';

$elasticaDir = $dir . 'Elastica/lib/Elastica/';
$wgAutoloadClasses['Elastica\AbstractUpdateAction'] = $elasticaDir . 'AbstractUpdateAction.php';
$wgAutoloadClasses['Elastica\Bulk'] = $elasticaDir . 'Bulk.php';
$wgAutoloadClasses['Elastica\Client'] = $elasticaDir . 'Client.php';
$wgAutoloadClasses['Elastica\Cluster'] = $elasticaDir . 'Cluster.php';
$wgAutoloadClasses['Elastica\Connection'] = $elasticaDir . 'Connection.php';
$wgAutoloadClasses['Elastica\Document'] = $elasticaDir . 'Document.php';
$wgAutoloadClasses['Elastica\Index'] = $elasticaDir . 'Index.php';
$wgAutoloadClasses['Elastica\JSON'] = $elasticaDir . 'JSON.php';
$wgAutoloadClasses['Elastica\Log'] = $elasticaDir . 'Log.php';
$wgAutoloadClasses['Elastica\Node'] = $elasticaDir . 'Node.php';
$wgAutoloadClasses['Elastica\Param'] = $elasticaDir . 'Param.php';
$wgAutoloadClasses['Elastica\Percolator'] = $elasticaDir . 'Percolator.php';
$wgAutoloadClasses['Elastica\Query'] = $elasticaDir . 'Query.php';
$wgAutoloadClasses['Elastica\Request'] = $elasticaDir . 'Request.php';
$wgAutoloadClasses['Elastica\Response'] = $elasticaDir . 'Response.php';
$wgAutoloadClasses['Elastica\Result'] = $elasticaDir . 'Result.php';
$wgAutoloadClasses['Elastica\ResultSet'] = $elasticaDir . 'ResultSet.php';
$wgAutoloadClasses['Elastica\ScanAndScroll'] = $elasticaDir . 'ScanAndScroll.php';
$wgAutoloadClasses['Elastica\Script'] = $elasticaDir . 'Script.php';
$wgAutoloadClasses['Elastica\ScriptFields'] = $elasticaDir . 'ScriptFields.php';
$wgAutoloadClasses['Elastica\Search'] = $elasticaDir . 'Search.php';
$wgAutoloadClasses['Elastica\SearchableInterface'] = $elasticaDir . 'SearchableInterface.php';
$wgAutoloadClasses['Elastica\Snapshot'] = $elasticaDir . 'Snapshot.php';
$wgAutoloadClasses['Elastica\Status'] = $elasticaDir . 'Status.php';
$wgAutoloadClasses['Elastica\Type'] = $elasticaDir . 'Type.php';
$wgAutoloadClasses['Elastica\Util'] = $elasticaDir . 'Util.php';
$wgAutoloadClasses['Elastica\Aggregation\Cardinality'] = $elasticaDir . 'Aggregation/Cardinality.php';
$wgAutoloadClasses['Elastica\Bulk\Action'] = $elasticaDir . 'Bulk/Action.php';
$wgAutoloadClasses['Elastica\Bulk\Response'] = $elasticaDir . 'Bulk/Response.php';
$wgAutoloadClasses['Elastica\Bulk\ResponseSet'] = $elasticaDir . 'Bulk/ResponseSet.php';
$wgAutoloadClasses['Elastica\Bulk\Action\AbstractDocument'] = $elasticaDir . 'Bulk/Action/AbstractDocument.php';
$wgAutoloadClasses['Elastica\Bulk\Action\CreateDocument'] = $elasticaDir . 'Bulk/Action/CreateDocument.php';
$wgAutoloadClasses['Elastica\Bulk\Action\DeleteDocument'] = $elasticaDir . 'Bulk/Action/DeleteDocument.php';
$wgAutoloadClasses['Elastica\Bulk\Action\IndexDocument'] = $elasticaDir . 'Bulk/Action/IndexDocument.php';
$wgAutoloadClasses['Elastica\Bulk\Action\UpdateDocument'] = $elasticaDir . 'Bulk/Action/UpdateDocument.php';
$wgAutoloadClasses['Elastica\Cluster\Health'] = $elasticaDir . 'Cluster/Health.php';
$wgAutoloadClasses['Elastica\Cluster\Settings'] = $elasticaDir . 'Cluster/Settings.php';
$wgAutoloadClasses['Elastica\Cluster\Health\Index'] = $elasticaDir . 'Cluster/Health/Index.php';
$wgAutoloadClasses['Elastica\Cluster\Health\Shard'] = $elasticaDir . 'Cluster/Health/Shard.php';
$wgAutoloadClasses['Elastica\Exception\BulkException'] = $elasticaDir . 'Exception/BulkException.php';
$wgAutoloadClasses['Elastica\Exception\ClientException'] = $elasticaDir . 'Exception/ClientException.php';
$wgAutoloadClasses['Elastica\Exception\ConnectionException'] = $elasticaDir . 'Exception/ConnectionException.php';
$wgAutoloadClasses['Elastica\Exception\ElasticsearchException'] = $elasticaDir . 'Exception/ElasticsearchException.php';
$wgAutoloadClasses['Elastica\Exception\ExceptionInterface'] = $elasticaDir . 'Exception/ExceptionInterface.php';
$wgAutoloadClasses['Elastica\Exception\InvalidException'] = $elasticaDir . 'Exception/InvalidException.php';
$wgAutoloadClasses['Elastica\Exception\JSONParseException'] = $elasticaDir . 'Exception/JSONParseException.php';
$wgAutoloadClasses['Elastica\Exception\NotFoundException'] = $elasticaDir . 'Exception/NotFoundException.php';
$wgAutoloadClasses['Elastica\Exception\NotImplementedException'] = $elasticaDir . 'Exception/NotImplementedException.php';
$wgAutoloadClasses['Elastica\Exception\PartialShardFailureException'] = $elasticaDir . 'Exception/PartialShardFailureException.php';
$wgAutoloadClasses['Elastica\Exception\ResponseException'] = $elasticaDir . 'Exception/ResponseException.php';
$wgAutoloadClasses['Elastica\Exception\RuntimeException'] = $elasticaDir . 'Exception/RuntimeException.php';
$wgAutoloadClasses['Elastica\Exception\Bulk\ResponseException'] = $elasticaDir . 'Exception/Bulk/ResponseException.php';
$wgAutoloadClasses['Elastica\Exception\Bulk\UdpException'] = $elasticaDir . 'Exception/Bulk/UdpException.php';
$wgAutoloadClasses['Elastica\Exception\Bulk\Response\ActionException'] = $elasticaDir . 'Exception/Bulk/Response/ActionException.php';
$wgAutoloadClasses['Elastica\Exception\Connection\GuzzleException'] = $elasticaDir . 'Exception/Connection/GuzzleException.php';
$wgAutoloadClasses['Elastica\Exception\Connection\HttpException'] = $elasticaDir . 'Exception/Connection/HttpException.php';
$wgAutoloadClasses['Elastica\Exception\Connection\ThriftException'] = $elasticaDir . 'Exception/Connection/ThriftException.php';
$wgAutoloadClasses['Elastica\Facet\AbstractFacet'] = $elasticaDir . 'Facet/AbstractFacet.php';
$wgAutoloadClasses['Elastica\Facet\DateHistogram'] = $elasticaDir . 'Facet/DateHistogram.php';
$wgAutoloadClasses['Elastica\Facet\Filter'] = $elasticaDir . 'Facet/Filter.php';
$wgAutoloadClasses['Elastica\Facet\GeoCluster'] = $elasticaDir . 'Facet/GeoCluster.php';
$wgAutoloadClasses['Elastica\Facet\GeoDistance'] = $elasticaDir . 'Facet/GeoDistance.php';
$wgAutoloadClasses['Elastica\Facet\Histogram'] = $elasticaDir . 'Facet/Histogram.php';
$wgAutoloadClasses['Elastica\Facet\Query'] = $elasticaDir . 'Facet/Query.php';
$wgAutoloadClasses['Elastica\Facet\Range'] = $elasticaDir . 'Facet/Range.php';
$wgAutoloadClasses['Elastica\Facet\Statistical'] = $elasticaDir . 'Facet/Statistical.php';
$wgAutoloadClasses['Elastica\Facet\Terms'] = $elasticaDir . 'Facet/Terms.php';
$wgAutoloadClasses['Elastica\Facet\TermsStats'] = $elasticaDir . 'Facet/TermsStats.php';
$wgAutoloadClasses['Elastica\Filter\AbstractFilter'] = $elasticaDir . 'Filter/AbstractFilter.php';
$wgAutoloadClasses['Elastica\Filter\AbstractGeoDistance'] = $elasticaDir . 'Filter/AbstractGeoDistance.php';
$wgAutoloadClasses['Elastica\Filter\AbstractGeoShape'] = $elasticaDir . 'Filter/AbstractGeoShape.php';
$wgAutoloadClasses['Elastica\Filter\AbstractMulti'] = $elasticaDir . 'Filter/AbstractMulti.php';
$wgAutoloadClasses['Elastica\Filter\BoolFilter'] = $elasticaDir . 'Filter/Bool.php';
$wgAutoloadClasses['Elastica\Filter\BoolAnd'] = $elasticaDir . 'Filter/BoolAnd.php';
$wgAutoloadClasses['Elastica\Filter\BoolNot'] = $elasticaDir . 'Filter/BoolNot.php';
$wgAutoloadClasses['Elastica\Filter\BoolOr'] = $elasticaDir . 'Filter/BoolOr.php';
$wgAutoloadClasses['Elastica\Filter\Exists'] = $elasticaDir . 'Filter/Exists.php';
$wgAutoloadClasses['Elastica\Filter\GeoBoundingBox'] = $elasticaDir . 'Filter/GeoBoundingBox.php';
$wgAutoloadClasses['Elastica\Filter\GeoDistance'] = $elasticaDir . 'Filter/GeoDistance.php';
$wgAutoloadClasses['Elastica\Filter\GeoDistanceRange'] = $elasticaDir . 'Filter/GeoDistanceRange.php';
$wgAutoloadClasses['Elastica\Filter\GeoPolygon'] = $elasticaDir . 'Filter/GeoPolygon.php';
$wgAutoloadClasses['Elastica\Filter\GeoShapePreIndexed'] = $elasticaDir . 'Filter/GeoShapePreIndexed.php';
$wgAutoloadClasses['Elastica\Filter\GeoShapeProvided'] = $elasticaDir . 'Filter/GeoShapeProvided.php';
$wgAutoloadClasses['Elastica\Filter\GeohashCell'] = $elasticaDir . 'Filter/GeohashCell.php';
$wgAutoloadClasses['Elastica\Filter\HasChild'] = $elasticaDir . 'Filter/HasChild.php';
$wgAutoloadClasses['Elastica\Filter\HasParent'] = $elasticaDir . 'Filter/HasParent.php';
$wgAutoloadClasses['Elastica\Filter\Ids'] = $elasticaDir . 'Filter/Ids.php';
$wgAutoloadClasses['Elastica\Filter\Limit'] = $elasticaDir . 'Filter/Limit.php';
$wgAutoloadClasses['Elastica\Filter\MatchAll'] = $elasticaDir . 'Filter/MatchAll.php';
$wgAutoloadClasses['Elastica\Filter\Missing'] = $elasticaDir . 'Filter/Missing.php';
$wgAutoloadClasses['Elastica\Filter\Nested'] = $elasticaDir . 'Filter/Nested.php';
$wgAutoloadClasses['Elastica\Filter\NumericRange'] = $elasticaDir . 'Filter/NumericRange.php';
$wgAutoloadClasses['Elastica\Filter\Prefix'] = $elasticaDir . 'Filter/Prefix.php';
$wgAutoloadClasses['Elastica\Filter\Query'] = $elasticaDir . 'Filter/Query.php';
$wgAutoloadClasses['Elastica\Filter\Range'] = $elasticaDir . 'Filter/Range.php';
$wgAutoloadClasses['Elastica\Filter\Regexp'] = $elasticaDir . 'Filter/Regexp.php';
$wgAutoloadClasses['Elastica\Filter\Script'] = $elasticaDir . 'Filter/Script.php';
$wgAutoloadClasses['Elastica\Filter\Term'] = $elasticaDir . 'Filter/Term.php';
$wgAutoloadClasses['Elastica\Filter\Terms'] = $elasticaDir . 'Filter/Terms.php';
$wgAutoloadClasses['Elastica\Filter\Type'] = $elasticaDir . 'Filter/Type.php';
$wgAutoloadClasses['Elastica\Index\Settings'] = $elasticaDir . 'Index/Settings.php';
$wgAutoloadClasses['Elastica\Index\Stats'] = $elasticaDir . 'Index/Stats.php';
$wgAutoloadClasses['Elastica\Index\Status'] = $elasticaDir . 'Index/Status.php';
$wgAutoloadClasses['Elastica\Multi\ResultSet'] = $elasticaDir . 'Multi/ResultSet.php';
$wgAutoloadClasses['Elastica\Multi\Search'] = $elasticaDir . 'Multi/Search.php';
$wgAutoloadClasses['Elastica\Node\Info'] = $elasticaDir . 'Node/Info.php';
$wgAutoloadClasses['Elastica\Node\Stats'] = $elasticaDir . 'Node/Stats.php';
$wgAutoloadClasses['Elastica\Query\AbstractQuery'] = $elasticaDir . 'Query/AbstractQuery.php';
$wgAutoloadClasses['Elastica\Query\BoolQuery'] = $elasticaDir . 'Query/Bool.php';
$wgAutoloadClasses['Elastica\Query\Boosting'] = $elasticaDir . 'Query/Boosting.php';
$wgAutoloadClasses['Elastica\Query\Builder'] = $elasticaDir . 'Query/Builder.php';
$wgAutoloadClasses['Elastica\Query\Common'] = $elasticaDir . 'Query/Common.php';
$wgAutoloadClasses['Elastica\Query\ConstantScore'] = $elasticaDir . 'Query/ConstantScore.php';
$wgAutoloadClasses['Elastica\Query\Filtered'] = $elasticaDir . 'Query/Filtered.php';
$wgAutoloadClasses['Elastica\Query\FunctionScore'] = $elasticaDir . 'Query/FunctionScore.php';
$wgAutoloadClasses['Elastica\Query\Fuzzy'] = $elasticaDir . 'Query/Fuzzy.php';
$wgAutoloadClasses['Elastica\Query\FuzzyLikeThis'] = $elasticaDir . 'Query/FuzzyLikeThis.php';
$wgAutoloadClasses['Elastica\Query\HasChild'] = $elasticaDir . 'Query/HasChild.php';
$wgAutoloadClasses['Elastica\Query\HasParent'] = $elasticaDir . 'Query/HasParent.php';
$wgAutoloadClasses['Elastica\Query\Ids'] = $elasticaDir . 'Query/Ids.php';
$wgAutoloadClasses['Elastica\Query\Match'] = $elasticaDir . 'Query/Match.php';
$wgAutoloadClasses['Elastica\Query\MatchAll'] = $elasticaDir . 'Query/MatchAll.php';
$wgAutoloadClasses['Elastica\Query\MoreLikeThis'] = $elasticaDir . 'Query/MoreLikeThis.php';
$wgAutoloadClasses['Elastica\Query\MultiMatch'] = $elasticaDir . 'Query/MultiMatch.php';
$wgAutoloadClasses['Elastica\Query\Nested'] = $elasticaDir . 'Query/Nested.php';
$wgAutoloadClasses['Elastica\Query\Prefix'] = $elasticaDir . 'Query/Prefix.php';
$wgAutoloadClasses['Elastica\Query\QueryString'] = $elasticaDir . 'Query/QueryString.php';
$wgAutoloadClasses['Elastica\Query\Range'] = $elasticaDir . 'Query/Range.php';
$wgAutoloadClasses['Elastica\Query\Simple'] = $elasticaDir . 'Query/Simple.php';
$wgAutoloadClasses['Elastica\Query\Term'] = $elasticaDir . 'Query/Term.php';
$wgAutoloadClasses['Elastica\Query\Terms'] = $elasticaDir . 'Query/Terms.php';
$wgAutoloadClasses['Elastica\Query\TopChildren'] = $elasticaDir . 'Query/TopChildren.php';
$wgAutoloadClasses['Elastica\Query\Wildcard'] = $elasticaDir . 'Query/Wildcard.php';
$wgAutoloadClasses['Elastica\Rescore\AbstractRescore'] = $elasticaDir . 'Rescore/AbstractRescore.php';
$wgAutoloadClasses['Elastica\Rescore\Query'] = $elasticaDir . 'Rescore/Query.php';
$wgAutoloadClasses['Elastica\Suggest\AbstractSuggest'] = $elasticaDir . 'Suggest/AbstractSuggest.php';
$wgAutoloadClasses['Elastica\Suggest\Term'] = $elasticaDir . 'Suggest/Term.php';
$wgAutoloadClasses['Elastica\Transport\AbstractTransport'] = $elasticaDir . 'Transport/AbstractTransport.php';
$wgAutoloadClasses['Elastica\Transport\Guzzle'] = $elasticaDir . 'Transport/Guzzle.php';
$wgAutoloadClasses['Elastica\Transport\Http'] = $elasticaDir . 'Transport/Http.php';
$wgAutoloadClasses['Elastica\Transport\Https'] = $elasticaDir . 'Transport/Https.php';
$wgAutoloadClasses['Elastica\Transport\Memcache'] = $elasticaDir . 'Transport/Memcache.php';
$wgAutoloadClasses['Elastica\Transport\Null'] = $elasticaDir . 'Transport/Null.php';
$wgAutoloadClasses['Elastica\Transport\Thrift'] = $elasticaDir . 'Transport/Thrift.php';
$wgAutoloadClasses['Elastica\Type\AbstractType'] = $elasticaDir . 'Type/AbstractType.php';
$wgAutoloadClasses['Elastica\Type\Mapping'] = $elasticaDir . 'Type/Mapping.php';

/**
 * i18n
 */
$wgMessagesDirs['Elastica'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['Elastica'] = $dir . 'Elastica.i18n.php';
