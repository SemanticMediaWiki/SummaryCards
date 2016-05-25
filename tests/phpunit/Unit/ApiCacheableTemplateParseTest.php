<?php

namespace SUC\Tests;

use SUC\ApiCacheableTemplateParse;
use ApiMain;
use ApiResult;
use FauxRequest;
use RequestContext;
use WebRequest;

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
