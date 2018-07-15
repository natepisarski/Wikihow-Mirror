/*
*
*
 */
(function () {
	"use strict";
	window.WH = window.WH || {};
	$(document).ready(function() {
		window.WH.ArticleDisplayWidget = {
			shown: false,
			$root: $('#adw_root'),
			$html: $('#adw_html'),
			$message: $('#adw_message'),
			$chevron: $('#adw_chevron'),
			$spinner: $('#adw_spinner'),
			request: null,
			showSpinner: true,
			aid: 0,
			init: function(data) {
				var data = data || {};

				if (typeof data.showSpinner !== 'undefined') {
					this.showSpinner = data.showSpinner;
				}

				if (typeof data.aid !== 'undefined') {
					this.aid = data.aid;
				}

				this.initListeners();
			},
			initListeners: function() {
				$('body').on('click', '#adw_toolbar', $.proxy(function(){
					if (this.isArticleVisible()) {
						this.hide();
					} else {
						if (this.$html.is(':empty')) {
							this.getArticleHtml($.proxy(this.show, this));
						} else {
							this.show();
						}
					}
				}, this));
			},
			updateArticleId: function(aid, html) {
				this.aid = aid;

				if (html) {
					this.hide($.proxy(function() {
						this.setArticleHtml(html);
						this.shown = false;
					}, this));
				} else {
					// Clear html so lazy loading can occur
					this.reset();
				}

			},
			isArticleVisible: function() {
				return this.$chevron.hasClass('fa-chevron-up');
			},
			show: function(callback) {
				var that = this;
				this.$html.slideDown('fast', function() {
					that.$message.text(mw.message('adw_hide'));
					that.$chevron.addClass('fa-chevron-up').removeClass('fa-chevron-down');

					if (!that.shown) {
						// Fire off events to properly init th earticle
						$(document).trigger('rcdataloaded');
						mw.mobileFrontend.emit('page-loaded');
					}
					if (typeof(callback) === typeof(Function)) {
						callback();
					}
				});
			},
			abortRequest: function() {
				if (this.request) {
					this.request.abort();
					this.hide();
				}
			},
			hide: function(callback) {
				var that = this;
				this.$html.slideUp('fast', function() {
					that.$message.text(mw.message('adw_show'));
					that.$chevron.addClass('fa-chevron-down').removeClass('fa-chevron-up');
					if (typeof(callback) === typeof(Function)) {
						//var callback = $.proxy(callback, that);
						callback();
					}
				});
			},
			gone: function () {
				this.$root.hide();
				if (this.request) {
					this.request.abort();
				}
			},
			getArticleHtml: function(callback) {
				var that = this;

				if (this.showSpinner) {
					this.$spinner.addClass('loading');
				}

				this.request = $.post('/Special:ArticleDisplayWidget', {a: 'fetch', aid: this.aid}, function(result) {
					that.$html.html(result);
					that.$spinner.removeClass('loading');
					if (typeof(callback) === typeof(Function)) {
						callback();
					}
				});
			},
			setArticleHtml: function(html) {
				this.$html.html(html);
			},
			reset: function () {
				this.hide($.proxy(function() {
					this.$html.empty();
					this.shown = false;
				}, this));
			},
			onBeginArticleChange: function() {
				this.abortRequest();
				this.reset();
				this.aid = 0;
				this.$spinner.removeClass('loading');
			}
		};
	});

}());
