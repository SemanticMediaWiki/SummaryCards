<?php

namespace SUC\Tests;

use SUC\ApiSummaryCardContentParser;
use SUC\CacheHelper;
use Onoi\BlobStore\BlobStore;
use Onoi\BlobStore\Container;
use ApiMain;
use ApiResult;
use FauxRequest;
use RequestContext;
use WebRequest;
use Title;

/**
 * @covers \SUC\ApiSummaryCardContentParser
 * @group summary-cards
 *
 * @license GNU GPL v2+
 * @since   1.0
 *
 * @author mwjames
 */
class ApiSummaryCardContentParserTest extends \PHPUnit_Framework_TestCase {

	protected function setUp() {
		if ( version_compare( $GLOBALS['wgVersion'], '1.25', '<' ) ) {
			$this->markTestSkipped( "Don't test < 1.25 API due to changes in 1.25." );
		}
	}

	public function testCanConstruct() {

		$cacheHelper = $this->getMockBuilder( '\SUC\CacheHelper' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ApiSummaryCardContentParser(
			$this->newApiMain( array() ),
			'summarycards'
		);

		$instance->setCacheHelper( $cacheHelper );

		$this->assertInstanceOf(
			'SUC\ApiSummaryCardContentParser',
			$instance
		);
	}

	public function testExecuteOnEmptyRequest() {

		$cacheHelper = $this->getMockBuilder( '\SUC\CacheHelper' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ApiSummaryCardContentParser(
			$this->newApiMain( array() ),
			'summarycards'
		);

		$instance->setCacheHelper( $cacheHelper );
		$instance->execute();

		$this->assertEquals(
			array(
				'summarycards' => array(
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

		$cacheHelper = $this->getMockBuilder( CacheHelper::class )
			->disableOriginalConstructor()
			->getMock();

		$cacheHelper->expects( $this->any() )
			->method( 'getBlobStore' )
			->will( $this->returnValue( $blobStore ) );

		$cacheHelper->expects( $this->atLeastOnce() )
			->method( 'newTitleFromText' )
			->will( $this->returnValue( Title::newFromText( __METHOD__ ) ) );

		$params = array(
			'text'  => 'Some text',
			'title' => 'Foo'
		);

		$instance = new ApiSummaryCardContentParser(
			$this->newApiMain( $params ),
			'summarycards'
		);

		$instance->setCacheHelper( $cacheHelper );
		$instance->execute();

		$result = $instance->getResult()->getResultData();

		$this->assertInternalType(
			'float',
			$result['summarycards']['time']['parse']
		);
	}

	public function testExecuteOnUntouchedTemplate() {

		$container = $this->getMockBuilder( Container::class )
			->disableOriginalConstructor()
			->getMock();

		$container->expects( $this->atLeastOnce() )
			->method( 'has' )
			->will( $this->returnValue( true ) );

		$blobStore = $this->getMockBuilder( BlobStore::class )
			->disableOriginalConstructor()
			->getMock();

		$blobStore->expects( $this->atLeastOnce() )
			->method( 'read' )
			->will( $this->returnValue( $container ) );

		$cacheHelper = $this->getMockBuilder( CacheHelper::class )
			->disableOriginalConstructor()
			->getMock();

		$cacheHelper->expects( $this->any() )
			->method( 'getBlobStore' )
			->will( $this->returnValue( $blobStore ) );

		$cacheHelper->expects( $this->atLeastOnce() )
			->method( 'newTitleFromText' )
			->will( $this->returnValue( Title::newFromText( __METHOD__ ) ) );

		$params = array(
			'text'  => 'Some text',
			'title' => 'Foo',
			'template' => 'Bar'
		);

		$instance = new ApiSummaryCardContentParser(
			$this->newApiMain( $params ),
			'summarycards'
		);

		$instance->setCacheHelper( $cacheHelper );
		$instance->execute();

		$result = $instance->getResult()->getResultData();

		$this->assertInternalType(
			'float',
			$result['summarycards']['time']['cached']
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
