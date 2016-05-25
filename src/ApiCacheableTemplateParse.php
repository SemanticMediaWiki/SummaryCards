<?php

namespace SUC;

use ParserOptions;
use ParserOutput;
use ApiBase;
use Title;

/**
 * Content parsing can be expensive especially if it contains a large set of
 * #ask queries/#if parser functions to form a summary card.
 *
 * This API module is to parse a text/template and if possible tries to retrieve
 * data from a persistent cache layer to avoid unnecessary content parsing.
 *
 * On the event of NewRevisionFromEditComplete a cached item will be evicted if
 * it matches the BackendCache::getHashFrom.
 *
 * On the event that a template that was used for generating content  was modified
 * then a re-parse of the content is requested the next time instead of a cache
 * retrievable.
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class ApiCacheableTemplateParse extends ApiBase {

	/**
	 * @var BackendCache
	 */
	private $backendCache;

	/**
	 * @since 1.0
	 *
	 * @param BackendCache $backendCache
	 */
	public function setBackendCache( BackendCache $backendCache ) {
		$this->backendCache = $backendCache;
	}

	/**
	 * ApiBase::execute
	 *
	 * @since 1.0
	 */
	public function execute() {

		if ( $this->backendCache === null ) {
			$this->backendCache = BackendCache::getInstance();
		}

		$data = $this->getDataFrom(
			$this->extractRequestParams()
		);

 		$this->getResult()->addValue(
 			null,
 			$this->getModuleName(),
 			$data
 		);
	}

	public function getAllowedParams() {
		return array(
			'text'         => array( ApiBase::PARAM_TYPE => 'text' ),
			'template'     => array( ApiBase::PARAM_TYPE => 'text' ),
			'title'        => array( ApiBase::PARAM_TYPE => 'text' ),
			'userlanguage' => array( ApiBase::PARAM_TYPE => 'text' )
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getParamDescription
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'text'     => 'Contains the text/template to be parsed.',
			'template' => 'Contains the template to track possible changes.',
			'title'    => 'Subject to filter repeated requests.',
			'userlanguage' => 'The user language.'
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getDescription
	 *
	 * @return array
	 */
	public function getDescription() {
		return array(
			'Module to parse raw text/templates and store returning results in a backend-cache.'
		);
	}

	private function getDataFrom( $params ) {

		$start = microtime( true );
		$untouchedTemplate = false;

		$data = array(
			'text' => '',
			'time' => false
		);

		if ( !isset( $params['text'] ) || !isset( $params['title'] ) ) {
			return $data;
		}

		$blobStore = $this->backendCache->getBlobStore();

		list( $templateKey, $templateTouched ) = $this->getTemplateFrom( $params );

		$title = $this->backendCache->getTargetFrom( $params['title'] );
		$hash = $this->backendCache->getHashFrom( $title );

		$container = $blobStore->read( $hash );

		// If the template was touched then re-parse the content to
		// avoid stalled data
		if ( $container->has( $templateKey ) ) {
			$untouchedTemplate = $container->get( $templateKey ) == $templateTouched;
		}

		// Split by lang and fragment as separate container, cache is stored on
		// a per subject basis allowing all relateed containers to evict at once
		$key = 'L#' . $params['userlanguage'] . '#' . ( $title !== null ? $title->getFragment() : '' );

		if ( $untouchedTemplate && $hash !== '' && $container->has( $key ) ) {
			wfDebugLog( 'smw', 'SummaryCards API cache hit on ' . $hash );
			$data = $container->get( $key );
			$data['time']['cached'] = microtime( true ) - $start;
			return $data;
		}

		$data = array(
			'text' => $this->doParse( $title, $params['userlanguage'], $params['text'] ),
			'time' => array(
				'parse'  => microtime( true ) - $start
			)
		);

		// Only cache where template is known is it is more traceable
		// and trackable then simple free text
		if ( $hash !== '' && $templateKey !== '' ) {
			$container->set( $key, $data );
			$container->set( $templateKey, $templateTouched );
			$blobStore->save( $container );
		}

		return $data;
	}

	private function getTemplateFrom( $params ) {

		$template = '';
		$templateTouched = 0;

		if ( isset( $params['template'] ) ) {
			$template = Title::makeTitleSafe( NS_TEMPLATE, $params['template'] );
			$templateTouched = $template->getTouched();
			$template = 'T#' . $params['template'] . '#' . $params['userlanguage'];
		}

		return array( $template, $templateTouched );
	}

	private function doParse( $title, $userLanguage, $text ) {

		$parserOutput = $GLOBALS['wgParser']->parse(
			$text,
			$title,
			$this->makeParserOptions( $title, $userLanguage )
		);

		return $parserOutput instanceof ParserOutput ? $parserOutput->getText() : '' ;
	}

	private function makeParserOptions( $title, $userLanguage ) {

		$user = null;

		$parserOptions = new ParserOptions( $user );
		$parserOptions->setInterfaceMessage( true );
		$parserOptions->setUserLang( $userLanguage );

		return $parserOptions;
	}

}
