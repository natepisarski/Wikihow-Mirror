(function () {
	window.WH = window.WH || {};
	window.WH.CategoryContacts = {
		
		URL: '/Special:CategoryContacts',
		
		init: function () {
			$('#cc_add_btn').click(function() {
				WH.CategoryContacts.grabAddContacts();
				return false;
			});
			
			$('#cc_stop_btn').click(function() {
				WH.CategoryContacts.grabStopContacts();
				return false;
			});
		},
		
		grabAddContacts: function () {
			this.cleanUp();
			$.getJSON(this.URL+'?action=add', function(data) {
				if (!data['good']) {
					$('#cc_add_result_bad').html(mw.message('cc_error').text()).slideDown();
					return;
				}
				
				$('#cc_add_result_good').html(data['good']).slideDown();
				if (data['bad']) $('#cc_add_result_bad').html(data['bad']).slideDown();
			});
		},
		
		grabStopContacts: function () {
			this.cleanUp();
			$.getJSON(this.URL+'?action=stop', function(data) {
				if (!data['good']) {
					$('#cc_stop_result_bad').html(mw.message('cc_error').text()).slideDown();
					return;
				}
				
				$('#cc_stop_result_good').html(data['good']).slideDown();
				if (data['bad']) $('#cc_stop_result_bad').html(data['bad']).slideDown();
			});
		},
		
		cleanUp: function() {
			$('.cc_result').slideUp();
		}
	};
			
	$(document).ready(function() {
		WH.CategoryContacts.init();
	});
		
}());