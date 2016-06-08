<?php

use SUC\HookRegistry;
use SUC\Options;

/**
 * @see https://github.com/SemanticMediaWiki/SummaryCards/
 *
 * @defgroup suc Semantic Citation
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is part of the SummaryCards extension, it is not a valid entry point.' );
}

if ( version_compare( $GLOBALS[ 'wgVersion' ], '1.24', 'lt' ) ) {
	die( '<b>Error:</b> This version of <a href="https://github.com/SemanticMediaWiki/SummaryCards/">SummaryCards</a> is only compatible with MediaWiki 1.24 or above. You need to upgrade MediaWiki first.' );
}

if ( defined( 'SUC_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

define( 'SUC_VERSION', '1.0.0-alpha' );

/**
 * @codeCoverageIgnore
 */
call_user_func( function () {

	// mediawiki/link-summary-cards
	// mediawiki/summary-hovercards

	// Register the extension
	$GLOBALS['wgExtensionCredits']['others'][ ] = array(
		'path'           => __DIR__,
		'name'           => 'Summary Cards',
		'author'         => array(
			'James Hong Kong'
			),
		'url'            => 'https://github.com/SemanticMediaWiki/SummaryCards/',
		'descriptionmsg' => 'suc-desc',
		'version'        => SUC_VERSION,
		'license-name'   => 'GPL-2.0+',
	);

	// Register message files
	$GLOBALS['wgMessagesDirs']['summary-cards'] = __DIR__ . '/i18n';

	$GLOBALS['wgResourceModules']['ext.summary.cards.styles'] = array(
		'styles'  => 'res/ext.suc.styles.css',
		'localBasePath' => __DIR__ ,
		'remoteExtPath' => 'SummaryCards',
		'position' => 'top',
		'targets' => array(
			'mobile',
			'desktop'
		)
	);

	$GLOBALS['wgResourceModules']['ext.summary.cards.tooltip'] = array(
		'localBasePath' => __DIR__ ,
		'remoteExtPath' => 'SummaryCards',
		'position' => 'bottom',
		'dependencies'  => array(
			'onoi.qtip',
			'onoi.md5',
			'onoi.blobstore',
			'onoi.util'
		),
		'targets' => array(
			'mobile',
			'desktop'
		)
	);

	$GLOBALS['wgResourceModules']['ext.summary.cards'] = array(
		'scripts' => array(
			'res/ext.suc.cards.js'
		),
		'localBasePath' => __DIR__ ,
		'remoteExtPath' => 'SummaryCards',
		'position' => 'bottom',
		'dependencies'  => array(
			'mediawiki.api',
			'mediawiki.api.parse',
			'ext.summary.cards.styles',
			'ext.summary.cards.tooltip',
		),
		'messages' => array(
			'suc-tooltip-title',
			'suc-tooltip-error'
		),
		'targets' => array(
			'mobile',
			'desktop'
		)
	);

	/**
	 * Only enabled namespaces with a template assignment
	 * are used to display a summary card
	 */
	$GLOBALS['sucgEnabledNamespaceWithTemplate'] = array();

	/**
	 * Whether anon users are able to see summary cards or not.
	 */
	$GLOBALS['sucgEnabledForAnonUser'] = true;

	/**
	 * Setting to regulate the local client caching of responses received from
	 * the API
	 *
	 * @default: 30 min, false to disable the cache
	 */
	$GLOBALS['sucgTooltipRequestCacheLifetime'] = 60 * 30;

	/**
	 * This cache is to serve already parsed result from a cache layer without
	 * requiring to parse a template and itscontent again. It allows to serve
	 * all users that request data for the same subject from a central cache
	 * which is independent from a browser (see sucgTooltipRequestCacheLifetime).
	 *
	 * Changes to a template or alteration of a subject will trigger a new parse
	 * of content before retrievng them from cache.
	 *
	 * @default: 24 h, CACHE_NONE to disable the cache
	 */
	$GLOBALS['sucgBackendParserCacheType'] = CACHE_NONE;

	/**
	 * @default: 1d, false to disable the cache
	 */
	$GLOBALS['sucgBackendParserCacheLifetime'] = 60 * 60 * 24;

	// Finalize registration process
	$GLOBALS['wgExtensionFunctions'][] = function() {

		$GLOBALS['wgAPIModules']['ctparse'] = '\SUC\ApiCacheableTemplateParse';

		$options = new Options();
		$options->init();

		$hookRegistry = new HookRegistry(
			$options
		);

		$hookRegistry->register();
	};

} );
