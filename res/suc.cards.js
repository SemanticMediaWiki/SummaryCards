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
	var suc = function ( mwApi, util, blobstore ) {

		this.VERSION = "1.0.0";

		this.mwApi = mwApi;
		this.util = util;
		this.blobstore = blobstore;

		this.userLanguage = mw.config.get( 'wgUserLanguage' );
		this.pageContentLanguage = mw.config.get( 'wgPageContentLanguage' );

		this.config = mw.config.get( 'ext.suc.config' );
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
	 * @since  1.0
	 * @method
	 */
	suc.prototype.updateConfig = function() {
		this.userLanguage = mw.config.get( 'wgUserLanguage' );
		this.pageContentLanguage = mw.config.get( 'wgPageContentLanguage' );
		this.config = mw.config.get( 'ext.suc.config' );
	}

	/**
	 * @since  1.0
	 * @method
	 *
	 * @param {Object} context
	 * @param {string} link
	 *
	 * @return {boolean}
	 */
	suc.prototype.isLegitimiateLink = function( context, link ) {

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
		if ( link.indexOf( 'images/archive' ) > -1 || link.indexOf( 'commons/archive' ) > -1 ) {
			return false;
		};

		result = link !== '' &&
			cls !== 'external' && // External url
			cls !== 'external text' && // External url
			cls !== 'external free' && // External url
			cls !== 'image' &&
			cls !== 'new'; // redlinks

		if ( result === false ) {
			return false;
		};

		// Special:Version
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
		if ( self.getTemplate( namespace ) === null ) {
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
	suc.prototype.isEnabled = function() {

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
	suc.prototype.getNormalizedLink = function( href ) {

		if ( href === undefined ) {
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
	suc.prototype.getNamespaceFrom = function( subject ) {
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
	suc.prototype.getTemplate = function( namespace ) {

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
	suc.prototype.getContentFor = function( subject, QTip ) {

		subject = subject.replace( "-20", " " ).replace(/_/g, " " );

		var self = this,
			namespace = self.getNamespaceFrom( subject ),
			template = self.getTemplate( namespace );

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
			if ( self.ttl == 0 || value === null ) {
				self.parse( hash, template, text, subject, QTip );
			} else {
				QTip.set( 'content.text', value );
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
	suc.prototype.parse = function( hash, template, text, subject, QTip ) {

		var self = this;

		self.mwApi.post( {
			action: "ctparse",
			title: subject,
			text: text,
			template: template,
			userlanguage: self.userLanguage
		} ).done( function( data ) {

			console.log( data.ctparse.time );

			// Remove any comments retrieved from the API parse process
			// var text = data.ctparse.text['*'].replace(/<!--[\S\s]*?-->/gm, '' );
			var text = data.ctparse.text.replace(/<!--[\S\s]*?-->/gm, '' );

			QTip.set( 'content.text', text );

			if ( self.ttl > 0 ) {
				self.blobstore.set( hash, text, self.ttl );
			}
		} ).fail ( function( xhr, status, error ) {

			var error = 'Unknown API error';

			if ( status.hasOwnProperty( 'error' ) ) {
				error = status.error.code + ': ' + status.error.info
			};

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
	suc.prototype.tooltip = function( context, link ) {

		var self = this;

		/*
		// Double check, there is no better way to avoid double
		// tooltips due to async load of modules
		var parentClass = context.parent().attr( 'class' ),
			grandparentClass = context.parent().parent().attr( 'class' );

		console.log( parentClass, grandparentClass );
		*/

		context.qtip( {
			content: {
				title :  mw.msg( 'suc-tooltip-title' ),
				text  : function( event, QTip ) {
					self.getContentFor( link, QTip );

					// Show a loading image while waiting on the request result
					return self.util.getBase64LoadingImg( 'suc-tooltip' );
				}
			},
			position: {
				viewport: $( window ),
				my: 'top left',
				at: 'bottom middle'
			},
			show: {
				delay: 500,
				when: {
					event: 'focus'
				}
			},
			hide    : {
				fixed: true,
				delay: 300,
				event: 'unfocus click mouseleave'
			},
			style   : {
				'default': false,
				classes: $( this ).attr( 'class' ) + ' qtip-shadow qtip-bootstrap suc-tooltip',
				def    : false
			}
		} );
	};

	/**
	 * Instance
	 */
	var summaryCards = new suc(
		new mw.Api(),
		new onoi.util(),
		new onoi.blobstore(
			'suc' +  ':' +
			mw.config.get( 'wgUserLanguage' ) + ':' +
			mw.config.get( 'wgCookiePrefix' )
		)
	);

	$( document ).ready( function() {

		if ( !summaryCards.isEnabled() ) {
			return;
		};

		$( '#bodyContent a' ).each( function() {

			var context = $( this ),
				link = summaryCards.getNormalizedLink( context.attr( "href" ) );

			if ( !summaryCards.isLegitimiateLink( context, link ) ) {
				return;
			};

			//summaryCards.updateConfig();
			summaryCards.tooltip( context, link );
		} );

		console.log( summaryCards.linksCount );
	} );

}( jQuery, mediaWiki, onoi ) );
