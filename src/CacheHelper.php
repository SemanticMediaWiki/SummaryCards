<?php

namespace SUC;

use ObjectCache;
use Onoi\Cache\CacheFactory as OnoiCacheFactory;
use Onoi\BlobStore\BlobStore;
use ApiParse;
use ApiBase;
use Title;
use Revision;

/**
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class CacheHelper {

	const VERSION = '0.1';

	/**
	 * @var BlobStore
	 */
	private $blobStore;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @since 1.0
	 *
	 * @param BlobStore $blobStore
	 * @param Options $options
	 */
	public function __construct( BlobStore $blobStore, Options $options ) {
		$this->blobStore = $blobStore;
		$this->options = $options;
	}

	/**
	 * @since 1.0
	 *
	 * @param Options $options
	 *
	 * @return CacheHelper
	 */
	public static function newFromOptions( Options $options ) {

		$cache = OnoiCacheFactory::getInstance()->newMediaWikiCache(
			ObjectCache::getInstance( $options->get( 'backendParserCacheType' ) )
		);

		$blobStore = new BlobStore(
			'summarycards:store',
			$cache
		);

		$blobStore->setNamespacePrefix(
			$options->get( 'cachePrefix' )
		);

		$blobStore->setExpiryInSeconds(
			$options->get( 'backendParserCacheLifetime' )
		);

		return new self( $blobStore, $options );
	}

	/**
	 * @since 1.0
	 *
	 * @param Title $title
	 *
	 * @return string
	 */
	public function getHashFrom( Title $title = null ) {

		$enabledNamespaceWithTemplate = $this->options->get( 'enabledNamespaceWithTemplate' );

		if ( $title === null || !isset( $enabledNamespaceWithTemplate[$title->getNamespace()] ) ) {
			return '';
		}

		return md5(
			self::VERSION .
			$title->getPrefixedDBKey() .
			$enabledNamespaceWithTemplate[$title->getNamespace()] .
			$this->options->get( 'backendParserCacheLifetime' )
		);
	}

	/**
	 * @since 1.0
	 *
	 * @param string $title
	 *
	 * @return Title|null
	 */
	public function newTitleFromText( $title ) {

		$title = Title::newFromText( $title );

		if ( $title === null || $title->hasFragment() ) {
			return $title;
		}

		# Get the article text
		$rev = Revision::newFromTitle( $title, false, Revision::READ_LATEST );

		if ( !is_object( $rev ) ) {
			return $title;
		}

		$content = $rev->getContent();
		# Does the redirect point to the source?
		# Or is it a broken self-redirect, usually caused by namespace collisions?
		return $content && $content->getRedirectTarget() !== null ? $content->getRedirectTarget() : $title;
	}

	/**
	 * @since 1.0
	 *
	 * @return BlobStore
	 */
	public function getBlobStore() {
		return $this->blobStore;
	}

	/**
	 * @since 1.0
	 *
	 * @param Title $title
	 */
	public function invalidateCache( Title $title ) {
		$this->blobStore->delete( $this->getHashFrom( $title ) );
		return true;
	}

}
