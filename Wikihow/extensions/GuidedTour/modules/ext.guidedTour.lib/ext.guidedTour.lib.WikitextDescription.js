( function () {
	/**
	 * @class mw.guidedTour.WikitextDescription
	 *
	 * Wikitext to be used as a step description
	 */

	/**
	 * @constructor
	 *
	 * @param {string} wikitext Wikitext to use as a description
	 */
	function WikitextDescription( wikitext ) {
		this.wikitext = wikitext;
	}

	/**
	 * Returns specified wikitext
	 *
	 * @return {string} Wikitext for description
	 */
	WikitextDescription.prototype.getWikitext = function () {
		return this.wikitext;
	};

	mw.guidedTour.WikitextDescription = WikitextDescription;
}() );
