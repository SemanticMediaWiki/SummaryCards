/**
 * JS for the suc extension
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */

/*global jQuery, mediaWiki, onoi */
/*global confirm */

( function ( $, mw, onoi ) {

	'use strict';

	/**
	 * @since  1.0
	 * @constructor
	 *
	 * @param {Object} mwApi
	 * @param {Object} util
	 * @param {Object} blobstore
	 *
	 * @return {this}
	 */
	var summaryCards = function ( mwApi, util, blobstore ) {

		this.VERSION = "1.0.0";

		this.mwApi = mwApi;
		this.util = util;
		this.blobstore = blobstore;

		this.userLanguage = mw.config.get( 'wgUserLanguage' );
		this.pageContentLanguage = mw.config.get( 'wgPageContentLanguage' );

		this.config = mw.config.get( 'ext.summaryCards.config' );
		this.articlePath = mw.config.get( 'wgArticlePath' ).replace( '$1', '' );

		this.enabledNamespaceWithTemplate = this.config.enabledNamespaceWithTemplate;
		this.ttl = 300;

		if ( this.config.hasOwnProperty( 'tooltipRequestCacheTTL' ) ) {
			this.ttl = parseInt( this.config.tooltipRequestCacheTTL );
		};

		this.linksCount = 0;

		return this;
	};

	/**
	 * Whether a link is legitimate for displaying a summary card or not.
	 *
	 * The listed rule-sets have been gather while observing a wiki, so it is
	 * possible that certain links are not yet correctly categorized as
	 * illegitimate.
	 *
	 * @since  1.0
	 * @method
	 *
	 * @param {Object} context
	 * @param {string} link
	 *
	 * @return {boolean}
	 */
	summaryCards.prototype.isLegitimiateLink = function( context, link ) {

		var self = this,
			cls = context.attr( 'class' ),
			result = false;

		if ( link === undefined ) {
			return false;
		};

		// link with & indicates a quey or action link
		// First # indicates a fragment link
		if ( link.indexOf( "&" ) > 0 || link.charAt( 0 ) === '#' ) {
			return false;
		};

		// Image archive links (what a mess but there is no class to check against)
		if (
			link.indexOf( 'images/archive' ) > -1 ||
			link.indexOf( 'commons/archive' ) > -1 ||
			link.indexOf( 'commons.wikimedia.org' ) > -1 ) {
			return false;
		};

		result = link !== '' &&
			cls !== 'image' &&
			cls !== 'extiw' && // interwiki
			cls !== 'new'; // redlinks

		if ( result === false ) {
			return false;
		};

		// External urls in various shades
		if ( cls !== undefined && cls.indexOf( 'external' ) > -1 ) {
			return false;
		};

		// Simple traversal filtering
		var parentClass = context.parent().attr( 'class' ),
			grandparentClass = context.parent().parent().attr( 'class' ),
			previousClass = context.parent().prev().attr( 'class' );

		// SBL
		if ( parentClass !== undefined && parentClass.indexOf( 'sbl-breadcrumb' ) > -1 ) {
			return false;
		}

		if ( grandparentClass !== undefined && grandparentClass.indexOf( 'smw-highlighter' ) > -1 ) {
			return false;
		}

		// Avoid cards over and existing SMW generated
		// info or other MW links
		if ( ( parentClass === 'smwtext' && grandparentClass === 'smw-highlighter' ) ||
			( grandparentClass === 'smwb-title' ) || // Special:Browse
			( grandparentClass === 'smwrdflink' ) || // Factbox
			( grandparentClass === 'smwfactboxhead' ) || // Factbox
			( parentClass === 'cancelLink' ) || // Edit form
			( parentClass === 'smwsearch' ) || // Factbox
			( parentClass === 'fullImageLink' ) || // Image page
			( parentClass === 'fullMedia' ) || // Image page
			( parentClass === 'filehistory-selected' ) ||
			( previousClass === 'filehistory-selected' ) || // Image page
			( parentClass === 'mw-usertoollinks' )  // Image page
		 ) {
			return false;
		};

		var namespace = self.getNamespaceFrom( link );

		// No card to SpecialPages
		if ( namespace === self.config.namespacesByCanonicalIdentifier['Special'] ) {
			return false;
		};

		// No template, no card
		if ( self.getTemplateNameFrom( namespace ) === null ) {
			return false;
		};

		self.linksCount++;

		return true;
	};

	/**
	 * @since  1.0
	 * @method
	 *
	 * @return {boolean}
	 */
	summaryCards.prototype.isEnabled = function() {

		if ( mw.config.get( 'wgUserName' ) === null ) {
			return this.config.enabledForAnonUsers;
		};

		return mw.user.options.get( 'suc-tooltip-disabled' ) !== '1';
	};

	/**
	 * @since  1.0
	 * @method
	 *
	 * @param {string} href
	 *
	 * @return {string}
	 */
	summaryCards.prototype.getNormalizedLink = function( href ) {

		if ( href === undefined ) {
			return '';
		};

		// Need to handle "redirect" before matching the articlePath
		if ( href.indexOf( '&redirect=no' ) > -1 ) {
			return href.substring( href.indexOf( "=" ) + 1, href.lastIndexOf( "&" ) );
		};

		// If the articlePath could not be found as part of the href then we
		// expect it to be an external or interwiki link and is therefore
		// disqualified by default
		if ( href.indexOf( this.articlePath ) < 0 ) {
			return '';
		};

		return decodeURIComponent( href.replace( this.articlePath, "" ) );
	}

	/**
	 * @since  1.0
	 * @method
	 *
	 * @param {string} subject
	 *
	 * @return {string}
	 */
	summaryCards.prototype.getNamespaceFrom = function( subject ) {
		var namespace = subject.split( ( subject.indexOf( '%3A' ) >= 0 ? '%3A': ':' ) );
		namespace = namespace.length > 1 ? namespace[0] : '';

		// Check for Foo:bar where Foo is not a matchable NS
		return this.config.namespacesByContentLanguage.hasOwnProperty( namespace ) ? namespace : '';
	}

	/**
	 * @since  1.0
	 * @method
	 *
	 * @param {string} namespace
	 *
	 * @return {string}
	 */
	summaryCards.prototype.getTemplateNameFrom = function( namespace ) {

		if ( this.enabledNamespaceWithTemplate.hasOwnProperty( namespace ) ) {
			return this.enabledNamespaceWithTemplate[namespace]
		};

		return null;
	}

	/**
	 * @since  1.0
	 * @method
	 *
	 * @param {string} subject
	 * @param {Object} QTip
	 */
	summaryCards.prototype.getContentsFor = function( subject, QTip ) {

		subject = subject.replace( "-20", " " ).replace(/_/g, " " );

		var self = this,
			namespace = self.getNamespaceFrom( subject ),
			template = self.getTemplateNameFrom( namespace );

		var text = '{{'  + template +
			'|subject='    + subject +
			'|namespace='  + namespace +
			'|isFile='     + ( self.config.namespacesByCanonicalIdentifier['File'] === namespace ) +
			'|isProperty=' + ( self.config.namespacesByCanonicalIdentifier['Property'] === namespace ) +
			'|isCategory=' + ( self.config.namespacesByCanonicalIdentifier['Category'] === namespace ) +
			'|userLanguage='        + self.userLanguage +
			'|pageContentLanguage=' + self.pageContentLanguage +
		'}}';

		var hash = self.util.md5( subject + self.VERSION );

		// Async process
		self.blobstore.get( hash, function( value ) {
			if ( self.ttl == 0 || value === null || value === '' ) {
				self.doApiRequest( hash, template, text, subject, QTip );
			} else {
				QTip.set(
					'content.title',
					mw.msg( 'suc-tooltip-title' ) + '<span class="suc-tooltip-cache-indicator suc-tooltip-cache-browser"></span>'
				);

				QTip.set(
					'content.text',
					value
				);
			}
		} );
	}

	/**
	 * @since  1.0
	 * @method
	 *
	 * @param {string} has
	 * @param {string} template
	 * @param {Object} QTip
	 */
	summaryCards.prototype.doApiRequest = function( hash, template, text, subject, QTip ) {

		var self = this;

		self.mwApi.get( {
			action: "summarycards",
			title: subject,
			text: text,
			template: template,
			userlanguage: self.userLanguage
		} ).done( function( data ) {

			// Remove any comments retrieved from the API parse process
			// var text = data.ctparse.text['*'].replace(/<!--[\S\s]*?-->/gm, '' );
			var text = data.summarycards.text.replace(/<!--[\S\s]*?-->/gm, '' );

			if ( data.summarycards.time.cached !== undefined ) {
				QTip.set( 'content.title', mw.msg( 'suc-tooltip-title' ) + '<span class="suc-tooltip-cache-indicator suc-tooltip-cache-backend"></span>' );
			};

			QTip.set( 'content.text', text );

			if ( self.ttl > 0 ) {
				self.blobstore.set( hash, text, self.ttl );
			}
		} ).fail ( function( xhr, status, error ) {

			var error = 'Unknown API error';

			if ( status.hasOwnProperty( 'error' ) ) {
				error = status.error.code + ': ' + status.error.info
			};

			QTip.set( 'content.title', mw.msg( 'suc-tooltip-error' ) );
			QTip.set( 'content.text', error );
		} );
	};

	/**
	 * @since  1.0
	 * @method
	 *
	 * @param {Object} context
	 * @param {string} link
	 */
	summaryCards.prototype.createTooltip = function( context, link ) {

		var self = this;

		context.qtip( {
			content: {
				title : mw.msg( 'suc-tooltip-title' ),
				text  : function( event, QTip ) {
					self.getContentsFor( link, QTip );

					// Show a loading image while waiting on the request result
					return self.util.getLoadingImg( 'suc-tooltip', 'dots' );
				}
			},
			position: {
				viewport: $( window ),
				my: 'top left',
				at: 'bottom middle'
			},
			show: {
				delay: 800
			//	when: {
			//		event: 'focus'
			//	}
			},
			hide    : {
				fixed: true,
				delay: 300,
				event: 'unfocus click mouseleave'
			},
			style   : {
				'default': false,
				classes: 'summary-cards qtip-shadow qtip-bootstrap suc-tooltip',
				def    : false
			}
		} );
	};

	/**
	 * @since  1.0
	 * @method
	 *
	 * @param {Object} context
	 */
	summaryCards.prototype.createCard = function( context ) {

		var link = this.getNormalizedLink( context.attr( "href" ) );

		if ( !this.isLegitimiateLink( context, link ) ) {
			return;
		};

		this.createTooltip( context, link );
	};

	/**
	 * @since  1.0
	 * @method
	 *
	 * @param {Object} context
	 */
	summaryCards.prototype.initCardsFromContext = function( context ) {

		var self = this;

		context.find( 'a' ).each( function() {
			self.createCard( $( this ) );
		} );
	};

	/**
	 * @since  1.0
	 * @method
	 */
	summaryCards.prototype.registerEventListeners = function() {

		var self = this;

		if ( !self.isEnabled() ) {
			return;
		};

		// Listen to the Special:Browse event
		$ ( document ).on( 'SMW::Browse::ApiParseComplete', function( event, opts ) {
			 self.initCardsFromContext( opts.context );
		} );
	};

	/**
	 * Factory
	 */
	var Factory = {
		newSummaryCards: function() {
			var instance;

			instance = new summaryCards(
				new mw.Api(),
				new onoi.util(),
				new onoi.blobstore(
					'summary-cards' +  ':' +
					mw.config.get( 'wgCookiePrefix' ) + ':' +
					mw.config.get( 'wgUserLanguage' )
				)
			);

			return instance;
		}
	}

	// Register addEventListeners early on
	var instance = Factory.newSummaryCards();
	instance.registerEventListeners();

	$( document ).ready( function() {

		if ( !instance.isEnabled() ) {
			return;
		};

		$( '#bodyContent a' ).each( function() {
			instance.createCard( $( this ) );
		} );
	} );

// Assign namespace
window.summaryCards = Factory;

}( jQuery, mediaWiki, onoi ) );
