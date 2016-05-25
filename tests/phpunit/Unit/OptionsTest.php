<?php

namespace SUC\Tests;

use SUC\Options;

/**
 * @covers \SUC\Options
 * @group summary-cards
 *
 * @license GNU GPL v2+
 * @since   1.0
 *
 * @author mwjames
 */
class OptionsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SUC\Options',
			new Options()
		);
	}

	public function testAddOption() {

		$instance = new Options();

		$this->assertFalse(
			$instance->has( 'Foo' )
		);

		$instance->set( 'Foo', 42 );

		$this->assertEquals(
			42,
			$instance->get( 'Foo' )
		);
	}

	/**
	 * @dataProvider initProvider
	 */
	public function testInit( $name ) {

		$instance = new Options();
		$instance->init();

		$this->assertTrue(
			$instance->has( $name )
		);
	}

	public function testUnregisteredKeyThrowsException() {

		$instance = new Options();

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->get( 'Foo' );
	}

	public function initProvider() {

		$provider[] = array(
			'tooltipRequestCacheTTL'
		);

		$provider[] = array(
			'cachePrefix'
		);

		$provider[] = array(
			'enabledNamespaceWithTemplate'
		);

		$provider[] = array(
			'enabledForAnonUsers'
		);

		$provider[] = array(
			'backendParserCacheLifetime'
		);

		$provider[] = array(
			'backendParserCacheType'
		);

		return $provider;
	}

}
