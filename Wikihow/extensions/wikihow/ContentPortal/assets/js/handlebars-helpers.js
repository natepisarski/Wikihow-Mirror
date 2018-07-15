
(function () {
	"use strict";
	Handlebars.registerHelper('isSelected', function (optId, id) {
		return optId === id ? 'selected="true"' : '';
	});
	
	Handlebars.registerHelper('classIf', function (val, className) {
		return val ? className : '';
	});
}());
