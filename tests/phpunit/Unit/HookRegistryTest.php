<?php

namespace SUC\Tests;

use SUC\HookRegistry;
use SUC\Options;

/**
 * @covers \SUC\HookRegistry
 * @group summary-cards
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class HookRegistryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$options = $this->getMockBuilder( Options::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			HookRegistry::class,
			new HookRegistry( $options )
		);
	}

	public function testRegister() {

		$configuration = array(
			'tooltipRequestCacheTTL'       => 1,
			'cachePrefix'                  => false,
			'enabledNamespaceWithTemplate' => array(),
			'enabledForAnonUsers'          => false,
			'backendParserCacheLifetime'   => 1,
			'backendParserCacheType'       => false
		);

		$instance = new HookRegistry(
			new Options( $configuration )
		);

		$this->doTestRegisteredBeforePageDisplay( $instance );
		$this->doTestRegisteredNewRevisionFromEditComplete( $instance );
		$this->doTestRegisteredGetPreferences( $instance );
		$this->doTestRegisteredResourceLoaderGetConfigVars( $instance );
	}

	public function doTestRegisteredBeforePageDisplay( $instance ) {

		$hook = 'BeforePageDisplay';

		$this->assertTrue(
			$instance->isRegistered( $hook )
		);

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $hook ),
			array( &$outputPage, &$skin )
		);
	}

	public function doTestRegisteredNewRevisionFromEditComplete( $instance ) {

		$hook = 'NewRevisionFromEditComplete';

		$this->assertTrue(
			$instance->isRegistered( $hook )
		);

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$baseId = 0;

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $hook ),
			array( $wikiPage, $revision, $baseId, $user )
		);
	}

	public function doTestRegisteredGetPreferences( $instance ) {

		$hook = 'GetPreferences';

		$this->assertTrue(
			$instance->isRegistered( $hook )
		);

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$preferences = array();

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $hook ),
			array( $user, &$preferences )
		);

		$this->assertArrayHasKey(
			'suc-tooltip-disabled',
			$preferences
		);
	}

	public function doTestRegisteredResourceLoaderGetConfigVars( $instance ) {

		$hook = 'ResourceLoaderGetConfigVars';

		$this->assertTrue(
			$instance->isRegistered( $hook )
		);

		$vars = array();

		$this->assertThatHookIsExcutable(
			$instance->getHandlerFor( $hook ),
			array( &$vars )
		);

		$this->assertArrayHasKey(
			'ext.suc.config',
			$vars
		);
	}

	private function assertThatHookIsExcutable( \Closure $handler, $arguments = array() ) {
		$this->assertInternalType(
			'boolean',
			call_user_func_array( $handler, $arguments )
		);
	}

}
