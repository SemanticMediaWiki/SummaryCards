<?php

/**
 * DO NOT EDIT!
 *
 * The following default settings are to be used by the extension itself,
 * please modify settings in the LocalSettings file.
 *
 * @codeCoverageIgnore
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is part of the SummaryCards extension, it is not a valid entry point.' );
}

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
