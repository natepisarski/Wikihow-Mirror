<?php
//$wgHooks['ParserBeforeStrip'][] = array('wfAddCaptionsToImageSteps');

/****
 *
 *
 * Adds a caption to images in steps that don't have them.
 *
 */
function wfAddCaptionsToImageSteps(&$parser, &$text, &$stripstate) {

	// only do this on main namespace articles that have steps
	$title = $parser->mTitle;
	if (!$title || !$title->inNamespace(NS_MAIN)) {
		return true;
	}
	if (!preg_match("@==[ ]*" . wfMessage('steps') . "@", $text)) {
		return true;
	}

	// find the steps section
	// Article::getSection seeems to break things in the middle of a parse, so we
	// we have to do it ourselves
	$index = 0;
	$steps = null;
	//$sections = preg_split("@^(==[^=]*==)@m", $text, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$sections = preg_split("@^(==[^=]+==)@m", $text, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	foreach ($sections as $section) {
		$index++;
		if (preg_match("@^==[ ]*" . wfMessage('steps') . "@", $section)) {
			$steps = preg_split("@\n@m", $sections[$index]);
			break;
		}
	}

	// barf!
	if (!$steps ) {
		return true;
	}

	// iterate over the steps, find the images for each one
	$newsteps = array();
	$changed = false;
	foreach ($steps as $s) {
		// pretty the step up, remove images, etc.
		$p  = preg_replace("@\[\[Image:[^\]]*\]\]@", "", $s);
		preg_match("@^#[^\.]*@", $p, $matches);
		$f = trim(preg_replace("@^#@", "", $matches[0]));
		$f = preg_replace("@''[']?@", "", $f) . ".";
		preg_match_all("@\[\[[^\]]*\]\]@", $f, $matches);
		foreach ($matches[0] as $m) {
			if (preg_match("@\|@", $m)) {
				$x = preg_replace("@.*\|@", "", $m);
				$f = str_replace($m, $x, $f);
			}
		}
		$f = preg_replace("@\[|\]@", "", $f);
		$f = preg_replace("@^[^A-Za-z0-9]*@", "", $f);
		preg_match("@\[\[Image:[^\]]*\]\]@", $s, $images);
		if (sizeof($images) > 0) {
			// just put a caption in the first image
			$parts = preg_split("@\|@", preg_replace("@\[|\]@", "", $images[0]));
			if (sizeof($parts) == 1) {
				// boundary case here it's just [[Image:hidyho.jpg]]
				$img = "[[" . $parts[0] . "|". $f . "]]";
			} else {
				// otherwise it has params
				$last = $parts[sizeof($parts)-1];

				// ignore the "description" caption and empty descriptions
				if (strtolower($last) == "description" || trim($last) == "") {
					array_pop($parts);
					$last = $parts[sizeof($parts)-1];
				}

				$caption = false;
				foreach ($parts as $p) {
					if (!preg_match("@px$@", $p)
						&& !preg_match("@^Image:@", $p)
						&& !in_array($p, array("thumb", "border", "right", "left"))) {
						$caption = true;
					}
				}
				// do we have a caption?
				if (!$caption) {
					// if the thumb param isn't there, the caption won't show
					if (!in_array("thumb", $parts)) {
						$parts[] = "thumb";
					}
					// put it all back together and do the replacement
					$img =  "[[" . implode("|", $parts) . "|" . $f . "]]";
					$s = str_replace($images[0], $img, $s);
					$changed = true;
				}
			}
		}
		// add it back to the new list
		$newsteps[] = $s;
	}

	// short-circuit return
	if (!$changed) {
		return true;
	}

	// put it all back together
	$sections[$index] = implode("\n", $newsteps);
	$text = implode("\n", $sections);
	return true;
}
