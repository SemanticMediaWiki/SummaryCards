<?php

namespace SUC\Tests;

use SUC\ApiCacheableTemplateParse;
use SUC\BackendCache;
use Onoi\BlobStore\BlobStore;
use Onoi\BlobStore\Container;
use ApiMain;
use ApiResult;
use FauxRequest;
use RequestContext;
use WebRequest;
use Title;

/**
 * @covers \SUC\ApiCacheableTemplateParse
 * @group summary-cards
 *
 * @license GNU GPL v2+
 * @since   1.0
 *
 * @author mwjames
 */
class ApiCacheableTemplateParseTest extends \PHPUnit_Framework_TestCase {

	protected function setUp() {
		if ( version_compare( $GLOBALS['wgVersion'], '1.25', '<' ) ) {
			$this->markTestSkipped( "Don't test < 1.25 API due to changes in 1.25." );
		}
	}

	public function testCanConstruct() {

		$backendCache = $this->getMockBuilder( '\SUC\BackendCache' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ApiCacheableTemplateParse(
			$this->newApiMain( array() ),
			'ctparse'
		);

		$instance->setBackendCache( $backendCache );

		$this->assertInstanceOf(
			'SUC\ApiCacheableTemplateParse',
			$instance
		);
	}

	public function testExecuteOnEmptyRequest() {

		$backendCache = $this->getMockBuilder( '\SUC\BackendCache' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ApiCacheableTemplateParse(
			$this->newApiMain( array() ),
			'ctparse'
		);

		$instance->setBackendCache( $backendCache );
		$instance->execute();

		$this->assertEquals(
			array(
				'ctparse' => array(
					'text' => '',
					'time' => false
				),
				'_type'   => 'assoc'
			),
			$instance->getResult()->getResultData()
		);
	}

	public function testExecuteOnNonCachedParseRequest() {

		$container = $this->getMockBuilder( Container::class )
			->disableOriginalConstructor()
			->getMock();

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->atLeastOnce() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$backendCache = $this->getMockBuilder( BackendCache::class )
			->disableOriginalConstructor()
			->getMock();

		$backendCache->expects( $this->any() )
			->method( 'getBlobStore' )
			->will( $this->returnValue( $blobStore ) );

		$backendCache->expects( $this->atLeastOnce() )
			->method( 'getTargetFrom' )
			->will( $this->returnValue( Title::newFromText( __METHOD__ ) ) );

		$params = array(
			'text'  => 'Some text',
			'title' => 'Foo'
		);

		$instance = new ApiCacheableTemplateParse(
			$this->newApiMain( $params ),
			'ctparse'
		);

		$instance->setBackendCache( $backendCache );
		$instance->execute();

		$result = $instance->getResult()->getResultData();

		$this->assertInternalType(
			'float',
			$result['ctparse']['time']['parse']
		);
	}

	private function newApiMain( array $params ) {
		return new ApiMain( $this->newRequestContext( $params ), true );
	}

	private function newRequestContext( $request = array() ) {

		$context = new RequestContext();

		if ( $request instanceof WebRequest ) {
			$context->setRequest( $request );
		} else {
			$context->setRequest( new FauxRequest( $request, true ) );
		}

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$context->setUser( $user );

		return $context;
	}

}
