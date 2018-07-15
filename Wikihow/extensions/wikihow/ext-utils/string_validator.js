(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.StringValidator = (function () {
		"use strict";

		function StringValidator(config) {
			this.config = config;
			var failedRules = [];

			this.getFailedRules = function() {
				return failedRules;
			}

			this.addFailedRule = function(rule) {
				failedRules.push(rule);
			}
		}

		StringValidator.prototype.short_rule = "too short";
		StringValidator.prototype.capitalization_rule = "capitalization";
		StringValidator.prototype.email_rule = "email";
		StringValidator.prototype.phone_rule = "phone";
		StringValidator.prototype.url_rule = "url";
		StringValidator.prototype.minspaces_rule = "not enough spaces";

		StringValidator.prototype.validate = function(text) {
			var isValid = true;

			if (this.config['capitalization'] && !this.isValidCapitalization(text)) {
				this.addFailedRule(this.capitalization_rule);
				isValid = false;
			}

			if (this.config['email'] && this.containsEmailAddress(text)) {
				this.addFailedRule(this.email_rule);
				isValid = false;
			}

			if (this.config['phone'] && this.containsPhoneNumber(text)) {
				this.addFailedRule(this.phone_rule);
				isValid = false;
			}

			if (this.config['url'] && this.containsUrlParts(text)) {
				this.addFailedRule(this.url_rule);
				isValid = false;
			}

			if (this.config['minlength'] && !this.isLongerThan(text, this.config['minlength'] - 1)) {
				this.addFailedRule(this.short_rule);
				isValid = false;
			}

			if (this.config['minspaces'] && !this.hasMinimumSpaces(text, this.config['minspaces'])) {
				this.addFailedRule(this.minspaces_rule);
				isValid = false;
			}

			return isValid;
		};

		// Do some basic checking of punctuation and sentence capitalization
		StringValidator.prototype.isValidCapitalization = function(answer) {
			var isValid = true;

			var punctuationRegex = /[?.!][ \n]+/g;
			var capitalizationRegex = /[.?!][ \n]+[A-Z]/g;

			var punctuationMatches = answer.match(punctuationRegex);
			punctuationMatches = punctuationMatches ? punctuationMatches.length : 0;

			var capitalizationMatches = answer.match(capitalizationRegex);
			capitalizationMatches = capitalizationMatches ? capitalizationMatches.length : 0;

			if (punctuationMatches > 1 && capitalizationMatches / (punctuationMatches - 1) < .5) {
				isValid = false;
			}

			return isValid;
		};

		StringValidator.prototype.containsEmailAddress = function(str) {
			var contains = false;
			// Regex adapted from http://www.regular-expressions.info/email.html
			if (str.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i)) {
				contains = true;
			}
			return contains;
		};

		StringValidator.prototype.containsPhoneNumber = function(str) {
			var contains = false;
			// Regex taken from http://www.regextester.com/17
			if (str.match(/(?:(?:\+?1\s*(?:[.-]\s*)?)?(?:\(\s*([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\s*\)|([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9]))\s*(?:[.-]\s*)?)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\s*(?:[.-]\s*)?([0-9]{4})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?/)) {
				contains = true;
			}
			return contains;
		};

		StringValidator.prototype.containsUrlParts = function(str) {
			var contains = false;
			// Regex adapted from http://www.regular-expressions.info/email.html
			if (str.match(/www|http|\/\//i)) {
				contains = true;
			}
			return contains;
		};

		StringValidator.prototype.isLongerThan = function(str, minChars) {
			return str.length > minChars;
		};

		StringValidator.prototype.hasMinimumSpaces = function(str, minSpaces) {
			var hasMinSpaces = true;

			var matches = str.match(/\s/g);
			if (matches && matches.length < minSpaces) {
				hasMinSpaces = false;
			}

			return hasMinSpaces;
		};

		return StringValidator;
	}());
}());
