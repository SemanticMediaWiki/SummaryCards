<?php

use SUC\HookRegistry;
use SUC\Options;

/**
 * @see https://github.com/SemanticMediaWiki/SummaryCards/
 *
 * @defgroup SummaryCards Summary Cards
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is part of the SummaryCards extension, it is not a valid entry point.' );
}

if ( version_compare( $GLOBALS[ 'wgVersion' ], '1.25', 'lt' ) ) {
	die( '<b>Error:</b> This version of <a href="https://github.com/SemanticMediaWiki/SummaryCards/">SummaryCards</a> is only compatible with MediaWiki 1.25 or above. You need to upgrade MediaWiki first.' );
}

if ( defined( 'SUC_VERSION' ) ) {
	// Do not initialize more than once.
	return 1;
}

SummaryCards::initExtension();

$GLOBALS['wgExtensionFunctions'][] = function() {
	SummaryCards::onExtensionFunction();
};

/**
 * @codeCoverageIgnore
 */
class SummaryCards {

	/**
	 * @since 1.0
	 */
	public static function initExtension() {

		define( 'SUC_VERSION', '1.0.0-alpha' );

		// Register the extension
		$GLOBALS['wgExtensionCredits']['others'][ ] = array(
			'path'           => __FILE__,
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
		$GLOBALS['wgMessagesDirs']['SummaryCards'] = __DIR__ . '/i18n';

		$GLOBALS['wgAPIModules']['summarycards'] = '\SUC\ApiSummaryCardContentParser';

		$GLOBALS['wgResourceModules']['ext.summary.cards.styles'] = array(
			'styles'  => 'res/ext.summaryCards.css',
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
				'res/ext.summaryCards.js'
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
	}

	/**
	 * @since 1.0
	 */
	public static function onExtensionFunction() {

		$hookRegistry = new HookRegistry(
			Options::newFromGlobals()
		);

		$hookRegistry->register();
	}

	/**
	 * @since 1.0
	 *
	 * @return string|null
	 */
	public static function getVersion() {
		return SUC_VERSION;
	}

}
