window.WH = window.WH || {};
window.WH.DataStore = (function () {
	"use strict";

	function DataStore(key, articleId) {
		this.key = key;
		this.articleId = articleId;
		this.data = this.parseStorage();
	}

	DataStore.prototype = {

		addItem: function (id) {
			this.data[this.articleId].push(id);
			// make sure we have no dupes...
			this.data[this.articleId] = _.uniq(this.data[this.articleId]);
			this.save();
		},

		getItems: function () {
			return this.data[this.articleId] || [];
		},

		save: function () {
			if (this.isEnabled()) {
				localStorage.setItem(this.key, JSON.stringify(this.data));
			}
		},

		isEnabled: function () {
			return !_.isUndefined(window.localStorage);
		},

		parseStorage: function () {
			var obj = {};
			if (this.isEnabled()) {
				obj = localStorage.getItem(this.key) ? $.parseJSON(localStorage.getItem(this.key)) : {};
			} else {
				obj = {};
			}

			obj[this.articleId] = obj[this.articleId] || [];
			return obj;
		},

		removeItem: function (id) {
			this.data[this.articleId] = _.without(this.data[this.articleId], id);
			this.save();
		}

	};

	return DataStore;
}());