<?php

namespace SUC\Tests;

use SUC\BackendCache;
use SUC\Options;
use Onoi\BlobStore\BlobStore;
use Title;

/**
 * @covers \SUC\BackendCache
 * @group summary-cards
 *
 * @license GNU GPL v2+
 * @since   1.0
 *
 * @author mwjames
 */
class BackendCacheTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$options = $this->getMockBuilder( Options::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			BackendCache::class,
			new BackendCache( $blobStore, $options)
		);

		$this->assertInstanceOf(
			BackendCache::class,
			BackendCache::getInstance()
		);

		BackendCache::clear();
	}

	public function testConstructFromInject() {

		$backendCache = $this->getMockBuilder( BackendCache::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertSame(
			$backendCache,
			BackendCache::getInstance( $backendCache )
		);
	}

	public function testGetHashFromForNonMatchableNamespace() {

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$options = new Options(
			array( 'enabledNamespaceWithTemplate' => array( NS_CATEGORY => 'Foo' ) )
		);

		$instance = new BackendCache(
			$blobStore,
			$options
		);

		$this->assertInternalType(
			'string',
			$instance->getHashFrom( Title::newFromText( __METHOD__ ) )
		);
	}

	public function testGetHashFromForMatchableNamespace() {

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$options = new Options(
			array(
				'enabledNamespaceWithTemplate' => array( NS_CATEGORY => 'Foo' ),
				'backendParserCacheLifetime'   => 50
			)
		);

		$instance = new BackendCache(
			$blobStore,
			$options
		);

		$this->assertInternalType(
			'string',
			$instance->getHashFrom( Title::newFromText( __METHOD__, NS_CATEGORY ) )
		);
	}

	public function testGetTargetFromForNonFragment() {

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$options = $this->getMockBuilder( Options::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new BackendCache(
			$blobStore,
			$options
		);

		$this->assertInstanceOf(
			Title::class,
			$instance->getTargetFrom( __METHOD__ )
		);
	}

	public function testInvalidateCache() {

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->once() )
			->method( 'delete' );

		$options = $this->getMockBuilder( Options::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new BackendCache(
			$blobStore,
			$options
		);

		$this->assertInstanceOf(
			BlobStore::class,
			$instance->getBlobStore()
		);

		$instance->invalidateCache( Title::newFromText( __METHOD__ ) );
	}

}
