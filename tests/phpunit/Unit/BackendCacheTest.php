<?php

namespace SUC\Tests;

use SUC\BackendCache;

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

		$blobStore = $this->getMockBuilder( '\Onoi\BlobStore\BlobStore' )
			->disableOriginalConstructor()
			->getMock();

		$options = $this->getMockBuilder( '\SUC\Options' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SUC\BackendCache',
			new BackendCache( $blobStore, $options)
		);

		$this->assertInstanceOf(
			'\SUC\BackendCache',
			BackendCache::getInstance()
		);

		BackendCache::clear();
	}

}
