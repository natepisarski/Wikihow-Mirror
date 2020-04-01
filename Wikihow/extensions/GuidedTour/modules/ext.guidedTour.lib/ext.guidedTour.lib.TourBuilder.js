( function () {
	var gt = mw.guidedTour;

	/**
	 * @class mw.guidedTour.TourBuilder
	 *
	 * A builder for defining a guided tour
	 */

	/**
	 * Constructs a TourBuilder, backed by a tour, based on an object specifying it.
	 *
	 * The newly constructed object can be further configured with fluent methods,
	 * such as #step
	 *
	 * @constructor
	 *
	 * @param {Object} tourSpec Specification of tour
	 * @param {string} tourSpec.name Name of tour; must match module or wiki page name
	 *
	 * @param {boolean} [tourSpec.isSinglePage=false] Tour is used on a single
	 *  page tour. This disables tour cookies.
	 * @param {string} [tourSpec.showConditionally] condition for showing
	 *  tour.  Currently, the supported conditions are:
	 *
	 *  - 'stickToFirstPage' - Only show on pages with the same article ID (non-
	 *    special pages) or page name (special pages) as the first page it showed
	 *    on.
	 *  - 'wikitext' - Show on pages that are part of a wikitext flow.  This
	 *    means all pages where the VisualEditor is not open.
	 *  - 'VisualEditor' - Show on pages that are part of the VisualEditor flow.
	 *    This means all pages, except for the wikitext editor, wikitext preview,
	 *    and wikitext show changes.
	 * @param {boolean} [tourSpec.shouldLog=false] Whether to log events to
	 *  EventLogging
	 *
	 * @throws {mw.guidedTour.TourDefinitionError} If tourSpec is missing or the tour
	 *  is unnamed
	 */
	function TourBuilder( tourSpec ) {
		if ( !$.isPlainObject( tourSpec ) || arguments.length !== 1 ) {
			throw new gt.TourDefinitionError( 'Check your syntax. There must be exactly one argument, \'tourSpec\', which must be an object.' );
		}

		if ( $.type( tourSpec.name ) !== 'string' ) {
			throw new gt.TourDefinitionError( '\'tourSpec.name\' must be a string, the tour name.' );
		}

		/**
		 * Tour being built by this TourBuilder
		 *
		 * @property {mw.guidedTour.Tour}
		 * @private
		 */
		this.tour = new gt.Tour( tourSpec );
	}

	/**
	 * Creates and returns a new StepBuilder, used to build a step of the tour.  The
	 * StepBuilder will be bound to the tour being built.
	 *
	 * @param {Object} stepSpec specification for this step of the tour
	 * @param {string} stepSpec.name Step name.  This identifier is not displayed to
	 *   the end user. but can be used as a reference to a step
	 * @param {string} stepSpec.title Title of guider.  Used only
	 *  for on-wiki tours
	 * @param {string} stepSpec.titlemsg Message key for title of
	 *  guider.  Used only for extension-defined tours
	 *
	 * @param {string|mw.guidedTour.WikitextDescription|mw.Title} stepSpec.description
	 *  Description of guider.  A string is treated as HTML, except that for
	 *  backwards compatibility, if onShow is gt.parseDescription or
	 *  gt.getPageAsDescription, the string is interpreted as described in onShow.
	 * @param {string} stepSpec.descriptionmsg Message key for
	 *  description of guider.  Used only for extension-defined tours.
	 *
	 * @param {string|Object} stepSpec.position A positional string specifying
	 *  what part of the element the guider attaches to.  One of 'topLeft',
	 *  'top', 'topRight', 'rightTop', 'right', 'rightBottom', 'bottomRight',
	 *  'bottom', 'bottomLeft', 'leftBottom', 'left', 'leftTop'
	 *
	 *  Or:
	 *
	 *     {
	 *         fallback: 'defaultPosition'
	 *         particularSkin: 'otherPosition',
	 *         anotherSkin: 'anotherPosition'
	 *     }
	 *
	 *  particularSkin should be replaced with a MediaWiki skin name, such as
	 *  monobook.  There can be entries for any number of skins.
	 *  'defaultPosition' is used if there is no custom value for a skin.
	 *
	 *  The position is automatically horizontally flipped if needed (LTR/RTL
	 *  interfaces).
	 *
	 * @param {string|Object|jQuery} stepSpec.attachTo The selector for an element to
	 *  attach to, a jQuery-wrapped node, or an object for that purpose with the same
	 *  format as position.  The values within the structure can also be selectors or
	 *  jQuery-wrapped nodes.
	 *
	 * @param {number} [stepSpec.width=400] Width, in pixels.
	 *
	 * @param {Function} [stepSpec.onShow] Function to execute immediately
	 *  before the guider is shown.  Using this for gt.parseDescription or
	 *  gt.getPageAsDescription is deprecated.  However, a string value of description
	 *  is interpreted as follows:
	 *
	 *  - gt.parseDescription - Treat description as wikitext
	 *  - gt.getPageAsDescription - Treat description as the name of a description
	 *    page on the wiki
	 *
	 * @param {boolean} [stepSpec.allowAutomaticOkay=true] By default, if
	 * you do not specify an Okay or Next button, an Okay button will be generated.
	 *
	 * To suppress this, set allowAutomaticOkay to false for the step.
	 *
	 * @param {boolean} [stepSpec.allowAutomaticNext=true] By default, if
	 * you call .next() and do not specify a Next button a next button will be
	 * generated automatically.
	 *
	 * To suppress this, set allowAutomaticNext to false for the step.
	 *
	 * @param {boolean} [stepSpec.autoFocus=true] By default, the browser will scroll
	 * to make the guider visible.
	 *
	 * To avoid this, set autoFocus to false for the step.
	 *
	 * @param {boolean} [stepSpec.closeOnClickOutside=true] Close the
	 *  guider when the user clicks elsewhere on screen
	 *
	 * @param {Array} stepSpec.buttons Buttons for step.  See also above
	 *  regarding button behavior and defaults.  Each button can have:
	 *
	 * @param {string} stepSpec.buttons.name Text of button.  Used only
	 *  for on-wiki tours
	 * @param {string} stepSpec.buttons.namemsg Message key for text of
	 *  button.  Used only for extension-defined tours
	 *
	 * @param {Function} stepSpec.buttons.onclick Function to execute
	 *  when button is clicked
	 *
	 * @param {string} stepSpec.buttons.action
	 *  Action keyword.  For actions listed below, you do not need to manually
	 *  specify button name and onclick.
	 *
	 *  Instead, you can pass a defined action as part of the buttons array.  The
	 *  actions currently supported are:
	 *
	 *  - next - Goes to the next step.  Requires mw.guidedTour.StepBuilder#next
	 *    also be called, to specify how the next step is determined.
	 *  - back - Goes back specified step.  Requires mw.guidedTour.StepBuilder#back
	 *    also be called, to specify how the step is determined.
	 *  - okay - An arbitrary function is used for okay button.  This must have
	 *    an accompanying 'onclick':
	 *
	 *        {
	 *            action: 'okay',
	 *            onclick: function () {
	 *                 // Do something...
	 *            }
	 *        }
	 *
	 *  - end - Ends the tour.
	 *  - wikiLink - links to a page on the same wiki
	 *  - externalLink - links to an external page
	 *
	 *  A button action with no parameters looks like:
	 *
	 *     {
	 *         action: 'next'
	 *     }
	 *
	 * Multiple action fields for a single button are not possible.
	 *
	 * @param {string} stepSpec.buttons.page Page to link to, only for
	 *  the wikiLink action
	 * @param {string} stepSpec.buttons.url URL to link to, only for the
	 *  externalLink action
	 * @param {string} stepSpec.buttons.type string to add button type
	 *  class.  Currently supports: progressive, destructive.
	 * @param {string} [stepSpec.buttons.classString] Space-separated list of
	 *  additional class names
	 *
	 *
	 * @return {mw.guidedTour.StepBuilder} Created StepBuilder object
	 * @throws {mw.guidedTour.TourDefinitionError} When the step specification is
         *  invalid
	 */
	TourBuilder.prototype.step = function ( stepSpec ) {
		var stepBuilder;

		if ( this.tour.steps[ stepSpec.name ] ) {
			throw new gt.TourDefinitionError( 'The name "' + stepSpec.name + '" is already taken.  Two steps in a tour can not have the same name.' );
		}

		stepBuilder = new gt.StepBuilder( this.tour, stepSpec );
		this.tour.steps[ stepSpec.name ] = stepBuilder.step;
		this.tour.stepCount++;
		return stepBuilder;
	};

	/**
	 * Creates and returns a StepBuilder, marking it as the first step (entry point) of
	 * the tour.  This can only be called once.
	 *
	 * @param {Object} stepSpec specification for step; see #step method for details.
	 *
	 * @return {mw.guidedTour.StepBuilder} Created StepBuilder
	 * @throws {mw.guidedTour.TourDefinitionError} When the step specification is
	 *   invalid, or the first step has already been specified
	 */
	TourBuilder.prototype.firstStep = function ( stepSpec ) {
		var stepBuilder;

		if ( this.tour.firstStep !== null ) {
			throw new gt.TourDefinitionError( 'You can only specify one first step.' );
		}

		stepBuilder = this.step( stepSpec );
		this.tour.firstStep = stepBuilder.step;
		return stepBuilder;
	};

	mw.guidedTour.TourBuilder = TourBuilder;
}() );
