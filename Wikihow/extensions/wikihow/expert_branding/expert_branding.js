(function() {
	window.WH = window.WH || {};
	window.WH.ExpertBranding = {
		
		startTest01: function() {
			WH.ExpertBranding.addTracking('1');
		},
		
		startTest02: function() {
			WH.ExpertBranding.addTracking('2');
		},
		
		startControl: function() {
			WH.ExpertBranding.addTracking('c');
		},
		
		addTracking: function(num) {
			var cat = 'expert_brand';
			var label = wgTitle;
			var actionA = 'ex_'+num+'_';
			var version = '1';
			
			WH.whEvent(cat, actionA+'loaded', label, '', version);

			setTimeout(function() {
				WH.whEvent(cat, actionA+'10s', label, '', version);
			},10000);
			
			setTimeout(function() {
				WH.whEvent(cat, actionA+'180s', label, '', version);
			},180000);
		}
	};
	
	$(document).ready(function() {
		if ($('#eb_test01_on').length) {
			WH.ExpertBranding.startTest01();
		}
		else if ($('#eb_test02_on').length) {
			WH.ExpertBranding.startTest02();
		}
		else {
			WH.ExpertBranding.startControl();
		}
	});
}());