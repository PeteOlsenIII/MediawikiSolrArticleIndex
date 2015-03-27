<?php
/**
 * SolrArticleIndex extension - lets users select custom javascript gadgets
 *
 * For more info see http://mediawiki.org/wiki/Extension:SolrArticleIndex
 *
 * @file
 * @ingroup Extensions
 * @author Pete Olsen
 * @copyright © 2014 Pete Olsen peteolsen@gmail.com
 * @license GNU General Public Licence 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

if ( version_compare( $wgVersion, '0.1', '<' ) ) {
	die( "This version of Extension:SolrArticleIndex requires MediaWiki 1.19+\n" );
}

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Solr Index Article',
	'author' => array( 'Pete Olsen' ),
	'url' => 'https://mediawiki.org/wiki/Extension:SolrArticleIndex',
	'descriptionmsg' => 'solrindexarticle-desc',
);

$wgHooks['ArticleInsertComplete'][] = 'SolrArticleIndexHooks::articleInsertComplete';
$wgHooks['ArticleSaveComplete'][]   = 'SolrArticleIndexHooks::articleSaveComplete';
$wgHooks['ArticleDeleteComplete'][] = 'SolrArticleIndexHooks::articleDeleteComplete';
$wgHooks['TitleMoveComplete'][]     = 'SolrArticleIndexHooks::titleMoveComplete';

$dir = dirname( __FILE__ ) . '/';
$wgAutoloadClasses['SolrArticleIndex'] = $dir . 'SolrArticleIndex_body.php';
$wgAutoloadClasses['SolrArticleIndexHooks'] = $dir . 'SolrArticleIndex_body.php';
$wgAutoloadClasses['SorlIndexArticleResourceLoaderModule'] = $dir . 'SolrArticleIndex_body.php';

