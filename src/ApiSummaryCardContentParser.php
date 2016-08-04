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
 * it matches the CacheHelper::getHashFrom.
 *
 * On the event that a template that was used for generating content  was modified
 * then a re-parse of the content is requested the next time instead of a cache
 * retrieval.
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class ApiSummaryCardContentParser extends ApiBase {

	/**
	 * @var CacheHelper
	 */
	private $cacheHelper;

	/**
	 * @since 1.0
	 *
	 * @param CacheHelper $cacheHelper
	 */
	public function setCacheHelper( CacheHelper $cacheHelper ) {
		$this->cacheHelper = $cacheHelper;
	}

	/**
	 * @since 1.0
	 *
	 * @return CacheHelper
	 */
	public function getCacheHelper() {

		if ( $this->cacheHelper !== null ) {
			return $this->cacheHelper;
		}

		return $this->cacheHelper = CacheHelper::newFromOptions(
			Options::newFromGlobals()
		);
	}

	/**
	 * ApiBase::execute
	 *
	 * @since 1.0
	 */
	public function execute() {

		$data = $this->getDataFrom(
			$this->extractRequestParams()
		);

 		$this->getResult()->addValue(
 			null,
 			$this->getModuleName(),
 			$data
 		);
	}

	/**
	 * ApiBase::getAllowedParams
	 *
	 * @since 1.0
	 */
	public function getAllowedParams() {
		return array(
			'text'         => array( ApiBase::PARAM_TYPE => 'string' ),
			'template'     => array( ApiBase::PARAM_TYPE => 'string' ),
			'title'        => array( ApiBase::PARAM_TYPE => 'string' ),
			'userlanguage' => array( ApiBase::PARAM_TYPE => 'string' )
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
			'Module to parse raw text/templates and store returning results using a persistent cache, if available.'
		);
	}

	private function getDataFrom( array $params ) {

		$start = microtime( true );
		$isUntouchedTemplate = false;

		$data = array(
			'text' => '',
			'time' => false
		);

		if ( !isset( $params['text'] ) || !isset( $params['title'] ) ) {
			return $data;
		}

		$blobStore = $this->getCacheHelper()->getBlobStore();

		list( $templateKey, $templateTouched ) = $this->getTemplateInfoFrom(
			$params
		);

		$title = $this->getCacheHelper()->newTitleFromText(
			$params['title']
		);

		$hash = $this->getCacheHelper()->getHashFrom( $title );

		$container = $blobStore->read( $hash );

		// If the template was touched then re-parse the content to
		// avoid stalled data
		if ( $container->has( $templateKey ) ) {
			$isUntouchedTemplate = $container->get( $templateKey ) == $templateTouched;
		}

		// Split by lang and fragment into separate containers, while the cache
		// is stored on a per subject basis allowing all related containers to
		// be purged at once
		$contentByLanguageKey = 'L#' . $params['userlanguage'] . '#' . ( $title !== null ? $title->getFragment() : '' );

		if ( $isUntouchedTemplate && $hash !== '' && $container->has( $contentByLanguageKey ) ) {
			wfDebugLog( 'smw', 'SummaryCards API cache hit on ' . $hash );
			$data = $container->get( $contentByLanguageKey );
			$data['time']['cached'] = microtime( true ) - $start;
			return $data;
		}

		$data = array(
			'text' => $this->doParse( $title, $params['userlanguage'], $params['text'] ),
			'time' => array(
				'parse'  => microtime( true ) - $start
			)
		);

		// Only cache when a template is known
		if ( $hash !== '' && $templateKey !== '' ) {
			$container->set( $contentByLanguageKey, $data );
			$container->set( $templateKey, $templateTouched );
			$blobStore->save( $container );
		}

		return $data;
	}

	private function getTemplateInfoFrom( $params ) {

		$templateKey = '';
		$templateTouched = 0;

		if ( isset( $params['template'] ) ) {
			$template = Title::makeTitleSafe( NS_TEMPLATE, $params['template'] );
			$templateTouched = $template->getTouched();
			$templateKey = 'T#' . $params['template'] . '#' . $params['userlanguage'];
		}

		return array( $templateKey, $templateTouched );
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
