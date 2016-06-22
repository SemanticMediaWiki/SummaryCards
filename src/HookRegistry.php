<?php

namespace SUC;

use Hooks;

/**
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class HookRegistry {

	/**
	 * @var array
	 */
	private $handlers = array();

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @since 1.0
	 *
	 * @param Options $options
	 */
	public function __construct( Options $options ) {
		$this->options = $options;

		$this->addCallbackHandlers(
			$this->options
		);
	}

	/**
	 * @since  1.0
	 *
	 * @param string $name
	 *
	 * @return boolean
	 */
	public function isRegistered( $name ) {
		return Hooks::isRegistered( $name );
	}

	/**
	 * @since  1.0
	 */
	public function clear() {
		foreach ( $this->handlers as $name => $callback ) {
			Hooks::clear( $name );
		}
	}

	/**
	 * @since  1.0
	 *
	 * @param string $name
	 *
	 * @return Callable|false
	 */
	public function getHandlerFor( $name ) {
		return isset( $this->handlers[$name] ) ? $this->handlers[$name] : false;
	}

	/**
	 * @since  1.0
	 */
	public function register() {
		foreach ( $this->handlers as $name => $callback ) {
			Hooks::register( $name, $callback );
		}
	}

	private function addCallbackHandlers( $options ) {

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
		 */
		$this->handlers['BeforePageDisplay'] = function ( &$outputPage, &$skin ) {

			$outputPage->addModuleStyles( 'ext.summary.cards.styles' );

			$outputPage->addModules(
				array(
					'ext.summary.cards'
				)
			);

			return true;
		};

		/**
		 * Hook: NewRevisionFromEditComplete called when a revision was inserted
		 * due to an edit
		 *
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
		 */
		$this->handlers['NewRevisionFromEditComplete'] = function ( $wikiPage, $revision, $baseId, $user ) {
			return BackendCache::getInstance()->invalidateCache( $wikiPage->getTitle() );
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
		 */
		$this->handlers['GetPreferences'] = function ( $user, &$preferences ) {

			// Option to enable tooltip info
			$preferences['suc-tooltip-disabled'] = array(
				'type'          => 'toggle',
				'label-message' => 'suc-tooltip-disabled',
				'section'       => 'rendering/suc-options',
			);

			return true;
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
		 */
		$this->handlers['ResourceLoaderGetConfigVars'] = function ( &$vars ) use ( $options ) {

			$contentLanguage = $GLOBALS['wgContLang'];
			$enabledNamespaceWithTemplate = array();
			$namespacesByContentLanguage = array();

			foreach ( $options->get( 'enabledNamespaceWithTemplate' ) as $ns => $template ) {
				$enabledNamespaceWithTemplate[$contentLanguage->getNsText( $ns )] = $template;
			}

			// Get literals to match and split when comparing href's during the
			// JS parse process
			foreach ( \MWNamespace::getCanonicalNamespaces() as $ns => $name ) {
				$namespacesByContentLanguage[$contentLanguage->getNsText( $ns )] = $name;
			}

			$vars['ext.suc.config'] = array(
				'tooltipRequestCacheTTL'          => $options->get( 'tooltipRequestCacheTTL' ),
				'cachePrefix'                     => $options->get( 'cachePrefix' ),
				'enabledForAnonUsers'             => $options->get( 'enabledForAnonUsers' ),
				'enabledNamespaceWithTemplate'    => $enabledNamespaceWithTemplate,
				'namespacesByContentLanguage'     => $namespacesByContentLanguage,
				'namespacesByCanonicalIdentifier' => array_flip( $namespacesByContentLanguage )
			);

			return true;
		};
	}

}
