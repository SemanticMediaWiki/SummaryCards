<?php

namespace SUC\Tests;

use SUC\CacheHelper;
use SUC\Options;
use Onoi\BlobStore\BlobStore;
use Title;

/**
 * @covers \SUC\CacheHelper
 * @group summary-cards
 *
 * @license GNU GPL v2+
 * @since   1.0
 *
 * @author mwjames
 */
class CacheHelperTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$options = $this->getMockBuilder( Options::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			CacheHelper::class,
			new CacheHelper( $blobStore, $options )
		);

		$this->assertInstanceOf(
			CacheHelper::class,
			CacheHelper::newFromOptions( Options::newFromGlobals() )
		);
	}

	public function testGetHashFromForNonMatchableNamespace() {

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$options = new Options(
			array( 'enabledNamespaceWithTemplate' => array( NS_CATEGORY => 'Foo' ) )
		);

		$instance = new CacheHelper(
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

		$instance = new CacheHelper(
			$blobStore,
			$options
		);

		$this->assertInternalType(
			'string',
			$instance->getHashFrom( Title::newFromText( __METHOD__, NS_CATEGORY ) )
		);
	}

	public function testTitleFromTextForNonFragment() {

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$options = $this->getMockBuilder( Options::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new CacheHelper(
			$blobStore,
			$options
		);

		$this->assertInstanceOf(
			Title::class,
			$instance->newTitleFromText( __METHOD__ )
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

		$instance = new CacheHelper(
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
