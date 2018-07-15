(function () {
	'use strict';
	window.WH = WH || {};
	window.WH.MethodHelpfulness.MethodThumbs.Mobile = function () {};
	window.WH.MethodHelpfulness.MethodThumbs.Mobile.prototype = new window.WH.MethodHelpfulness.MethodThumbs();

	window.WH.MethodHelpfulness.MethodThumbs.Mobile.prototype.platform = 'mobile';

	window.WH.MethodHelpfulness.MethodThumbs.Mobile.prototype.parentElements = $('.steps_list_2');

	window.WH.MethodHelpfulness.MethodThumbs.Mobile.prototype.prepareSubmit = function (e) {
		var tgt = $(e.target);

		if (!tgt.hasClass('mhmt-vote')) {
			tgt = tgt.parents('.mhmt-vote');
		}

		var upvote = tgt.hasClass('mhmt-up');
		if (!upvote && !tgt.hasClass('mhmt-down')) {
			return false;
		}

		var container = tgt.parents('.methodhelpfulness');
		var method = container.data('method');

		var inner = container.find('.mh-method-thumbs-inner');
		if (!inner.hasClass('mhmt-not-voted')) {
			return false;
		}

		var data = {
			type: 'method_thumbs',
			aid: wgArticleId,
			platform: this.platform,
			label: '',
			method: method,
			voteType: upvote ? 'vote_yes' : 'vote_no'
		};

		inner.removeClass('mhmt-not-voted')
			.addClass(upvote ? 'mhmt-voted-up' : 'mhmt-voted-down');

		inner.parent().addClass('mhmt-v');

		this.submit(data);

		return false;
	};

	window.WH.MethodHelpfulness.MethodThumbs.Mobile.prototype.submitDone = function (result) {
		return;
	};

	window.WH.MethodHelpfulness.MethodThumbs.Mobile.prototype.submitFail = function (result, t, e) {
		return;
	};

	$(document).ready(function () {
		var mhmtm = new WH.MethodHelpfulness.MethodThumbs.Mobile();
		mhmtm.initialize(mhmtm.parentElements);
	});
}());

