/**********
 *  This JS class is the starting point for any "otpi" tests we run.
 *  This check runs on all article pages. Add a snippet of code here
 *  to test if a specific test should run, then load the appropriate
 *  resource module. When tests are done, the code in this file should
 *  be deleted to keep the size small.
 **********/

(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.WikihowTests = {

		init: function() {
			//qaexperttest
			if($(".qa_expert_area").length > 0) {
				mw.loader.load('ext.wikihow.wikihowtests.expertqanda');
			}
		}
	};

	$(document).ready(function() {
		WH.WikihowTests.init();
	});
})();
