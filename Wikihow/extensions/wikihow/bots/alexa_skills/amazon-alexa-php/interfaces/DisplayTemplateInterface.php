<?php

/**
 * Interface for bulding Templates as referenced here:
 *
 * https://developer.amazon.com/public/solutions/alexa/alexa-skills-kit/docs/display-interface-reference#bodytemplate1
 *
 */
interface DisplayTemplateInterface {

	/**
	 * render()
	 *
	 * Return the template as an associate array for conversion to JSON
	 *
	 * @return array
	 */
	public function render();
}
