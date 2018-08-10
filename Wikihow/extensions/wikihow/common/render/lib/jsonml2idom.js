/*
 * The MIT License (MIT)
 * 
 * Copyright (c) 2015 Paolo Caminiti
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
var jsonml2idom = (function () {
	'use strict';

	var elementOpenStart = IncrementalDOM.elementOpenStart;
	var elementOpenEnd = IncrementalDOM.elementOpenEnd;
	var elementClose = IncrementalDOM.elementClose;
	var currentElement = IncrementalDOM.currentElement;
	var skip = IncrementalDOM.skip;
	var attr = IncrementalDOM.attr;
	var text = IncrementalDOM.text;

	function openTag(head, keyAttr) {
		var dotSplit = head.split('.');
		var hashSplit = dotSplit[0].split('#');

		var tagName = hashSplit[0] || 'div';
		var id = hashSplit[1];
		var className = dotSplit.slice(1).join(' ');

		elementOpenStart(tagName, keyAttr);

		if (id) attr('id', id);
		if (className) attr('class', className);

		return tagName;
	}

	function applyAttrsObj(attrsObj) {
		for (var k in attrsObj) {
			attr(k, attrsObj[k]);
		}
	}

	function parse(markup) {
		var head = markup[0];
		var attrsObj = markup[1];
		var hasAttrs = attrsObj && attrsObj.constructor === Object;
		var firstChildPos = hasAttrs ? 2 : 1;
		var keyAttr = hasAttrs && attrsObj.key;
		var skipAttr = hasAttrs && attrsObj.skip;

		var tagName = openTag(head, keyAttr);

		if (hasAttrs) applyAttrsObj(attrsObj);

		elementOpenEnd();

		if (skipAttr) {
			skip();
		} else {
			for (var i = firstChildPos, len = markup.length; i < len; i++) {
				var node = markup[i];

				if (node === undefined) continue;

				switch (node.constructor) {
					case Array:
						parse(node);
						break;
					case Function:
						node(currentElement());
						break;
					default:
						text(node);
				}
			}
		}

		elementClose(tagName);
	}

	return parse;
})();
