//QA test
(function($) {
	window.WH = window.WH || {};
	$(document).ready(function() {
	   if ($('#qanda_test_on').length) {
		   // fireLoadTrack();
		   // $(window).bind('scroll.qa_testing', checkQAtest);
		   // checkQAtest();
		   // readyTheAccordion();
		   // readySideList();
		   startQAtimer();
	   }
	});

	function checkQAtest() {
	   var docViewTop = $(window).scrollTop();
	   var docViewBottom = docViewTop + $(window).height();

	   var offset = $('#qanda_test_on').offset();
	   var qaing = offset ? offset.top <= docViewBottom : false;
	   if (qaing) {
		   $(window).unbind('scroll.qa_testing'); //don't need this anymore
		   startQAtimer();
	   }
	}
	
	function readyTheAccordion() {
	   //open first one
	   $('#qa_test2 .qa_test_hdr:first').next().slideDown();
	   $('#qa_test2 .qa_test_hdr:first').find('i').removeClass('fa-plus-square-o').addClass('fa-minus-square-o');

	   //click handlers for headers
	   $('#qa_test2 .qa_test_hdr').click(function() {
		   var icon = $(this).find('i');
		   if ($(icon).hasClass('fa-plus-square-o')) {
			   $(this).next().slideDown();
			   $(icon).removeClass('fa-plus-square-o').addClass('fa-minus-square-o');
		   }
		   else {
			   $(this).next().slideUp();
			   $(icon).removeClass('fa-minus-square-o').addClass('fa-plus-square-o');
		   }
	   });
	}
	
	function readySideList() {
		$('#qa_see_all').click(function() {
			$(this).fadeOut(function() {
				$('.qa_hidden').show();
			});
			return false;
		});
	}

	function fireLoadTrack() {
		var label = wgTitle;
		var action = '_load';
		var cat = 'qatest';
		var type = 'qa_';
		var test = '';

		if ($('#qa_test1').length) {
			test = 'simple';
		}
		else if ($('#qa_test2').length) {
			test = '2';
		}
		else {
			test = '0';
		}
		
		//onload tracker!
		WH.whEvent(cat, type+test+action, label);
	}

	function startQAtimer() {
		var label = wgTitle;
		var action = '_scroll';
		var cat = 'qatest';
		var type = 'qa_';
		var test = '';
		
		if ($('#qa_test1').length) {
			test = 'simple';
		}
		else if ($('#qa_test2').length) {
			test = '2';
		}
		else {
			test = '0';
		}
		
		//fire off the first scroll tracker
		WH.whEvent(cat, type+test+'_load', label);
		
		//5 seconds
		setTimeout(function() {
			action = '_5s';
			WH.whEvent(cat, type+test+action, label);
		},5000);
		
		//10 seconds
		setTimeout(function() {
			action = '_10s';
			WH.whEvent(cat, type+test+action, label);		
		},10000);
		
		//30 seconds
		setTimeout(function() {
			action = '_30s';
			WH.whEvent(cat, type+test+action, label);	
		},30000);
		
		//60 seconds
		setTimeout(function() {
			action = '_60s';
			WH.whEvent(cat, type+test+action, label);
		},60000);
		
		//180 seconds
		setTimeout(function() {
			action = '_180s';
			WH.whEvent(cat, type+test+action, label);		
		},180000);
	}
	
})(jQuery);