$.fn.queryBuilder.define('select2', function(options) {
	if (!$.fn.select2) {
		Utils.error('MissingLibrary', 'Select2 is required for this plugin!');
	}

	this.on('afterCreateRuleFilters', function (e, rule) {
		rule.$el.find('.rule-filter-container [name$=_filter]').removeClass('form-control').select2(options);
	});

	this.on('afterCreateRuleOperators', function (e, rule) {
		rule.$el.find('.rule-operator-container [name$=_operator]').removeClass('form-control').select2(options);
	});

	this.on('afterUpdateRuleFilter', function (e, rule) {
		rule.$el.find('.rule-filter-container [name$=_filter]').removeClass('form-control').select2(options)
	});

	this.on('afterUpdateRuleOperator', function (e, rule) {
		rule.$el.find('.rule-operator-container [name$=_operator]').removeClass('form-control').select2(options);
	});
});
